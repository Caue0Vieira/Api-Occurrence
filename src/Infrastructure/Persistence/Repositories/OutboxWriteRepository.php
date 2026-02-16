<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Repositories;

use Domain\Outbox\Repositories\OutboxWriteRepositoryInterface;
use Domain\Shared\ValueObjects\Uuid;
use Illuminate\Support\Facades\DB;

class OutboxWriteRepository implements OutboxWriteRepositoryInterface
{
    public function addPendingEvent(
        string $aggregateType,
        string $aggregateId,
        string $eventType,
    ): void {
        $existing = DB::table('outbox')
            ->where('aggregate_id', $aggregateId)
            ->first();

        if ($existing !== null) {
            if ($existing->event_type !== $eventType || $existing->aggregate_type !== $aggregateType) {
                DB::table('outbox')
                    ->where('id', $existing->id)
                    ->update([
                        'aggregate_type' => $aggregateType,
                        'event_type' => $eventType,
                        'status' => 'PENDING',
                        'sent_at' => null,
                        'updated_at' => now(),
                    ]);
                return;
            }
            return;
        }

        try {
            DB::table('outbox')->insert([
                'id' => Uuid::generate()->toString(),
                'aggregate_type' => $aggregateType,
                'aggregate_id' => $aggregateId,
                'event_type' => $eventType,
                'status' => 'PENDING',
                'created_at' => now(),
                'sent_at' => null,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'duplicate')) {
                DB::table('outbox')
                    ->where('aggregate_id', $aggregateId)
                    ->update([
                        'aggregate_type' => $aggregateType,
                        'event_type' => $eventType,
                        'status' => 'PENDING',
                        'sent_at' => null,
                        'updated_at' => now(),
                    ]);
            } else {
                throw $e;
            }
        }
    }
}