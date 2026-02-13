<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CommandInboxTestHelpers;

class CommandApiTest extends TestCase
{
    use RefreshDatabase;
    use CommandInboxTestHelpers;

    public function test_get_command_status_returns_pending_command(): void
    {
        $commandId = $this->createCommandInbox([
            'id' => '018f0e2b-f278-7be1-88f9-cf0d43edc001',
            'idempotency_key' => 'idem-command-pending',
            'source' => 'internal_system',
            'type' => 'start_occurrence',
            'scope_key' => 'occ-1001',
            'payload' => ['occurrenceId' => 'occ-1001'],
            'status' => 'pending',
        ]);

        $response = $this
            ->withHeader('X-API-Key', config('api.keys.internal'))
            ->get("/api/commands/{$commandId}");

        $response->assertStatus(200);
        $response->assertJson([
            'commandId' => $commandId,
            'status' => 'pending',
            'result' => null,
            'errorMessage' => null,
            'processedAt' => null,
        ]);
    }

    public function test_get_command_status_returns_processed_command_with_result(): void
    {
        $commandId = $this->createCommandInbox([
            'id' => '018f0e2b-f278-7be1-88f9-cf0d43edc002',
            'idempotency_key' => 'idem-command-processed',
            'source' => 'internal_system',
            'type' => 'create_occurrence',
            'scope_key' => 'ext-2002',
            'payload' => ['externalId' => 'ext-2002'],
            'status' => 'processed',
            'result' => ['occurrenceId' => '018f0e2b-f278-7be1-88f9-cf0d43edc003'],
            'processed_at' => now(),
        ]);

        $response = $this
            ->withHeader('X-API-Key', config('api.keys.internal'))
            ->get("/api/commands/{$commandId}");

        $response->assertStatus(200);
        $response->assertJson([
            'commandId' => $commandId,
            'status' => 'processed',
            'result' => [
                'occurrenceId' => '018f0e2b-f278-7be1-88f9-cf0d43edc003',
            ],
            'errorMessage' => null,
        ]);
    }

    public function test_get_command_status_returns_404_when_command_does_not_exist(): void
    {
        $response = $this
            ->withHeader('X-API-Key', config('api.keys.internal'))
            ->get('/api/commands/018f0e2b-f278-7be1-88f9-cf0d43edc099');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Not found',
        ]);
    }
}

