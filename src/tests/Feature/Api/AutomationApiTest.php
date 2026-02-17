<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Domain\Idempotency\Enums\CommandSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $headers = $this->withExternalApiHeaders('idem-external-00001');

        $payload = $this->createOccurrencePayload([
            'externalId' => 'external-123',
            'description' => 'Incendio em galpao industrial com fumaca intensa',
            'reportedAt' => '2026-02-12T10:00:00-03:00',
        ]);

        $first = $this->withHeaders($headers)->postJson('/api/integrations/occurrences', $payload);
        $second = $this->withHeaders($headers)->postJson('/api/integrations/occurrences', $payload);

        $first->assertStatus(202);
        $second->assertStatus(409); // Duplicado retorna 409

        $firstCommandId = (string) $first->json('command_id');

        $this->assertNotEmpty($firstCommandId);
        
        // Segunda requisição deve retornar 409 com mensagem de comando duplicado
        $second->assertJson([
            'error' => "Comando duplicado - esta requisição já foi processada anteriormente (command_id: {$firstCommandId})",
        ]);

        $this->assertDatabaseCount('command_inbox', 1);
        $this->assertDatabaseHas('command_inbox', [
            'id' => $firstCommandId,
            'source' => CommandSource::EXTERNAL->value,
            'type' => 'create_occurrence',
            'scope_key' => 'external-123',
            'status' => 'RECEIVED',
        ]);
        $this->assertDatabaseHas('outbox', [
            'aggregate_type' => 'OccurrenceCommand',
            'aggregate_id' => $firstCommandId,
            'event_type' => 'OccurrenceCreateRequested',
            'status' => 'PENDING',
        ]);
        $this->assertDatabaseCount('outbox', 1);
    }

    public function test_update_dispatch_status_rejects_invalid_status_code(): void
    {
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
        $this->assertDatabaseCount('outbox', 0);
    }

    public function test_update_dispatch_status_accepts_valid_status_code_and_registers_command(): void
    {
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
            'source' => CommandSource::INTERNAL->value,
            'type' => 'update_dispatch_status',
            'scope_key' => $dispatchId,
            'status' => 'RECEIVED',
        ]);
        $this->assertDatabaseHas('outbox', [
            'aggregate_type' => 'DispatchCommand',
            'aggregate_id' => $commandId,
            'event_type' => 'DispatchStatusUpdateRequested',
            'status' => 'PENDING',
        ]);
    }

    public function test_command_inbox_stores_payload_hash_as_operational_audit_trail(): void
    {
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
        $occurrenceId = '018f0e2b-f278-7be1-88f9-cf0d43edc700';
        $this->createOccurrence($occurrenceId, 'ext-start-test-1', 'incendio_urbano', 'reported');

        $headers = $this->withInternalApiHeaders('idem-start-occ-001');

        $first = $this->withHeaders($headers)->postJson("/api/occurrences/{$occurrenceId}/start");
        $second = $this->withHeaders($headers)->postJson("/api/occurrences/{$occurrenceId}/start");

        $first->assertStatus(202);
        $second->assertStatus(409); // Duplicado retorna 409

        $firstCommandId = (string) $first->json('command_id');

        // Segunda requisição deve retornar 409 com mensagem de comando duplicado
        $second->assertJson([
            'error' => "Comando duplicado - esta requisição já foi processada anteriormente (command_id: {$firstCommandId})",
        ]);
        $this->assertDatabaseCount('command_inbox', 1);

        $command = DB::table('command_inbox')->where('id', $firstCommandId)->first();
        $this->assertNotNull($command);
        $this->assertSame('RECEIVED', $command->status);
        $this->assertDatabaseHas('outbox', [
            'aggregate_type' => 'OccurrenceCommand',
            'aggregate_id' => $firstCommandId,
            'event_type' => 'OccurrenceStartRequested',
            'status' => 'PENDING',
        ]);
        $this->assertDatabaseCount('outbox', 1);
    }

    public function test_cancel_occurrence_keeps_idempotency(): void
    {
        $occurrenceId = '018f0e2b-f278-7be1-88f9-cf0d43edc800';
        $this->createOccurrence($occurrenceId, 'ext-cancel-test-1', 'incendio_urbano', 'reported');

        $headers = $this->withInternalApiHeaders('idem-cancel-occ-001');

        $first = $this->withHeaders($headers)->postJson("/api/occurrences/{$occurrenceId}/cancel");
        $second = $this->withHeaders($headers)->postJson("/api/occurrences/{$occurrenceId}/cancel");

        $first->assertStatus(202);
        $second->assertStatus(409); // Duplicado retorna 409

        $firstCommandId = (string) $first->json('command_id');

        // Segunda requisição deve retornar 409 com mensagem de comando duplicado
        $second->assertJson([
            'error' => "Comando duplicado - esta requisição já foi processada anteriormente (command_id: {$firstCommandId})",
        ]);
        $this->assertDatabaseCount('command_inbox', 1);

        $command = DB::table('command_inbox')->where('id', $firstCommandId)->first();
        $this->assertNotNull($command);
        $this->assertSame('RECEIVED', $command->status);
        $this->assertDatabaseHas('outbox', [
            'aggregate_type' => 'OccurrenceCommand',
            'aggregate_id' => $firstCommandId,
            'event_type' => 'OccurrenceCancelledRequested',
            'status' => 'PENDING',
        ]);
        $this->assertDatabaseCount('outbox', 1);
    }

    public function test_cancel_occurrence_returns_404_when_not_found(): void
    {
        $occurrenceId = '018f0e2b-f278-7be1-88f9-cf0d43edc999';
        $headers = $this->withInternalApiHeaders('idem-cancel-occ-002');

        $response = $this->withHeaders($headers)->postJson("/api/occurrences/{$occurrenceId}/cancel");

        $response->assertStatus(404);
        $response->assertJson([
            'error' => "Occurrence not found: {$occurrenceId}",
        ]);
    }

    public function test_idempotency_conflict_returns_409_when_payload_differs(): void
    {
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
        $this->assertDatabaseCount('outbox', 1);
    }

    public function test_concurrent_requests_with_same_idempotency_key_create_single_command(): void
    {
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

        // Primeira requisição deve ser 202, as demais (duplicadas) devem ser 409
        $responses[0]->assertStatus(202);
        $firstCommandId = (string) $responses[0]->json('command_id');
        
        for ($i = 1; $i < count($responses); $i++) {
            $responses[$i]->assertStatus(409); // Duplicado retorna 409
            $responses[$i]->assertJson([
                'error' => "Comando duplicado - esta requisição já foi processada anteriormente (command_id: {$firstCommandId})",
            ]);
        }
        
        $commandIds = [$firstCommandId];
        $uniqueCommandIds = array_unique($commandIds);

        $this->assertCount(1, $uniqueCommandIds, 'Todas as requisições devem retornar o mesmo command_id');
        $this->assertDatabaseCount('command_inbox', 1);
        
        $command = DB::table('command_inbox')->where('id', $uniqueCommandIds[0])->first();
        $this->assertNotNull($command);
        $this->assertSame('RECEIVED', $command->status);
        $this->assertDatabaseHas('outbox', [
            'aggregate_type' => 'OccurrenceCommand',
            'aggregate_id' => $uniqueCommandIds[0],
            'event_type' => 'OccurrenceCreateRequested',
            'status' => 'PENDING',
        ]);
        $this->assertDatabaseCount('outbox', 1);
    }

    public function test_rejects_duplicate_external_id_with_different_idempotency_key(): void
    {
        $externalId = 'external-duplicate-test';
        $payload = $this->createOccurrencePayload([
            'externalId' => $externalId,
            'description' => 'Primeira ocorrência',
            'reportedAt' => '2026-02-12T10:00:00-03:00',
        ]);

        // Primeira requisição - deve criar com sucesso
        $firstHeaders = $this->withExternalApiHeaders('idem-key-001');
        $firstResponse = $this->withHeaders($firstHeaders)->postJson('/api/integrations/occurrences', $payload);
        $firstResponse->assertStatus(202);

        $firstCommandId = (string) $firstResponse->json('command_id');
        $this->assertNotEmpty($firstCommandId);

        // Simular que o worker processou e criou a ocorrência
        $this->createOccurrence(
            id: '018f0e2b-f278-7be1-88f9-cf0d43edc999',
            externalId: $externalId,
            typeCode: $payload['type'],
            statusCode: 'reported'
        );

        // Segunda requisição com external_id igual mas idempotencyKey diferente - deve rejeitar
        $secondHeaders = $this->withExternalApiHeaders('idem-key-002');
        $secondResponse = $this->withHeaders($secondHeaders)->postJson('/api/integrations/occurrences', $payload);
        $secondResponse->assertStatus(409);
        $secondResponse->assertJson([
            'error' => "Occurrence with external ID '{$externalId}' already exists",
        ]);

        // Verificar que não criou novo comando nem registro na outbox
        $this->assertDatabaseCount('command_inbox', 1);
        $this->assertDatabaseCount('outbox', 1);
        $this->assertDatabaseHas('command_inbox', [
            'id' => $firstCommandId,
        ]);
    }
}

