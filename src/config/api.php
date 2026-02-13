<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Authentication
    |--------------------------------------------------------------------------
    |
    | Configurações de autenticação via X-API-Key para acesso à API.
    | Suporta múltiplas chaves para diferentes clientes/sistemas.
    |
    */

    'keys' => [
        // Chave para sistema externo
        'external' => env('API_KEY_EXTERNAL', 'external-system-key'),

        // Chave para frontend interno
        'internal' => env('API_KEY_INTERNAL', 'internal-frontend-key'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configurações de limite de requisições por minuto para cada API Key.
    |
    */

    'rate_limit' => [
        'requests_per_minute' => env('API_RATE_LIMIT', 100),
        'enabled' => env('API_RATE_LIMIT_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Idempotency Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para garantir idempotência nas requisições.
    |
    */

    'idempotency' => [
        'header_name' => 'Idempotency-Key',
        'ttl' => env('IDEMPOTENCY_TTL', 86400), // 24 horas em segundos
        'wait_max_seconds' => env('IDEMPOTENCY_WAIT_MAX_SECONDS', 5), // Timeout máximo para getResult()
        'poll_interval_ms' => env('IDEMPOTENCY_POLL_INTERVAL_MS', 100), // Intervalo entre verificações no polling
        'required_methods' => ['POST', 'PUT', 'PATCH'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações padrão para respostas da API.
    |
    */

    'response' => [
        'async_status_code' => 202, // Accepted
        'include_request_id' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Occurrences List Cache
    |--------------------------------------------------------------------------
    |
    | Configurações de cache para listagem de ocorrências (cache-first).
    |
    */
    'occurrences_cache' => [
        'enabled' => env('OCCURRENCES_CACHE_ENABLED', true),
        'ttl_seconds' => env('OCCURRENCES_CACHE_TTL_SECONDS', 300),
        'redis_connection' => env('OCCURRENCES_CACHE_REDIS_CONNECTION', 'cache'),
        'key_prefix' => env('OCCURRENCES_CACHE_KEY_PREFIX', 'occurrences:list'),
    ],

];

