<?php

declare(strict_types=1);

namespace Application\UseCases\CloseDispatch;

use App\Jobs\ProcessCloseDispatchJob;
use Application\DTOs\AcceptedCommandResult;
use Domain\Idempotency\Repositories\CommandInboxWriteRepositoryInterface;

final readonly class CloseDispatchHandler
{
    public function __construct(
        private CommandInboxWriteRepositoryInterface $commandInboxWriteRepository,
    ) {
    }

    public function handle(CloseDispatchCommand $command): AcceptedCommandResult
    {
        $registration = $this->commandInboxWriteRepository->registerOrGet(
            idempotencyKey: $command->idempotencyKey,
            source: $command->source,
            type: 'close_dispatch',
            scopeKey: $command->dispatchId,
            payload: $command->toPayload(),
        );

        if ($registration->shouldDispatch) {
            ProcessCloseDispatchJob::dispatch(
                idempotencyKey: $command->idempotencyKey,
                source: $command->source,
                type: 'close_dispatch',
                scopeKey: $command->dispatchId,
                payload: $command->toPayload(),
                dispatchId: $command->dispatchId,
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

