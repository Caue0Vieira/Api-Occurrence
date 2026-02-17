<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: Idempotency-Key
 *
 * Valida se requisições que alteram estado possuem Idempotency-Key.
 */
class IdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requiredMethods = config('api.idempotency.required_methods', ['POST', 'PUT', 'PATCH']);

        if (in_array($request->method(), $requiredMethods, true)) {
            $idempotencyKey = $request->header(config('api.idempotency.header_name', 'Idempotency-Key'));

            if (!$idempotencyKey) {
                return response()->json([
                    'error' => 'Missing Idempotency-Key header',
                    'message' => 'Idempotency-Key is required for this operation',
                ], 400);
            }

            if (strlen($idempotencyKey) < 10) {
                return response()->json([
                    'error' => 'Invalid Idempotency-Key',
                    'message' => 'Idempotency-Key must be at least 10 characters long',
                ], 400);
            }

            $request->attributes->set('idempotency_key', $idempotencyKey);
        }

        return $next($request);
    }
}

