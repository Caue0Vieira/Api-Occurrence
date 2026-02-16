<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Repositories;

use Domain\Outbox\Repositories\OutboxWriteRepositoryInterface;
use Domain\Shared\ValueObjects\Uuid;
use Illuminate\Support\Facades\DB;

final class OutboxWriteRepository implements OutboxWriteRepositoryInterface
{
    public function addPendingEvent(
        string $aggregateType,
        string $aggregateId,
        string $eventType,
    ): void {
        DB::table('outbox')->insertOrIgnore([
            'id' => Uuid::generate()->toString(),
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_type' => $eventType,
            'status' => 'PENDING',
            'created_at' => now(),
            'sent_at' => null,
        ]);
    }
}


