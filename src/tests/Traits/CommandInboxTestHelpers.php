<?php

declare(strict_types=1);

namespace Tests\Traits;

use Domain\Shared\ValueObjects\Uuid;
use Illuminate\Support\Facades\DB;

trait CommandInboxTestHelpers
{
    /**
     * Cria um comando no command_inbox com valores padrão
     */
    protected function createCommandInbox(array $attributes = []): string
    {
        $defaults = [
            'id' => Uuid::generate()->toString(),
            'idempotency_key' => 'idem-test-' . uniqid(),
            'source' => 'external_system',
            'type' => 'create_occurrence',
            'scope_key' => 'ext-test-1',
            'payload' => ['externalId' => 'ext-test-1'],
            'status' => 'pending',
            'result' => null,
            'error_message' => null,
            'processed_at' => null,
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $merged = array_merge($defaults, $attributes);

        // Calcular payload_hash se payload for array
        if (is_array($merged['payload'])) {
            $merged['payload_hash'] = hash('sha256', json_encode($merged['payload'], JSON_THROW_ON_ERROR));
            $merged['payload'] = json_encode($merged['payload'], JSON_THROW_ON_ERROR);
        } elseif (!isset($merged['payload_hash'])) {
            // Se payload já for string JSON, calcular hash
            $merged['payload_hash'] = hash('sha256', $merged['payload']);
        }

        // Converter result para JSON se for array
        if (isset($merged['result']) && is_array($merged['result'])) {
            $merged['result'] = json_encode($merged['result'], JSON_THROW_ON_ERROR);
        }

        DB::table('command_inbox')->insert($merged);

        return $merged['id'];
    }

    /**
     * Retorna headers para requisições com API Key externa
     */
    protected function withExternalApiHeaders(string $idempotencyKey): array
    {
        return [
            'X-API-Key' => config('api.keys.external'),
            'Idempotency-Key' => $idempotencyKey,
        ];
    }

    /**
     * Retorna headers para requisições com API Key interna
     */
    protected function withInternalApiHeaders(string $idempotencyKey): array
    {
        return [
            'X-API-Key' => config('api.keys.internal'),
            'Idempotency-Key' => $idempotencyKey,
        ];
    }

    /**
     * Cria payload padrão para criação de ocorrência
     */
    protected function createOccurrencePayload(array $overrides = []): array
    {
        return array_merge([
            'externalId' => 'external-' . uniqid(),
            'type' => 'incendio_urbano',
            'description' => 'Test occurrence description',
            'reportedAt' => now()->format('Y-m-d\TH:i:sP'),
        ], $overrides);
    }

    /**
     * Configura tabelas relacionadas (occurrence_types e occurrence_status)
     */
    protected function setupOccurrenceTables(string $typeCode = 'incendio_urbano', string $statusCode = 'reported'): void
    {
        if (!DB::table('occurrence_types')->where('code', $typeCode)->exists()) {
            DB::table('occurrence_types')->insert([
                'id' => Uuid::generate()->toString(),
                'code' => $typeCode,
                'name' => ucfirst(str_replace('_', ' ', $typeCode)),
                'category' => 'emergency',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!DB::table('occurrence_status')->where('code', $statusCode)->exists()) {
            DB::table('occurrence_status')->insert([
                'id' => Uuid::generate()->toString(),
                'code' => $statusCode,
                'name' => ucfirst($statusCode),
                'is_final' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Cria uma ocorrência no banco de dados
     */
    protected function createOccurrence(string $id, string $externalId, string $typeCode = 'incendio_urbano', string $statusCode = 'reported'): void
    {
        $this->setupOccurrenceTables($typeCode, $statusCode);

        DB::table('occurrences')->insert([
            'id' => $id,
            'external_id' => $externalId,
            'type_code' => $typeCode,
            'status_code' => $statusCode,
            'description' => 'Test occurrence',
            'reported_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Calcula hash do payload
     */
    protected function calculatePayloadHash(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}

