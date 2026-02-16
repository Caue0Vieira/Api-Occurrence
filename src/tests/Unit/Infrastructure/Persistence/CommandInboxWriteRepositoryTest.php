<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence;

use Domain\Idempotency\Enums\CommandSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Persistence\Repositories\CommandInboxWriteRepository;
use Tests\TestCase;
use Tests\Traits\CommandInboxTestHelpers;

class CommandInboxWriteRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use CommandInboxTestHelpers;

    public function test_simulated_concurrent_registration_keeps_single_command_record(): void
    {
        $repository = app(CommandInboxWriteRepository::class);

        $first = $repository->registerOrGet(
            idempotencyKey: 'idem-concurrency-api-001',
            source: CommandSource::EXTERNAL->value,
            type: 'create_occurrence',
            scopeKey: 'external-concurrent-1',
            payload: [
                'externalId' => 'external-concurrent-1',
                'type' => 'incendio_urbano',
                'description' => 'Primeiro registro de concorrencia simulada',
                'reportedAt' => '2026-02-12T13:00:00-03:00',
            ],
        );

        $second = $repository->registerOrGet(
            idempotencyKey: 'idem-concurrency-api-001',
            source: CommandSource::EXTERNAL->value,
            type: 'create_occurrence',
            scopeKey: 'external-concurrent-1',
            payload: [
                'externalId' => 'external-concurrent-1',
                'type' => 'incendio_urbano',
                'description' => 'Primeiro registro de concorrencia simulada',
                'reportedAt' => '2026-02-12T13:00:00-03:00',
            ],
        );

        $this->assertSame($first->commandId, $second->commandId);
        $this->assertDatabaseCount('command_inbox', 1);
        $this->assertDatabaseHas('command_inbox', [
            'id' => $first->commandId,
            'type' => 'create_occurrence',
            'scope_key' => 'external-concurrent-1',
        ]);
    }
}

