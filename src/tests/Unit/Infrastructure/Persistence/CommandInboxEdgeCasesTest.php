<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence;

use Application\DTOs\CommandRegistrationResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Persistence\Repositories\CommandInboxWriteRepository;
use Tests\TestCase;
use Tests\Traits\CommandInboxTestHelpers;

class CommandInboxEdgeCasesTest extends TestCase
{
    use RefreshDatabase;
    use CommandInboxTestHelpers;

    public function test_expired_command_is_deleted_and_new_one_is_created(): void
    {
        $repository = app(CommandInboxWriteRepository::class);

        $expiredCommandId = $this->createCommandInbox([
            'id' => '018f0e2b-f278-7be1-88f9-cf0d43edc901',
            'idempotency_key' => 'idem-expired-001',
            'scope_key' => 'ext-expired-1',
            'payload' => ['externalId' => 'ext-expired-1'],
            'expires_at' => now()->subHour(),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $result = $repository->registerOrGet(
            idempotencyKey: 'idem-expired-001',
            source: 'external_system',
            type: 'create_occurrence',
            scopeKey: 'ext-expired-1',
            payload: ['externalId' => 'ext-expired-1', 'type' => 'incendio_urbano'],
        );

        $this->assertNotSame($expiredCommandId, $result->commandId);
        $this->assertTrue($result->shouldDispatch);
        $this->assertDatabaseMissing('command_inbox', ['id' => $expiredCommandId]);
        $this->assertDatabaseHas('command_inbox', ['id' => $result->commandId]);
    }

    public function test_already_processed_command_returns_existing_without_dispatch(): void
    {
        $repository = app(CommandInboxWriteRepository::class);

        $payload = ['externalId' => 'ext-processed-1', 'type' => 'incendio_urbano'];
        $commandId = $this->createCommandInbox([
            'id' => '018f0e2b-f278-7be1-88f9-cf0d43edc902',
            'idempotency_key' => 'idem-processed-001',
            'scope_key' => 'ext-processed-1',
            'payload' => $payload,
            'status' => 'processed',
            'result' => ['occurrenceId' => '018f0e2b-f278-7be1-88f9-cf0d43edc903'],
            'processed_at' => now()->subMinute(),
            'created_at' => now()->subHour(),
            'updated_at' => now()->subMinute(),
        ]);

        $result = $repository->registerOrGet(
            idempotencyKey: 'idem-processed-001',
            source: 'external_system',
            type: 'create_occurrence',
            scopeKey: 'ext-processed-1',
            payload: $payload,
        );

        $this->assertSame($commandId, $result->commandId);
        $this->assertFalse($result->shouldDispatch);
    }

    public function test_failed_command_allows_retry_with_same_idempotency_key(): void
    {
        $repository = app(CommandInboxWriteRepository::class);

        $payload = ['externalId' => 'ext-failed-1', 'type' => 'incendio_urbano'];
        $commandId = $this->createCommandInbox([
            'id' => '018f0e2b-f278-7be1-88f9-cf0d43edc904',
            'idempotency_key' => 'idem-failed-001',
            'scope_key' => 'ext-failed-1',
            'payload' => $payload,
            'status' => 'failed',
            'error_message' => 'Previous processing error',
            'processed_at' => now()->subMinute(),
            'created_at' => now()->subHour(),
            'updated_at' => now()->subMinute(),
        ]);

        $result = $repository->registerOrGet(
            idempotencyKey: 'idem-failed-001',
            source: 'external_system',
            type: 'create_occurrence',
            scopeKey: 'ext-failed-1',
            payload: $payload,
        );

        $this->assertSame($commandId, $result->commandId);
        $this->assertTrue($result->shouldDispatch, 'Failed commands should allow retry');
    }

    public function test_command_with_different_payload_throws_conflict_exception(): void
    {
        $repository = app(CommandInboxWriteRepository::class);

        $firstPayload = ['externalId' => 'ext-conflict-1', 'type' => 'incendio_urbano'];
        $commandId = $this->createCommandInbox([
            'id' => '018f0e2b-f278-7be1-88f9-cf0d43edc905',
            'idempotency_key' => 'idem-conflict-001',
            'scope_key' => 'ext-conflict-1',
            'payload' => $firstPayload,
        ]);

        $differentPayload = ['externalId' => 'ext-conflict-1', 'type' => 'resgate_veicular'];

        $this->expectException(\Domain\Idempotency\Exceptions\IdempotencyConflictException::class);
        $this->expectExceptionMessage("Idempotency-Key 'idem-conflict-001' já foi utilizada com payload diferente");

        $repository->registerOrGet(
            idempotencyKey: 'idem-conflict-001',
            source: 'external_system',
            type: 'create_occurrence',
            scopeKey: 'ext-conflict-1',
            payload: $differentPayload,
        );
    }
}

