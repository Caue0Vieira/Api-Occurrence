<?php

declare(strict_types=1);

namespace Domain\Idempotency\Repositories;

use Application\DTOs\CommandRegistrationResult;

interface CommandInboxWriteRepositoryInterface
{
    public function registerOrGet(
        string $idempotencyKey,
        string $source,
        string $type,
        string $scopeKey,
        array $payload,
    ): CommandRegistrationResult;

    public function markAsEnqueued(string $commandId): void;
}

