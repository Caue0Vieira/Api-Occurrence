<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthentication
{
    public function handle(Request $request, Closure $next, string $expectedType): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing X-API-Key header',
            ], 401);
        }

        $keyType = $this->identifyKeyType($apiKey);

        if (!$keyType) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API key',
            ], 401);
        }

        if ($keyType !== $expectedType) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => "API key type '$keyType' does not have access to '$expectedType' routes",
            ], 403);
        }

        $request->attributes->set('api_key_type', $keyType);
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }

    /**
     * Identifica o tipo da chave API
     */
    private function identifyKeyType(string $apiKey): ?string
    {
        $keys = config('api.keys');

        foreach ($keys as $type => $key) {
            if ($key && hash_equals($key, $apiKey)) {
                return $type;
            }
        }

        return null;
    }
}

