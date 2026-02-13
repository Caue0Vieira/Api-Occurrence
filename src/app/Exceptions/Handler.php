<?php

declare(strict_types=1);

namespace App\Exceptions;

use Domain\Idempotency\Exceptions\IdempotencyConflictException;
use Domain\Idempotency\Exceptions\CommandNotFoundException;
use Domain\Occurrence\Exceptions\OccurrenceNotFoundException;
use Domain\Shared\Exceptions\DomainException as DomainRuleException;
use Error;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (OccurrenceNotFoundException $e, $request): JsonResponse {
            return response()->json([
                'error' => 'Not found',
                'message' => $e->getMessage(),
            ], 404);
        });

        $this->renderable(function (CommandNotFoundException $e, $request): JsonResponse {
            return response()->json([
                'error' => 'Not found',
                'message' => $e->getMessage(),
            ], 404);
        });

        $this->renderable(function (IdempotencyConflictException $e, $request): JsonResponse {
            return response()->json([
                'error' => 'Idempotency conflict',
                'message' => $e->getMessage(),
            ], 409);
        });

        $this->renderable(function (DomainRuleException $e, $request): JsonResponse {
            return response()->json([
                'error' => 'Business rule violation',
                'message' => $e->getMessage(),
            ], 422);
        });

        $this->renderable(function (InvalidArgumentException $e, $request): JsonResponse {
            return response()->json([
                'error' => 'Invalid parameters',
                'message' => $e->getMessage(),
            ], 400);
        });

        // fallback 500 (recomendado só em produção)
        $this->renderable(function (Throwable $e, $request): JsonResponse {
            if (!app()->environment('production')) {
                // deixa o Laravel mostrar o erro detalhado no ambiente de dev/test
                throw $e;
            }

            return response()->json([
                'error' => 'Internal error',
                'message' => 'An unexpected error occurred',
            ], 500);
        });
    }
}
