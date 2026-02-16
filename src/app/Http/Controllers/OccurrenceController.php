<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\OccurrenceResource;
use Domain\Idempotency\Enums\CommandSource;
use Domain\Idempotency\Exceptions\DuplicateCommandException;
use Domain\Occurrence\Exceptions\OccurrenceNotFoundException;
use Domain\Occurrence\Services\OccurrenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Occurrences")]
class OccurrenceController extends Controller
{
    public function __construct(
        private readonly OccurrenceService $occurrenceService,
    ) {
    }

    #[OA\Get(
        path: "/api/occurrences",
        operationId: "listOccurrences",
        description: "Retorna uma lista paginada de ocorrências com opções de filtro por status e tipo",
        summary: "Listar ocorrências",
        security: [
            ["apiKey" => []]
        ],
        tags: ["Occurrences"],
        parameters: [
            new OA\Parameter(
                name: "status",
                description: "Filtrar por status da ocorrência",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    example: "pending"
                )
            ),
            new OA\Parameter(
                name: "type",
                description: "Filtrar por tipo de ocorrência",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    example: "incendio_urbano"
                )
            ),
            new OA\Parameter(
                name: "limit",
                description: "Número de itens por página",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "integer",
                    default: 50,
                    maximum: 100,
                    minimum: 1
                )
            ),
            new OA\Parameter(
                name: "page",
                description: "Número da página",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "integer",
                    default: 1,
                    minimum: 1
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de ocorrências retornada com sucesso",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/OccurrenceListResponse"
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
    public function index(Request $request): JsonResponse
    {
        $result = $this->occurrenceService->listOccurrencesWithCache(
            status: $request->query('status'),
            type: $request->query('type'),
            limit: (int) $request->query('limit', 50),
            page: (int) $request->query('page', 1),
        );

        return response()->json($result->toArray());
    }

    #[OA\Get(
        path: "/api/occurrences/{id}",
        operationId: "getOccurrence",
        description: "Retorna os detalhes completos de uma ocorrência específica, incluindo seus despachos",
        summary: "Obter detalhes de uma ocorrência",
        security: [
            ["apiKey" => []]
        ],
        tags: ["Occurrences"],
        parameters: [
            new OA\Parameter(
                name: "id",
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
                response: 200,
                description: "Detalhes da ocorrência retornados com sucesso",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/OccurrenceResponse"
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
                response: 401,
                description: "Não autenticado - API Key inválida ou ausente",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            )
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $occurrence = $this->occurrenceService->getOccurrenceByIdWithDispatchesOrFail($id);

        return (new OccurrenceResource($occurrence))->response();
    }

    #[OA\Post(
        path: "/api/occurrences/{id}/start",
        operationId: "startOccurrence",
        description: "Inicia o processamento assíncrono de uma ocorrência. Retorna um comando que pode ser consultado posteriormente para verificar o status",
        summary: "Iniciar processamento de uma ocorrência",
        security: [
            ["apiKey" => []],
            ["idempotencyKey" => []]
        ],
        tags: ["Occurrences"],
        parameters: [
            new OA\Parameter(
                name: "id",
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
                description: "Comando de início aceito para processamento",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Command"
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
                response: 401,
                description: "Não autenticado - API Key inválida ou ausente",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            )
        ]
    )]
    public function start(Request $request, string $id): JsonResponse
    {
        try {
            $result = $this->occurrenceService->startOccurrence(
                occurrenceId: $id,
                idempotencyKey: (string) $request->attributes->get('idempotency_key'),
                source: CommandSource::INTERNAL
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
        path: "/api/occurrences/{id}/resolve",
        operationId: "resolveOccurrence",
        description: "Marca uma ocorrência como resolvida. Retorna um comando que pode ser consultado posteriormente para verificar o status",
        summary: "Resolver uma ocorrência",
        security: [
            ["apiKey" => []],
            ["idempotencyKey" => []]
        ],
        tags: ["Occurrences"],
        parameters: [
            new OA\Parameter(
                name: "id",
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
                description: "Comando de resolução aceito para processamento",
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
                response: 401,
                description: "Não autenticado - API Key inválida ou ausente",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Error"
                )
            )
        ]
    )]
    public function resolve(Request $request, string $id): JsonResponse
    {
        try {
            $result = $this->occurrenceService->resolveOccurrence(
                occurrenceId: $id,
                idempotencyKey: (string) $request->attributes->get('idempotency_key'),
                source: CommandSource::INTERNAL
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

    #[OA\Get(
        path: "/api/occurrences/types",
        operationId: "getOccurrenceTypes",
        description: "Retorna todos os tipos de ocorrência disponíveis no sistema",
        summary: "Listar tipos de ocorrência disponíveis",
        security: [
            ["apiKey" => []]
        ],
        tags: ["Occurrences"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de tipos de ocorrência retornada com sucesso",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/OccurrenceTypesResponse"
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
    public function findOccurrenceTypes(): JsonResponse
    {
        $types = $this->occurrenceService->findOccurrenceTypes();

        return response()->json(['data' => $types]);
    }

    #[OA\Get(
        path: "/api/occurrences/status",
        operationId: "getOccurrenceStatuses",
        description: "Retorna todos os status de ocorrência disponíveis no sistema",
        summary: "Listar status de ocorrência disponíveis",
        security: [
            ["apiKey" => []]
        ],
        tags: ["Occurrences"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de status de ocorrência retornada com sucesso",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/OccurrenceStatusesResponse"
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
    public function findOccurrenceStatuses(): JsonResponse
    {
        $statuses = $this->occurrenceService->findOccurrenceStatuses();

        return response()->json(['data' => $statuses]);
    }
}
