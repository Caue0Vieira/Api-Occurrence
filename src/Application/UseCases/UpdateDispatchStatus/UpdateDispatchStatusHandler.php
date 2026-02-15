<?php

declare(strict_types=1);

namespace Application\UseCases\UpdateDispatchStatus;

use App\Jobs\ProcessUpdateDispatchStatusJob;
use Application\DTOs\AcceptedCommandResult;
use Domain\Idempotency\Repositories\CommandInboxWriteRepositoryInterface;

readonly class UpdateDispatchStatusHandler
{
    public function __construct(
        private CommandInboxWriteRepositoryInterface $commandInboxWriteRepository,
    ) {
    }

    public function handle(UpdateDispatchStatusCommand $command): AcceptedCommandResult
    {
        $registration = $this->commandInboxWriteRepository->registerOrGet(
            idempotencyKey: $command->idempotencyKey,
            source: $command->source,
            type: 'update_dispatch_status',
            scopeKey: $command->dispatchId,
            payload: $command->toPayload(),
        );

        if ($registration->shouldDispatch) {
            ProcessUpdateDispatchStatusJob::dispatch(
                idempotencyKey: $command->idempotencyKey,
                source: $command->source,
                type: 'update_dispatch_status',
                scopeKey: $command->dispatchId,
                payload: $command->toPayload(),
                dispatchId: $command->dispatchId,
                statusCode: $command->statusCode,
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

