<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Repositories;

use Application\DTOs\CommandRegistrationResult;
use Domain\Idempotency\Exceptions\IdempotencyConflictException;
use Domain\Idempotency\Repositories\CommandInboxWriteRepositoryInterface;
use Domain\Shared\ValueObjects\Uuid;
use Illuminate\Support\Facades\DB;
use JsonException;

class CommandInboxWriteRepository implements CommandInboxWriteRepositoryInterface
{
    /**
     * @throws JsonException
     */
    public function registerOrGet(string $idempotencyKey, string $source, string $type, string $scopeKey, array $payload): CommandRegistrationResult
    {
        $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        $ttlInSeconds = (int)config('api.idempotency.ttl', 86400);
        $expiresAt = now()->addSeconds($ttlInSeconds);
        $normalizedIdempotencyKey = $this->normalizeIdempotencyKey($idempotencyKey);

        return DB::transaction(function () use ($normalizedIdempotencyKey, $source, $type, $scopeKey, $payload, $payloadHash, $expiresAt): CommandRegistrationResult {
            DB::table('command_inbox')
                ->where('idempotency_key', $normalizedIdempotencyKey)
                ->where('type', $type)
                ->where('scope_key', $scopeKey)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->delete();

            $existing = DB::table('command_inbox')
                ->where('idempotency_key', $normalizedIdempotencyKey)
                ->where('type', $type)
                ->where('scope_key', $scopeKey)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if ($existing->payload_hash !== $payloadHash) {
                    throw IdempotencyConflictException::withPayloadMismatch($normalizedIdempotencyKey, $scopeKey);
                }

                $shouldDispatch = $existing->status === 'pending' || $existing->status === 'failed';

                return new CommandRegistrationResult(
                    commandId: $existing->id,
                    shouldDispatch: $shouldDispatch
                );
            }

            $commandId = Uuid::generate()->toString();

            DB::table('command_inbox')->insert([
                'id' => $commandId,
                'idempotency_key' => $normalizedIdempotencyKey,
                'source' => $source,
                'type' => $type,
                'scope_key' => $scopeKey,
                'payload_hash' => $payloadHash,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                'status' => 'pending',
                'processed_at' => null,
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return new CommandRegistrationResult(
                commandId: $commandId,
                shouldDispatch: true
            );
        });
    }

    private function normalizeIdempotencyKey(string $idempotencyKey): string
    {
        $trimmed = trim($idempotencyKey);

        if ($trimmed !== '') {
            return $trimmed;
        }

        // Mantem rastreabilidade de comandos internos sem depender de header.
        return 'auto-' . Uuid::generate()->toString();
    }
}

