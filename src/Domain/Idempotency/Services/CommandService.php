<?php

declare(strict_types=1);

namespace Domain\Idempotency\Services;

use Application\DTOs\CommandStatusResult;
use Domain\Idempotency\Exceptions\CommandNotFoundException;
use Domain\Idempotency\Repositories\CommandInboxReadRepositoryInterface;

readonly class CommandService
{
    public function __construct(
        private CommandInboxReadRepositoryInterface $commandInboxReadRepository,
    ) {
    }

    public function getCommandStatus(string $commandId): CommandStatusResult
    {
        $commandStatus = $this->commandInboxReadRepository->findByCommandId($commandId);

        if ($commandStatus === null) {
            throw CommandNotFoundException::withId($commandId);
        }

        return $commandStatus;
    }
}