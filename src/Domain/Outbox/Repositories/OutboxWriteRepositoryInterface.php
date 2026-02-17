<?php

declare(strict_types=1);

namespace Domain\Outbox\Repositories;

interface OutboxWriteRepositoryInterface
{
    public function addPendingEvent(
        string $aggregateType,
        string $aggregateId,
        string $eventType,
    ): void;
}


