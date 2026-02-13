<?php

declare(strict_types=1);

namespace Domain\Dispatch\Service;

use Application\DTOs\AcceptedCommandResult;
use Application\UseCases\CloseDispatch\CloseDispatchCommand;
use Application\UseCases\CloseDispatch\CloseDispatchHandler;
use Application\UseCases\CreateDispatch\CreateDispatchCommand;
use Application\UseCases\CreateDispatch\CreateDispatchHandler;
use Application\UseCases\UpdateDispatchStatus\UpdateDispatchStatusCommand;
use Application\UseCases\UpdateDispatchStatus\UpdateDispatchStatusHandler;

readonly class DispatchService
{
    public function __construct(
        private CreateDispatchHandler $createDispatchHandler,
        private CloseDispatchHandler $closeDispatchHandler,
        private UpdateDispatchStatusHandler $updateDispatchStatusHandler,
    ) {
    }

    public function createDispatch(
        string $occurrenceId,
        string $resourceCode,
        string $idempotencyKey,
        string $source = 'internal_system'
    ): AcceptedCommandResult {
        $command = new CreateDispatchCommand(
            occurrenceId: $occurrenceId,
            resourceCode: $resourceCode,
            idempotencyKey: $idempotencyKey,
            source: $source
        );

        return $this->createDispatchHandler->handle($command);
    }

    public function closeDispatch(
        string $dispatchId,
        string $idempotencyKey,
        string $source = 'internal_system'
    ): AcceptedCommandResult {
        $command = new CloseDispatchCommand(
            dispatchId: $dispatchId,
            idempotencyKey: $idempotencyKey,
            source: $source
        );

        return $this->closeDispatchHandler->handle($command);
    }

    public function updateDispatchStatus(
        string $dispatchId,
        string $statusCode,
        string $idempotencyKey = '',
        string $source = 'internal_system'
    ): AcceptedCommandResult {
        $command = new UpdateDispatchStatusCommand(
            dispatchId: $dispatchId,
            statusCode: $statusCode,
            source: $source,
            idempotencyKey: $idempotencyKey
        );

        return $this->updateDispatchStatusHandler->handle($command);
    }
}
