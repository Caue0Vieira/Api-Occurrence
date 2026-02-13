<?php

use Domain\Idempotency\Exceptions\CommandNotFoundException;
use Domain\Idempotency\Exceptions\IdempotencyConflictException;
use Domain\Occurrence\Exceptions\OccurrenceNotFoundException;
use Domain\Shared\Exceptions\DomainException as DomainRuleException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (OccurrenceNotFoundException $e): JsonResponse {
            return response()->json([
                'error' => 'Not found',
                'message' => $e->getMessage(),
            ], 404);
        });

        $exceptions->render(function (CommandNotFoundException $e): JsonResponse {
            return response()->json([
                'error' => 'Not found',
                'message' => $e->getMessage(),
            ], 404);
        });

        $exceptions->render(function (IdempotencyConflictException $e): JsonResponse {
            return response()->json([
                'error' => 'Idempotency conflict',
                'message' => $e->getMessage(),
            ], 409);
        });

        $exceptions->render(function (DomainRuleException $e): JsonResponse {
            return response()->json([
                'error' => 'Business rule violation',
                'message' => $e->getMessage(),
            ], 422);
        });

        $exceptions->render(function (\InvalidArgumentException $e): JsonResponse {
            return response()->json([
                'error' => 'Invalid parameters',
                'message' => $e->getMessage(),
            ], 400);
        });

        $exceptions->render(function (\Throwable $e): ?JsonResponse {
            if (!app()->environment('production')) {
                return null;
            }

            return response()->json([
                'error' => 'Internal error',
                'message' => 'An unexpected error occurred',
            ], 500);
        });
    })->create();
