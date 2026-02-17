<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Repositories;

use Application\DTOs\CommandRegistrationResult;
use Carbon\CarbonInterface;
use Domain\Idempotency\Enums\CommandStatus;
use Domain\Idempotency\Exceptions\IdempotencyConflictException;
use Domain\Idempotency\Repositories\CommandInboxWriteRepositoryInterface;
use Domain\Shared\ValueObjects\Uuid;
use Infrastructure\Support\CommandNormalizationHelper;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use JsonException;

class CommandInboxWriteRepository implements CommandInboxWriteRepositoryInterface
{
    /**
     * @throws JsonException
     */
    public function registerOrGet(
        string $idempotencyKey,
        string $source,
        string $type,
        string $scopeKey,
        array $payload
    ): CommandRegistrationResult {
        $normalizedKey = CommandNormalizationHelper::normalizeIdempotencyKey($idempotencyKey);
        $normalizedPayload = CommandNormalizationHelper::normalizePayload($payload);
        $payloadHash = $this->calculatePayloadHash($payload);
        $expiresAt = $this->getExpirationDate();
        return DB::transaction(function () use (
            $normalizedKey,
            $source,
            $type,
            $scopeKey,
            $normalizedPayload,
            $payloadHash,
            $expiresAt
        ): CommandRegistrationResult {
            $existing = $this->findExistingCommand(
                idempotencyKey: $normalizedKey,
                type: $type,
                scopeKey: $scopeKey
            );

            if ($existing !== null) {
                return $this->buildResultFromExisting($existing, $normalizedKey, $scopeKey, $payloadHash);
            }

            try {
                $commandId = $this->insertNewCommand(
                    idempotencyKey: $normalizedKey,
                    source: $source,
                    type: $type,
                    scopeKey: $scopeKey,
                    payload: $normalizedPayload,
                    payloadHash: $payloadHash,
                    expiresAt: $expiresAt
                );

                return new CommandRegistrationResult(
                    commandId: $commandId,
                    status: CommandStatus::RECEIVED->value,
                    shouldDispatch: true,
                    isNew: true
                );
            } catch (QueryException $e) {
                if (($e->errorInfo[0] ?? null) !== '23505') {
                    throw $e;
                }

                $existing = $this->findExistingCommand(
                    idempotencyKey: $normalizedKey,
                    type: $type,
                    scopeKey: $scopeKey
                );

                if ($existing === null) {
                    throw $e;
                }

                return $this->buildResultFromExisting($existing, $normalizedKey, $scopeKey, $payloadHash);
            }
        });
    }

    /**
     * @throws JsonException
     */
    private function insertNewCommand(
        string $idempotencyKey,
        string $source,
        string $type,
        string $scopeKey,
        array $payload,
        string $payloadHash,
        CarbonInterface $expiresAt
    ): string {
        $commandId = Uuid::generate()->toString();

        DB::table('command_inbox')->insert([
            'id' => $commandId,
            'idempotency_key' => $idempotencyKey,
            'source' => $source,
            'type' => $type,
            'scope_key' => $scopeKey,
            'payload_hash' => $payloadHash,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'status' => CommandStatus::RECEIVED->value,
            'processed_at' => null,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $commandId;
    }

    private function findExistingCommand(string $idempotencyKey, string $type, string $scopeKey): ?object
    {
        return DB::table('command_inbox')
            ->select(['id', 'status', 'payload_hash'])
            ->where('idempotency_key', $idempotencyKey)
            ->where('type', $type)
            ->where('scope_key', $scopeKey)
            ->first();
    }

    private function buildResultFromExisting(
        object $existing,
        string $normalizedKey,
        string $scopeKey,
        string $payloadHash
    ): CommandRegistrationResult {
        if ((string) $existing->payload_hash !== $payloadHash) {
            throw IdempotencyConflictException::withPayloadMismatch($normalizedKey, $scopeKey);
        }

        $status = CommandStatus::fromString((string) $existing->status);

        return new CommandRegistrationResult(
            commandId: (string) $existing->id,
            status: $status->value,
            shouldDispatch: $status->shouldDispatch(),
            isNew: false
        );
    }

    /**
     * @throws JsonException
     */
    private function calculatePayloadHash(array $normalizedPayload): string
    {
        return hash('sha256', json_encode($normalizedPayload, JSON_THROW_ON_ERROR));
    }

    private function getExpirationDate(): CarbonInterface
    {
        $ttlInSeconds = (int) config('api.idempotency.ttl', 86400);
        return now()->addSeconds($ttlInSeconds);
    }
}
