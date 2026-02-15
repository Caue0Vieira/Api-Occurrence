<?php

declare(strict_types=1);

namespace Application\UseCases\StartOccurrence;

use App\Jobs\ProcessStartOccurrenceJob;
use Application\DTOs\AcceptedCommandResult;
use Domain\Idempotency\Repositories\CommandInboxWriteRepositoryInterface;

final readonly class StartOccurrenceHandler
{
    public function __construct(
        private CommandInboxWriteRepositoryInterface $commandInboxWriteRepository,
    ) {
    }

    public function handle(StartOccurrenceCommand $command): AcceptedCommandResult
    {
        $registration = $this->commandInboxWriteRepository->registerOrGet(
            idempotencyKey: $command->idempotencyKey,
            source: $command->source,
            type: 'start_occurrence',
            scopeKey: $command->occurrenceId,
            payload: $command->toPayload(),
        );

        if ($registration->shouldDispatch) {
            ProcessStartOccurrenceJob::dispatch(
                idempotencyKey: $command->idempotencyKey,
                source: $command->source,
                type: 'start_occurrence',
                scopeKey: $command->occurrenceId,
                payload: $command->toPayload(),
                occurrenceId: $command->occurrenceId,
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

