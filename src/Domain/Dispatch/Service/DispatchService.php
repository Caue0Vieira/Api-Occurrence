<?php

declare(strict_types=1);

namespace Domain\Dispatch\Service;

use Application\DTOs\AcceptedCommandResult;
use Application\Support\OutboxEventResolver;
use Domain\Idempotency\Enums\CommandSource;
use Domain\Idempotency\Enums\CommandStatus;
use Domain\Idempotency\Exceptions\DuplicateCommandException;
use Domain\Idempotency\Repositories\CommandInboxWriteRepositoryInterface;
use Domain\Occurrence\Exceptions\OccurrenceNotFoundException;
use Domain\Occurrence\Repositories\OccurrenceRepositoryInterface;
use Domain\Outbox\Repositories\OutboxWriteRepositoryInterface;
use Domain\Shared\ValueObjects\Uuid;

readonly class DispatchService
{
    public function __construct(
        private CommandInboxWriteRepositoryInterface $commandInboxWriteRepository,
        private OutboxWriteRepositoryInterface $outboxWriteRepository,
        private OutboxEventResolver $outboxEventResolver,
        private OccurrenceRepositoryInterface $occurrenceRepository,
    ) {
    }

    public function createDispatch(
        string $occurrenceId,
        string $resourceCode,
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
            type: 'create_dispatch',
            scopeKey: $occurrenceId,
            payload: [
                'occurrenceId' => $occurrenceId,
                'resourceCode' => $resourceCode,
            ],
        );

        if ($registration->shouldDispatch && $registration->isNew) {
            $this->registerOutboxEvent('create_dispatch', $registration->commandId);

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

    public function closeDispatch(
        string $dispatchId,
        string $idempotencyKey,
        CommandSource $source = CommandSource::INTERNAL
    ): AcceptedCommandResult {
        $registration = $this->commandInboxWriteRepository->registerOrGet(
            idempotencyKey: $idempotencyKey,
            source: $source->value,
            type: 'close_dispatch',
            scopeKey: $dispatchId,
            payload: ['dispatchId' => $dispatchId],
        );

        if ($registration->shouldDispatch && $registration->isNew) {
            $this->registerOutboxEvent('close_dispatch', $registration->commandId);

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

    public function updateDispatchStatus(
        string $dispatchId,
        string $statusCode,
        string $idempotencyKey,
        CommandSource $source = CommandSource::INTERNAL
    ): AcceptedCommandResult {
        $registration = $this->commandInboxWriteRepository->registerOrGet(
            idempotencyKey: $idempotencyKey,
            source: $source->value,
            type: 'update_dispatch_status',
            scopeKey: $dispatchId,
            payload: [
                'dispatchId' => $dispatchId,
                'statusCode' => $statusCode,
            ],
        );

        if ($registration->shouldDispatch && $registration->isNew) {
            $this->registerOutboxEvent('update_dispatch_status', $registration->commandId);

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
