<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateDispatchRequest;
use App\Http\Requests\UpdateDispatchStatusRequest;
use Domain\Dispatch\Service\DispatchService;
use Domain\Idempotency\Exceptions\DuplicateCommandException;
use Domain\Occurrence\Exceptions\OccurrenceNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Dispatches")]
class DispatchController extends Controller
{
    public function __construct(
        private readonly DispatchService $dispatchService
    ) {}

    #[OA\Post(
        path: "/api/occurrences/{occurrenceId}/dispatches",
        operationId: "createDispatch",
        description: "Cria um novo despacho para uma ocorrência específica. Retorna um comando que pode ser consultado posteriormente para verificar o status",
        summary: "Criar um novo despacho",
        security: [
            ["apiKey" => []],
            ["idempotencyKey" => []]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: "#/components/schemas/CreateDispatchRequest"
            )
        ),
        tags: ["Dispatches"],
        parameters: [
            new OA\Parameter(
                name: "occurrenceId",
                description: "ID único da ocorrência (UUID)",
                in: "path",
                required: true,
                schema: new OA\Schema(
                    type: "string",
                    format: "uuid",
                    example: "550e8400-e29b-41d4-a716-446655440000"
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 202,
                description: "Comando de criação de despacho aceito para processamento",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Command"
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad Request - Idempotency-Key ausente ou inválido",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            ),
            new OA\Response(
                response: 404,
                description: "Ocorrência não encontrada",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            ),
            new OA\Response(
                response: 409,
                description: "Requisição duplicada - Idempotency Key já utilizada",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
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
                response: 401,
                description: "Não autenticado - API Key inválida ou ausente",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            )
        ]
    )]
    public function create(CreateDispatchRequest $request, string $occurrenceId): JsonResponse
    {
        try {
            $result = $this->dispatchService->createDispatch(
                occurrenceId: $occurrenceId,
                resourceCode: $request->input('resourceCode'),
                idempotencyKey: (string) $request->attributes->get('idempotency_key')
            );

            return response()->json($result->toArray(), 202);
        } catch (OccurrenceNotFoundException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode());
        } catch (DuplicateCommandException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    #[OA\Post(
        path: "/api/dispatches/{dispatchId}/close",
        operationId: "closeDispatch",
        description: "Fecha um despacho específico. Retorna um comando que pode ser consultado posteriormente para verificar o status",
        summary: "Fechar um despacho",
        security: [
            ["apiKey" => []],
            ["idempotencyKey" => []]
        ],
        tags: ["Dispatches"],
        parameters: [
            new OA\Parameter(
                name: "dispatchId",
                description: "ID único do despacho (UUID)",
                in: "path",
                required: true,
                schema: new OA\Schema(
                    type: "string",
                    format: "uuid",
                    example: "550e8400-e29b-41d4-a716-446655440001"
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 202,
                description: "Comando de fechamento de despacho aceito para processamento",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Command"
                )
            ),
            new OA\Response(
                response: 400,
                description: "Bad Request - Idempotency-Key ausente ou inválido",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            ),
            new OA\Response(
                response: 404,
                description: "Despacho não encontrado",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            ),
            new OA\Response(
                response: 409,
                description: "Requisição duplicada - Idempotency Key já utilizada",
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
            )
        ]
    )]
    public function close(Request $request, string $dispatchId): JsonResponse
    {
        try {
        $result = $this->dispatchService->closeDispatch(
            dispatchId: $dispatchId,
            idempotencyKey: (string) $request->attributes->get('idempotency_key')
        );

        return response()->json($result->toArray(), 202);
        } catch (DuplicateCommandException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    #[OA\Patch(
        path: "/api/dispatches/{dispatchId}/status",
        operationId: "updateDispatchStatus",
        description: "Atualiza o status de um despacho. Retorna um comando que pode ser consultado posteriormente para verificar o status",
        summary: "Atualizar status de um despacho",
        security: [
            ["apiKey" => []],
            ["idempotencyKey" => []]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: "#/components/schemas/UpdateDispatchStatusRequest"
            )
        ),
        tags: ["Dispatches"],
        parameters: [
            new OA\Parameter(
                name: "dispatchId",
                description: "ID único do despacho (UUID)",
                in: "path",
                required: true,
                schema: new OA\Schema(
                    type: "string",
                    format: "uuid",
                    example: "550e8400-e29b-41d4-a716-446655440001"
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 202,
                description: "Comando de atualização de status aceito para processamento",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Command"
                )
            ),
            new OA\Response(
                response: 404,
                description: "Despacho não encontrado",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            ),
            new OA\Response(
                response: 422,
                description: "Erro de validação - Status inválido",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            ),
            new OA\Response(
                response: 409,
                description: "Requisição duplicada - Idempotency Key já utilizada",
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
            )
        ]
    )]
    public function updateStatus(UpdateDispatchStatusRequest $request, string $dispatchId): JsonResponse
    {
        try {
        $result = $this->dispatchService->updateDispatchStatus(
            dispatchId: $dispatchId,
            statusCode: $request->input('statusCode'),
                idempotencyKey: (string) $request->attributes->get('idempotency_key')
        );

        return response()->json($result->toArray(), 202);
        } catch (DuplicateCommandException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode());
        }
    }
}
