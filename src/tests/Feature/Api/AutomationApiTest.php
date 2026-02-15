<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Jobs\ProcessCreateOccurrenceJob;
use App\Jobs\ProcessStartOccurrenceJob;
use App\Jobs\ProcessUpdateDispatchStatusJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CommandInboxTestHelpers;

class AutomationApiTest extends TestCase
{
    use RefreshDatabase;
    use CommandInboxTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        config(['api.rate_limit.enabled' => false]);
    }

    public function test_external_integration_keeps_idempotency_for_same_payload(): void
    {
        Queue::fake();

        $headers = $this->withExternalApiHeaders('idem-external-00001');

        $payload = $this->createOccurrencePayload([
            'externalId' => 'external-123',
            'description' => 'Incendio em galpao industrial com fumaca intensa',
            'reportedAt' => '2026-02-12T10:00:00-03:00',
        ]);

        $first = $this->withHeaders($headers)->postJson('/api/integrations/occurrences', $payload);
        $second = $this->withHeaders($headers)->postJson('/api/integrations/occurrences', $payload);

        $first->assertStatus(202);
        $second->assertStatus(202);

        $firstCommandId = (string) $first->json('command_id');
        $secondCommandId = (string) $second->json('command_id');

        $this->assertNotEmpty($firstCommandId);
        $this->assertSame($firstCommandId, $secondCommandId);

        $this->assertDatabaseCount('command_inbox', 1);
        $this->assertDatabaseHas('command_inbox', [
            'id' => $firstCommandId,
            'source' => 'external_system',
            'type' => 'create_occurrence',
            'scope_key' => 'external-123',
            'status' => 'ENQUEUED',
        ]);

        Queue::assertPushed(ProcessCreateOccurrenceJob::class, function (ProcessCreateOccurrenceJob $job) use ($firstCommandId): bool {
            return $job->commandId === $firstCommandId;
        });
    }

    public function test_update_dispatch_status_rejects_invalid_status_code(): void
    {
        Queue::fake();

        $dispatchId = '018f0e2b-f278-7be1-88f9-cf0d43edc610';

        $response = $this
            ->withHeaders($this->withInternalApiHeaders('idem-internal-00002'))
            ->patchJson("/api/dispatches/{$dispatchId}/status", [
                'statusCode' => 'not-a-valid-status',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');

        $this->assertDatabaseMissing('command_inbox', [
            'type' => 'update_dispatch_status',
            'scope_key' => $dispatchId,
        ]);
        Queue::assertNothingPushed();
    }

    public function test_update_dispatch_status_accepts_valid_status_code_and_registers_command(): void
    {
        Queue::fake();

        $dispatchId = '018f0e2b-f278-7be1-88f9-cf0d43edc611';

        $response = $this
            ->withHeaders($this->withInternalApiHeaders('idem-internal-00003'))
            ->patchJson("/api/dispatches/{$dispatchId}/status", [
                'statusCode' => 'en_route',
            ]);

        $response->assertStatus(202);
        $commandId = (string) $response->json('command_id');

        $this->assertNotEmpty($commandId);
        $this->assertDatabaseHas('command_inbox', [
            'id' => $commandId,
            'source' => 'internal_system',
            'type' => 'update_dispatch_status',
            'scope_key' => $dispatchId,
            'status' => 'ENQUEUED',
        ]);

        Queue::assertPushed(ProcessUpdateDispatchStatusJob::class, function (ProcessUpdateDispatchStatusJob $job) use ($dispatchId, $commandId): bool {
            return $job->dispatchId === $dispatchId
                && $job->statusCode === 'en_route'
                && $job->commandId === $commandId;
        });
    }

    public function test_command_inbox_stores_payload_hash_as_operational_audit_trail(): void
    {
        Queue::fake();

        $payload = $this->createOccurrencePayload([
            'externalId' => 'external-audit-1',
            'type' => 'resgate_veicular',
            'description' => 'Colisao entre dois veiculos com vitimas presas',
            'reportedAt' => '2026-02-12T11:30:00-03:00',
        ]);

        $response = $this
            ->withHeaders($this->withExternalApiHeaders('idem-audit-api-001'))
            ->postJson('/api/integrations/occurrences', $payload);

        $response->assertStatus(202);
        $commandId = (string) $response->json('command_id');
        $row = DB::table('command_inbox')->where('id', $commandId)->first();

        $this->assertNotNull($row);
        $this->assertNotEmpty($row->payload_hash);
        $this->assertSame(
            hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            $row->payload_hash
        );
    }

    public function test_start_occurrence_keeps_idempotency(): void
    {
        Queue::fake();

        $occurrenceId = '018f0e2b-f278-7be1-88f9-cf0d43edc700';
        $this->createOccurrence($occurrenceId, 'ext-start-test-1', 'incendio_urbano', 'reported');

        $headers = $this->withInternalApiHeaders('idem-start-occ-001');

        $first = $this->withHeaders($headers)->postJson("/api/occurrences/{$occurrenceId}/start");
        $second = $this->withHeaders($headers)->postJson("/api/occurrences/{$occurrenceId}/start");

        $first->assertStatus(202);
        $second->assertStatus(202);

        $firstCommandId = (string) $first->json('command_id');
        $secondCommandId = (string) $second->json('command_id');

        $this->assertSame($firstCommandId, $secondCommandId);
        $this->assertDatabaseCount('command_inbox', 1);

        $command = DB::table('command_inbox')->where('id', $firstCommandId)->first();
        $this->assertNotNull($command);
        $this->assertSame('ENQUEUED', $command->status);

        Queue::assertPushed(ProcessStartOccurrenceJob::class, function (ProcessStartOccurrenceJob $job) use ($firstCommandId, $occurrenceId): bool {
            return $job->commandId === $firstCommandId
                && $job->occurrenceId === $occurrenceId;
        });
    }

    public function test_idempotency_conflict_returns_409_when_payload_differs(): void
    {
        Queue::fake();

        $headers = $this->withExternalApiHeaders('idem-conflict-001');

        $firstPayload = $this->createOccurrencePayload([
            'externalId' => 'external-conflict-1',
            'description' => 'Primeira descricao para conflito',
            'reportedAt' => '2026-02-12T14:00:00-03:00',
        ]);

        $secondPayload = $this->createOccurrencePayload([
            'externalId' => 'external-conflict-1',
            'type' => 'resgate_veicular',
            'description' => 'Segunda descricao diferente para conflito',
            'reportedAt' => '2026-02-12T14:00:00-03:00',
        ]);

        $first = $this->withHeaders($headers)->postJson('/api/integrations/occurrences', $firstPayload);
        $first->assertStatus(202);

        $second = $this->withHeaders($headers)->postJson('/api/integrations/occurrences', $secondPayload);
        $second->assertStatus(409);
        $second->assertJsonPath('error', 'Idempotency conflict');

        $this->assertDatabaseCount('command_inbox', 1);
        Queue::assertPushed(ProcessCreateOccurrenceJob::class, 1);
    }

    public function test_concurrent_requests_with_same_idempotency_key_create_single_command(): void
    {
        Queue::fake();

        $headers = $this->withExternalApiHeaders('idem-concurrent-batch-001');

        $payload = $this->createOccurrencePayload([
            'externalId' => 'external-concurrent-batch',
            'description' => 'Teste de concorrencia com multiplas requisicoes',
            'reportedAt' => '2026-02-12T15:00:00-03:00',
        ]);

        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->withHeaders($headers)->postJson('/api/integrations/occurrences', $payload);
        }

        $commandIds = array_map(fn($r) => (string) $r->json('command_id'), $responses);
        $uniqueCommandIds = array_unique($commandIds);

        foreach ($responses as $response) {
            $response->assertStatus(202);
        }

        $this->assertCount(1, $uniqueCommandIds, 'Todas as requisições devem retornar o mesmo command_id');
        $this->assertDatabaseCount('command_inbox', 1);
        
        $command = DB::table('command_inbox')->where('id', $uniqueCommandIds[0])->first();
        $this->assertNotNull($command);
        $this->assertSame('ENQUEUED', $command->status);
        
        Queue::assertPushed(ProcessCreateOccurrenceJob::class, function (ProcessCreateOccurrenceJob $job) use ($uniqueCommandIds): bool {
            return $job->commandId === $uniqueCommandIds[0];
        });
    }
}

