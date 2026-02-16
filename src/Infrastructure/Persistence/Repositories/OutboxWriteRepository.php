<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Repositories;

use Domain\Outbox\Repositories\OutboxWriteRepositoryInterface;
use Domain\Shared\ValueObjects\Uuid;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class OutboxWriteRepository implements OutboxWriteRepositoryInterface
{
    public function addPendingEvent(
        string $aggregateType,
        string $aggregateId,
        string $eventType,
    ): void {
        // Verificar se já existe um evento com o mesmo aggregate_id
        $existing = DB::table('outbox')
            ->where('aggregate_id', $aggregateId)
            ->first();

        if ($existing !== null) {
            // Se o evento existente tem tipo diferente, atualizar para o tipo correto
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

        // Se não existe, inserir normalmente
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
        } catch (QueryException $e) {
            // Se falhar por constraint única, atualizar o registro existente
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
