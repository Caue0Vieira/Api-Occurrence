<?php

declare(strict_types=1);

namespace Application\UseCases\CreateDispatch;

use App\Jobs\ProcessCreateDispatchJob;
use Application\DTOs\AcceptedCommandResult;
use Domain\Idempotency\Repositories\CommandInboxWriteRepositoryInterface;

final readonly class CreateDispatchHandler
{
    public function __construct(
        private CommandInboxWriteRepositoryInterface $commandInboxWriteRepository,
    ) {
    }

    public function handle(CreateDispatchCommand $command): AcceptedCommandResult
    {
        $registration = $this->commandInboxWriteRepository->registerOrGet(
            idempotencyKey: $command->idempotencyKey,
            source: $command->source,
            type: 'create_dispatch',
            scopeKey: $command->occurrenceId,
            payload: $command->toPayload(),
        );

        if ($registration->shouldDispatch) {
            ProcessCreateDispatchJob::dispatch(
                idempotencyKey: $command->idempotencyKey,
                source: $command->source,
                type: 'create_dispatch',
                scopeKey: $command->occurrenceId,
                payload: $command->toPayload(),
                occurrenceId: $command->occurrenceId,
                resourceCode: $command->resourceCode,
                commandId: $registration->commandId,
            );

            $this->commandInboxWriteRepository->markAsEnqueued($registration->commandId);

            return new AcceptedCommandResult(
                commandId: $registration->commandId,
                status: \Domain\Idempotency\Enums\CommandStatus::ENQUEUED->value
            );
        }

        return new AcceptedCommandResult(
            commandId: $registration->commandId,
            status: $registration->status
        );
    }
}

