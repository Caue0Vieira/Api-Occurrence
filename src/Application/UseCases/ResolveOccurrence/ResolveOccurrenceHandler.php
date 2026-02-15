<?php

declare(strict_types=1);

namespace Application\UseCases\ResolveOccurrence;

use App\Jobs\ProcessResolveOccurrenceJob;
use Application\DTOs\AcceptedCommandResult;
use Domain\Idempotency\Repositories\CommandInboxWriteRepositoryInterface;

final readonly class ResolveOccurrenceHandler
{
    public function __construct(
        private CommandInboxWriteRepositoryInterface $commandInboxWriteRepository,
    ) {
    }

    public function handle(ResolveOccurrenceCommand $command): AcceptedCommandResult
    {
        $registration = $this->commandInboxWriteRepository->registerOrGet(
            idempotencyKey: $command->idempotencyKey,
            source: $command->source,
            type: 'resolve_occurrence',
            scopeKey: $command->occurrenceId,
            payload: $command->toPayload(),
        );

        if ($registration->shouldDispatch) {
            ProcessResolveOccurrenceJob::dispatch(
                idempotencyKey: $command->idempotencyKey,
                source: $command->source,
                type: 'resolve_occurrence',
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

