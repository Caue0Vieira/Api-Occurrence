<?php

declare(strict_types=1);

namespace Application\UseCases\GetCommandStatus;

use Application\DTOs\CommandStatusResult;
use Domain\Idempotency\Exceptions\CommandNotFoundException;
use Domain\Idempotency\Repositories\CommandInboxReadRepositoryInterface;

final readonly class GetCommandStatusHandler
{
    public function __construct(
        private CommandInboxReadRepositoryInterface $commandInboxReadRepository,
    ) {
    }

    public function handle(GetCommandStatusQuery $query): CommandStatusResult
    {
        $commandStatus = $this->commandInboxReadRepository->findByCommandId($query->commandId);

        if ($commandStatus === null) {
            throw CommandNotFoundException::withId($query->commandId);
        }

        return $commandStatus;
    }
}

