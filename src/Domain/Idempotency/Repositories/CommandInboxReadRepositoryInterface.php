<?php

declare(strict_types=1);

namespace Domain\Idempotency\Repositories;

use Application\DTOs\CommandStatusResult;

interface CommandInboxReadRepositoryInterface
{
    public function findByCommandId(string $commandId): ?CommandStatusResult;

    public function existsByTypeAndExternalIdWithDifferentIdempotencyKey(string $type, string $externalId, string $idempotencyKey): bool;
}

