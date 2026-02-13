<?php

declare(strict_types=1);

namespace Domain\Idempotency\Services;

use Application\DTOs\CommandStatusResult;
use Application\UseCases\GetCommandStatus\GetCommandStatusHandler;
use Application\UseCases\GetCommandStatus\GetCommandStatusQuery;

final readonly class CommandService
{
    public function __construct(
        private GetCommandStatusHandler $getCommandStatusHandler,
    ) {
    }

    public function getCommandStatus(string $commandId): CommandStatusResult
    {
        $query = new GetCommandStatusQuery(commandId: $commandId);

        return $this->getCommandStatusHandler->handle($query);
    }
}

