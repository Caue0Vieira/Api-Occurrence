<?php

declare(strict_types=1);

namespace Domain\Occurrence\Services;

use Application\DTOs\AcceptedCommandResult;
use Application\DTOs\ListOccurrencesFilter;
use Application\DTOs\ListOccurrencesResult;
use Application\Ports\OccurrenceListCacheInterface;
use Application\Support\OutboxEventResolver;
use Domain\Idempotency\Enums\CommandSource;
use Domain\Idempotency\Enums\CommandStatus;
use Domain\Idempotency\Exceptions\DuplicateCommandException;
use Domain\Idempotency\Repositories\CommandInboxReadRepositoryInterface;
use Domain\Idempotency\Repositories\CommandInboxWriteRepositoryInterface;
use Domain\Occurrence\Collections\OccurrenceStatusCollection;
use Domain\Occurrence\Collections\OccurrenceTypeCollection;
use Domain\Occurrence\Entities\Occurrence;
use Domain\Occurrence\Exceptions\OccurrenceAlreadyExistsException;
use Domain\Occurrence\Exceptions\OccurrenceNotFoundException;
use Domain\Occurrence\Repositories\OccurrenceRepositoryInterface;
use Domain\Outbox\Repositories\OutboxWriteRepositoryInterface;
use Domain\Shared\ValueObjects\Uuid;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class OccurrenceService
{
    public function __construct(
        private OccurrenceRepositoryInterface $occurrenceRepository,
        private CommandInboxWriteRepositoryInterface $commandInboxWriteRepository,
        private CommandInboxReadRepositoryInterface $commandInboxReadRepository,
        private OutboxWriteRepositoryInterface $outboxWriteRepository,
        private OutboxEventResolver $outboxEventResolver,
        private OccurrenceListCacheInterface $occurrenceListCache,
    ) {}

    public function findOccurrenceByIdWithDispatches(Uuid $id): ?Occurrence
    {
        return $this->occurrenceRepository->findOccurrenceByIdWithDispatches($id);
    }

    public function listOccurrences(
        ?string $statusCode = null,
        ?string $typeCode = null,
        int $perPage = 50,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->occurrenceRepository->listOccurrences(
            statusCode: $statusCode,
            typeCode: $typeCode,
            perPage: $perPage,
            page: $page
        );
    }

    public function listOccurrencesWithCache(
        ?string $status = null,
        ?string $type = null,
        int $limit = 50,
        int $page = 1
    ): ListOccurrencesResult {
        $normalizedFilter = new ListOccurrencesFilter(
            status: $status,
            type: $type,
            limit: max(1, min($limit, 200)),
            page: max(1, $page),
        );

        $cachedResult = $this->occurrenceListCache->get($normalizedFilter);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        $paginator = $this->listOccurrences(
            statusCode: $normalizedFilter->status,
            typeCode: $normalizedFilter->type,
            perPage: $normalizedFilter->limit,
            page: $normalizedFilter->page,
        );

        $result = new ListOccurrencesResult(
            occurrences: $paginator->items(),
            total: $paginator->total(),
            page: $paginator->currentPage(),
            limit: $paginator->perPage(),
        );

        $this->occurrenceListCache->put($normalizedFilter, $result);

        return $result;
    }

    public function createOccurrence(
        string $externalId,
        string $type,
        string $description,
        string $reportedAt,
        string $idempotencyKey,
        CommandSource $source = CommandSource::EXTERNAL
    ): AcceptedCommandResult {
        Log::info('[Service] CreateOccurrence started', [
            'externalId' => $externalId,
            'idempotencyKey' => $idempotencyKey,
        ]);

        try {
            // Validar se já existe ocorrência com o mesmo external_id
            if ($this->occurrenceRepository->existsByExternalId($externalId)) {
                throw OccurrenceAlreadyExistsException::withExternalId($externalId);
            }

            // Validar se já existe comando com o mesmo external_id mas idempotencyKey diferente
            $normalizedIdempotencyKey = \Infrastructure\Support\CommandNormalizationHelper::normalizeIdempotencyKey($idempotencyKey);
            if ($this->commandInboxReadRepository->existsByTypeAndExternalIdWithDifferentIdempotencyKey('create_occurrence', $externalId, $normalizedIdempotencyKey)) {
                throw OccurrenceAlreadyExistsException::withExternalId($externalId);
            }

            $payload = [
                'externalId' => $externalId,
                'type' => $type,
                'description' => $description,
                'reportedAt' => $reportedAt,
            ];

            $registration = $this->commandInboxWriteRepository->registerOrGet(
                idempotencyKey: $idempotencyKey,
                source: $source->value,
                type: 'create_occurrence',
                scopeKey: $externalId,
                payload: $payload,
            );

            if ($registration->shouldDispatch && $registration->isNew) {
                $this->registerOutboxEvent('create_occurrence', $registration->commandId);

                return new AcceptedCommandResult(
                    commandId: $registration->commandId,
                    status: CommandStatus::RECEIVED->value
                );
            }

            // Se não é novo, significa que é um caso de idempotência (comando já existe)
            if (!$registration->isNew) {
                throw DuplicateCommandException::withCommandId($registration->commandId);
            }

            return new AcceptedCommandResult(
                commandId: $registration->commandId,
                status: $registration->status
            );
        } catch (Throwable $e) {
            Log::error('[Service] Error in CreateOccurrence', [
                'externalId' => $externalId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function getOccurrenceByIdWithDispatchesOrFail(string $occurrenceId): Occurrence
    {
        $occurrence = $this->findOccurrenceByIdWithDispatches(Uuid::fromString($occurrenceId));

        if ($occurrence === null) {
            throw OccurrenceNotFoundException::withId($occurrenceId);
        }

        return $occurrence;
    }

    public function startOccurrence(
        string $occurrenceId,
        string $idempotencyKey,
        CommandSource $source = CommandSource::INTERNAL
    ): AcceptedCommandResult {
        // Validar se a ocorrência existe antes de criar o comando
        $occurrence = $this->occurrenceRepository->findOccurrenceById(Uuid::fromString($occurrenceId));
        if ($occurrence === null) {
            throw OccurrenceNotFoundException::withId($occurrenceId);
        }

        $registration = $this->commandInboxWriteRepository->registerOrGet(
            idempotencyKey: $idempotencyKey,
            source: $source->value,
            type: 'start_occurrence',
            scopeKey: $occurrenceId,
            payload: ['occurrenceId' => $occurrenceId],
        );

        if ($registration->shouldDispatch && $registration->isNew) {
            $this->registerOutboxEvent('start_occurrence', $registration->commandId);

            return new AcceptedCommandResult(
                commandId: $registration->commandId,
                status: CommandStatus::RECEIVED->value
            );
        }

        // Se não é novo, significa que é um caso de idempotência (comando já existe)
        if (!$registration->isNew) {
            throw DuplicateCommandException::withCommandId($registration->commandId);
        }

        return new AcceptedCommandResult(
            commandId: $registration->commandId,
            status: $registration->status
        );
    }

    public function resolveOccurrence(
        string $occurrenceId,
        string $idempotencyKey,
        CommandSource $source = CommandSource::INTERNAL
    ): AcceptedCommandResult {
        // Validar se a ocorrência existe antes de criar o comando
        $occurrence = $this->occurrenceRepository->findOccurrenceById(Uuid::fromString($occurrenceId));
        if ($occurrence === null) {
            throw OccurrenceNotFoundException::withId($occurrenceId);
        }

        $registration = $this->commandInboxWriteRepository->registerOrGet(
            idempotencyKey: $idempotencyKey,
            source: $source->value,
            type: 'resolve_occurrence',
            scopeKey: $occurrenceId,
            payload: ['occurrenceId' => $occurrenceId],
        );

        if ($registration->shouldDispatch && $registration->isNew) {
            $this->registerOutboxEvent('resolve_occurrence', $registration->commandId);

            return new AcceptedCommandResult(
                commandId: $registration->commandId,
                status: CommandStatus::RECEIVED->value
            );
        }

        // Se não é novo, significa que é um caso de idempotência (comando já existe)
        if (!$registration->isNew) {
            throw DuplicateCommandException::withCommandId($registration->commandId);
        }

        return new AcceptedCommandResult(
            commandId: $registration->commandId,
            status: $registration->status
        );
    }

    /**
     * Retorna todos os tipos de ocorrência disponíveis
     * @return OccurrenceTypeCollection
     */
    public function findOccurrenceTypes(): OccurrenceTypeCollection
    {
        return $this->occurrenceRepository->findOccurrenceTypes();
    }

    /**
     * Retorna todos os status de ocorrência disponíveis
     * @return OccurrenceStatusCollection
     */
    public function findOccurrenceStatuses(): OccurrenceStatusCollection
    {
        return $this->occurrenceRepository->findOccurrenceStatuses();
    }

    private function registerOutboxEvent(string $commandType, string $commandId): void
    {
        $event = $this->outboxEventResolver->resolve($commandType);

        $this->outboxWriteRepository->addPendingEvent(
            aggregateType: $event['aggregateType'],
            aggregateId: $commandId,
            eventType: $event['eventType'],
        );
    }
}
