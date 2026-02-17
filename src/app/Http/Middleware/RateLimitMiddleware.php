<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: Rate Limiting
 *
 * Limita número de requisições por minuto por API Key.
 * Implementação: 100 requisições por minuto.
 */
class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('api.rate_limit.enabled', true)) {
            return $next($request);
        }

        $apiKey = $request->attributes->get('api_key', 'unknown');
        $limit = config('api.rate_limit.requests_per_minute', 100);

        $rateLimitKey = "rate_limit:api:{$apiKey}:" . now()->format('Y-m-d-H-i');

        $currentCount = Redis::incr($rateLimitKey);

        if ($currentCount === 1) {
            Redis::expire($rateLimitKey, 60);
        }

        if ($currentCount > $limit) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => "You have exceeded the rate limit of {$limit} requests per minute",
                'retry_after' => 60,
            ], 429)->header('Retry-After', '60');
        }

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $limit - $currentCount));
        $response->headers->set('X-RateLimit-Reset', (string) (time() + 60));

        return $response;
    }
}

