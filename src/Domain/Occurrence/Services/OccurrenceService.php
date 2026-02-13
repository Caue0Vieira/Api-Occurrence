<?php

declare(strict_types=1);

namespace Domain\Occurrence\Services;

use Application\DTOs\AcceptedCommandResult;
use Application\UseCases\CreateOccurrence\CreateOccurrenceCommand;
use Application\UseCases\CreateOccurrence\CreateOccurrenceHandler;
use Application\UseCases\ResolveOccurrence\ResolveOccurrenceCommand;
use Application\UseCases\ResolveOccurrence\ResolveOccurrenceHandler;
use Application\UseCases\StartOccurrence\StartOccurrenceCommand;
use Application\UseCases\StartOccurrence\StartOccurrenceHandler;
use Domain\Occurrence\Collections\OccurrenceStatusCollection;
use Domain\Occurrence\Collections\OccurrenceTypeCollection;
use Domain\Occurrence\Entities\Occurrence;
use Domain\Occurrence\Repositories\OccurrenceRepositoryInterface;
use Domain\Shared\ValueObjects\Uuid;
use Illuminate\Pagination\LengthAwarePaginator;

readonly class OccurrenceService
{
    public function __construct(
        private OccurrenceRepositoryInterface $occurrenceRepository,
        private CreateOccurrenceHandler $createOccurrenceHandler,
        private StartOccurrenceHandler $startOccurrenceHandler,
        private ResolveOccurrenceHandler $resolveOccurrenceHandler,
    ) {}

    public function findByIdWithDispatches(Uuid $id): ?Occurrence
    {
        return $this->occurrenceRepository->findByIdWithDispatches($id);
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

    public function createOccurrence(
        string $externalId,
        string $type,
        string $description,
        string $reportedAt,
        string $idempotencyKey,
        string $source = 'external_system'
    ): AcceptedCommandResult {
        $command = new CreateOccurrenceCommand(
            externalId: $externalId,
            type: $type,
            description: $description,
            reportedAt: $reportedAt,
            idempotencyKey: $idempotencyKey,
            source: $source
        );

        return $this->createOccurrenceHandler->handle($command);
    }

    public function startOccurrence(
        string $occurrenceId,
        string $idempotencyKey,
        string $source = 'internal_system'
    ): AcceptedCommandResult {
        $command = new StartOccurrenceCommand(
            occurrenceId: $occurrenceId,
            idempotencyKey: $idempotencyKey,
            source: $source
        );

        return $this->startOccurrenceHandler->handle($command);
    }

    public function resolveOccurrence(
        string $occurrenceId,
        string $idempotencyKey,
        string $source = 'internal_system'
    ): AcceptedCommandResult {
        $command = new ResolveOccurrenceCommand(
            occurrenceId: $occurrenceId,
            idempotencyKey: $idempotencyKey,
            source: $source
        );

        return $this->resolveOccurrenceHandler->handle($command);
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
}
