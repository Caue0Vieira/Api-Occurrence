<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Domain\Idempotency\Services\CommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Commands")]
class CommandController extends Controller
{
    public function __construct(
        private readonly CommandService $commandService,
    ) {
    }

    #[OA\Get(
        path: "/api/commands/{command_id}",
        operationId: "getCommandStatus",
        description: "Retorna o status atual de um comando assíncrono. Útil para verificar o progresso de operações como criação de ocorrências, despachos, etc.",
        summary: "Consultar status de um comando",
        security: [
            ["apiKey" => []]
        ],
        tags: ["Commands"],
        parameters: [
            new OA\Parameter(
                name: "command_id",
                description: "ID único do comando (UUID)",
                in: "path",
                required: true,
                schema: new OA\Schema(
                    type: "string",
                    format: "uuid",
                    example: "550e8400-e29b-41d4-a716-446655440002"
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Status do comando retornado com sucesso",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/Command"
                )
            ),
            new OA\Response(
                response: 404,
                description: "Comando não encontrado",
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
    public function getCommandStatus(string $command_id): JsonResponse
    {
        $result = $this->commandService->getCommandStatus($command_id);

        return response()->json($result->toArray());
    }
}
