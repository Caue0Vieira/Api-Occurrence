<?php

declare(strict_types=1);

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

/**
 * Classe para definir schemas e configurações globais do Swagger/OpenAPI
 *
 * Esta classe contém todas as definições reutilizáveis de schemas, tags,
 * security schemes e informações gerais da API.
 */
#[OA\Info(
    version: "1.0.0",
    description: "API para gerenciamento de ocorrências, despachos e comandos. Suporta integrações externas e sistemas internos.",
    title: "API de Ocorrências",
    contact: new OA\Contact(
        email: "suporte@example.com"
    )
)]
#[OA\Server(
    url: "http://localhost:8089",
    description: "Servidor de API"
)]
#[OA\Tag(
    name: "Occurrences",
    description: "Endpoints para gerenciamento de ocorrências"
)]
#[OA\Tag(
    name: "Dispatches",
    description: "Endpoints para gerenciamento de despachos"
)]
#[OA\Tag(
    name: "Commands",
    description: "Endpoints para consulta de status de comandos"
)]
#[OA\Tag(
    name: "Integrations",
    description: "Endpoints para integrações externas"
)]
#[OA\Schema(
    schema: "Occurrence",
    title: "Ocorrência",
    description: "Modelo de dados de uma ocorrência",
    properties: [
        new OA\Property(property: "id", type: "string", example: "550e8400-e29b-41d4-a716-446655440000"),
        new OA\Property(property: "external_id", type: "string", example: "EXT-12345"),
        new OA\Property(property: "type_code", type: "string", example: "incendio_urbano"),
        new OA\Property(property: "type_name", type: "string", example: "Incêndio Urbano"),
        new OA\Property(property: "type_category", type: "string", example: "emergency"),
        new OA\Property(property: "status_code", type: "string", example: "pending"),
        new OA\Property(property: "status_name", type: "string", example: "Pendente"),
        new OA\Property(property: "description", type: "string", example: "Incêndio em prédio residencial"),
        new OA\Property(property: "reported_at", type: "string", format: "date-time", example: "2026-02-01T14:32:00-03:00"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-02-01T14:32:00-03:00"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-02-01T14:32:00-03:00"),
        new OA\Property(
            property: "dispatches",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/Dispatch")
        )
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "Dispatch",
    title: "Despacho",
    description: "Modelo de dados de um despacho",
    properties: [
        new OA\Property(property: "id", type: "string", example: "550e8400-e29b-41d4-a716-446655440001"),
        new OA\Property(property: "resource_code", type: "string", example: "ABT-12"),
        new OA\Property(property: "status_code", type: "string", example: "assigned"),
        new OA\Property(property: "status_name", type: "string", example: "Atribuído"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-02-01T14:32:00-03:00")
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "Command",
    title: "Comando",
    description: "Modelo de dados de um comando assíncrono",
    properties: [
        new OA\Property(property: "command_id", type: "string", example: "550e8400-e29b-41d4-a716-446655440002"),
        new OA\Property(property: "status", type: "string", enum: ["RECEIVED", "ENQUEUED", "PROCESSING", "SUCCEEDED", "FAILED"], example: "RECEIVED"),
        new OA\Property(property: "result", description: "Resultado do comando quando status é 'SUCCEEDED'", type: "object", nullable: true),
        new OA\Property(property: "error_message", description: "Mensagem de erro quando status é 'FAILED'", type: "string", nullable: true),
        new OA\Property(property: "processed_at", description: "Data/hora em que o comando terminou processamento", type: "string", format: "date-time", nullable: true)
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "Error",
    title: "Erro",
    description: "Modelo de resposta de erro",
    properties: [
        new OA\Property(property: "error", type: "string", example: "Validation failed"),
        new OA\Property(property: "message", type: "string", example: "The given data was invalid"),
        new OA\Property(
            property: "errors",
            description: "Detalhes dos erros de validação",
            type: "object",
            additionalProperties: new OA\AdditionalProperties(
                type: "array",
                items: new OA\Items(type: "string")
            )
        )
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "CreateOccurrenceRequest",
    title: "Criar Ocorrência",
    description: "Dados necessários para criar uma nova ocorrência",
    required: ["externalId", "type", "description", "reportedAt"],
    properties: [
        new OA\Property(
            property: "externalId",
            description: "ID externo da ocorrência (único por sistema externo)",
            type: "string",
            maxLength: 100,
            example: "EXT-12345"
        ),
        new OA\Property(
            property: "type",
            description: "Tipo da ocorrência",
            type: "string",
            enum: ["incendio_urbano", "resgate_veicular", "atendimento_pre_hospitalar", "salvamento_aquatico", "falso_chamado", "vazamento_gas", "queda_arvore", "incendio_florestal"],
            example: "incendio_urbano"
        ),
        new OA\Property(
            property: "description",
            description: "Descrição detalhada da ocorrência",
            type: "string",
            maxLength: 5000,
            minLength: 10,
            example: "Incêndio em prédio residencial de 3 andares"
        ),
        new OA\Property(
            property: "reportedAt",
            description: "Data e hora do reporte da ocorrência (formato ISO 8601)",
            type: "string",
            format: "date-time",
            example: "2026-02-01T14:32:00-03:00"
        )
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "CreateDispatchRequest",
    title: "Criar Despacho",
    description: "Dados necessários para criar um novo despacho",
    required: ["resourceCode"],
    properties: [
        new OA\Property(
            property: "resourceCode",
            description: "Código do recurso no formato XX-YY ou XXX-YY (ex: ABT-12, UR-05)",
            type: "string",
            pattern: "^[A-Z]{2,3}-\\d{2}$",
            example: "ABT-12"
        )
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "UpdateDispatchStatusRequest",
    title: "Atualizar Status do Despacho",
    description: "Dados necessários para atualizar o status de um despacho",
    required: ["statusCode"],
    properties: [
        new OA\Property(
            property: "statusCode",
            description: "Novo status do despacho",
            type: "string",
            enum: ["assigned", "en_route", "on_site", "closed"],
            example: "on_site"
        )
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "OccurrenceListResponse",
    title: "Lista de Ocorrências",
    properties: [
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/Occurrence")
        ),
        new OA\Property(property: "meta", description: "Metadados de paginação", type: "object"),
        new OA\Property(property: "links", description: "Links de paginação", type: "object")
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "OccurrenceResponse",
    title: "Ocorrência Detalhada",
    properties: [
        new OA\Property(property: "data", ref: "#/components/schemas/Occurrence")
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "OccurrenceTypesResponse",
    title: "Tipos de Ocorrência",
    properties: [
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "code", type: "string", example: "incendio_urbano"),
                    new OA\Property(property: "name", type: "string", example: "Incêndio Urbano"),
                    new OA\Property(property: "category", type: "string", example: "emergency")
                ],
                type: "object"
            )
        )
    ],
    type: "object"
)]
#[OA\Schema(
    schema: "OccurrenceStatusesResponse",
    title: "Status de Ocorrência",
    properties: [
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "code", type: "string", example: "pending"),
                    new OA\Property(property: "name", type: "string", example: "Pendente")
                ],
                type: "object"
            )
        )
    ],
    type: "object"
)]
#[OA\SecurityScheme(
    securityScheme: "apiKey",
    type: "apiKey",
    description: "Chave de API para autenticação",
    name: "X-API-Key",
    in: "header"
)]
#[OA\SecurityScheme(
    securityScheme: "idempotencyKey",
    type: "apiKey",
    description: "Chave de idempotência (obrigatória para POST, PUT, PATCH)",
    name: "Idempotency-Key",
    in: "header"
)]
class SwaggerSchemas
{
    // Esta classe existe apenas para conter as anotações OpenAPI
    // As anotações são processadas pelo swagger-php durante a geração da documentação
}
