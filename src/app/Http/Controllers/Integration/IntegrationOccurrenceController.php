<?php

declare(strict_types=1);

namespace App\Http\Controllers\Integration;

use App\Http\Requests\CreateOccurrenceRequest;
use Domain\Occurrence\Services\OccurrenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Integrations")]
class IntegrationOccurrenceController extends Controller
{
    public function __construct(
        private readonly OccurrenceService $occurrenceService
    ) {}

    #[OA\Post(
        path: "/api/integrations/occurrences",
        operationId: "createOccurrence",
        description: "Endpoint para sistemas externos criarem ocorrências. Requer API Key de integração externa. Retorna um comando que pode ser consultado posteriormente para verificar o status",
        summary: "Criar ocorrência (Integração Externa)",
        security: [
            ["apiKey" => []],
            ["idempotencyKey" => []]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: "#/components/schemas/CreateOccurrenceRequest"
            )
        ),
        tags: ["Integrations"],
        responses: [
            new OA\Response(
                response: 202,
                description: "Comando de criação de ocorrência aceito para processamento",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Command"
                )
            ),
            new OA\Response(
                response: 422,
                description: "Erro de validação - Dados inválidos",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            ),
            new OA\Response(
                response: 409,
                description: "Requisição duplicada - Idempotency Key já utilizada ou externalId já existe",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            ),
            new OA\Response(
                response: 401,
                description: "Não autenticado - API Key inválida ou ausente",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            ),
            new OA\Response(
                response: 429,
                description: "Muitas requisições - Rate limit excedido",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            )
        ]
    )]
    public function create(CreateOccurrenceRequest $request): JsonResponse
    {
        Log::info('📥 [API] POST /api/integrations/occurrences received', [
            'externalId' => $request->input('externalId'),
            'type' => $request->input('type'),
            'idempotencyKey' => $request->attributes->get('idempotency_key'),
        ]);

        try {
            Log::info('📦 [API] Calling occurrence service', [
                'externalId' => $request->input('externalId'),
            ]);

            $result = $this->occurrenceService->createOccurrence(
                externalId: $request->input('externalId'),
                type: $request->input('type'),
                description: $request->input('description'),
                reportedAt: $request->input('reportedAt'),
                idempotencyKey: (string) $request->attributes->get('idempotency_key'),
                source: 'external_system'
            );

            Log::info('✅ [API] Service completed, returning response', [
                'commandId' => $result->commandId,
            ]);

            return response()->json($result->toArray(), 202);
        } catch (\Throwable $e) {
            Log::error('❌ [API] Error in create occurrence', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
