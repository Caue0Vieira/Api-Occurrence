<?php

declare(strict_types=1);

namespace Application\UseCases\CreateOccurrence;

use App\Jobs\ProcessCreateOccurrenceJob;
use Application\DTOs\AcceptedCommandResult;
use Domain\Idempotency\Enums\CommandStatus;
use Domain\Idempotency\Repositories\CommandInboxWriteRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class CreateOccurrenceHandler
{
    public function __construct(
        private CommandInboxWriteRepositoryInterface $commandInboxWriteRepository,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handle(CreateOccurrenceCommand $command): AcceptedCommandResult
    {
        Log::info('[Handler] CreateOccurrenceHandler started', [
            'externalId' => $command->externalId,
            'idempotencyKey' => $command->idempotencyKey,
        ]);

        try {
            $registration = $this->commandInboxWriteRepository->registerOrGet(
                idempotencyKey: $command->idempotencyKey,
                source: $command->source,
                type: 'create_occurrence',
                scopeKey: $command->externalId,
                payload: $command->toPayload(),
            );

            if (!$registration->shouldDispatch) {
                return new AcceptedCommandResult(
                    commandId: $registration->commandId,
                    status: $registration->status
                );
            }

            Log::info('[Handler] Dispatching job to queue', [
                'idempotencyKey' => $command->idempotencyKey,
                'queue_connection' => config('queue.default'),
                'commandId' => $registration->commandId,
            ]);

            ProcessCreateOccurrenceJob::dispatch(
                idempotencyKey: $command->idempotencyKey,
                source: $command->source,
                type: 'create_occurrence',
                scopeKey: $command->externalId,
                payload: $command->toPayload(),
                externalId: $command->externalId,
                occurrenceType: $command->type,
                description: $command->description,
                reportedAt: $command->reportedAt,
                commandId: $registration->commandId,
            );
            $this->commandInboxWriteRepository->markAsEnqueued($registration->commandId);

            Log::info('[Handler] Job dispatched successfully', [
                'idempotencyKey' => $command->idempotencyKey,
            ]);

            return new AcceptedCommandResult(
                commandId: $registration->commandId,
                status: CommandStatus::ENQUEUED->value
            );
        } catch (Throwable $e) {
            Log::error('[Handler] Error in CreateOccurrenceHandler', [
                'externalId' => $command->externalId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
