<?php

use App\Http\Controllers\DispatchController;
use App\Http\Controllers\CommandController;
use App\Http\Controllers\Integration\IntegrationOccurrenceController;
use App\Http\Controllers\OccurrenceController;
use App\Http\Middleware\ApiKeyAuthentication;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use Illuminate\Support\Facades\Route;

// Rotas para sistemas externos
Route::prefix('integrations')->middleware([
    ApiKeyAuthentication::class . ':external',
    RateLimitMiddleware::class,
])->group(function () {
    Route::post('/occurrences', [IntegrationOccurrenceController::class, 'create'])
        ->middleware(IdempotencyMiddleware::class);
});

// Rotas para sistemas internos
Route::middleware([
    ApiKeyAuthentication::class . ':internal',
    RateLimitMiddleware::class,
])->group(function () {

    Route::prefix('occurrences')->group(function () {

        Route::get('/', [OccurrenceController::class, 'index']);

        Route::get('/types', [OccurrenceController::class, 'findOccurrenceTypes']);

        Route::get('/status', [OccurrenceController::class, 'findOccurrenceStatuses']);

        Route::get('/{id}', [OccurrenceController::class, 'show']);

        Route::post('/{id}/start', [OccurrenceController::class, 'start'])
            ->middleware(IdempotencyMiddleware::class);

        Route::post('/{id}/resolve', [OccurrenceController::class, 'resolve'])
            ->middleware(IdempotencyMiddleware::class);

        Route::post('/{id}/dispatches', [DispatchController::class, 'create'])
            ->middleware(IdempotencyMiddleware::class);
    });

    Route::prefix('dispatches')->group(function () {

        Route::post('/{id}/close', [DispatchController::class, 'close'])
            ->middleware(IdempotencyMiddleware::class);

        Route::patch('/{id}/status', [DispatchController::class, 'updateStatus'])
            ->middleware(IdempotencyMiddleware::class);
    });

    Route::prefix('commands')->group(function () {
        Route::get('/{id}', [CommandController::class, 'getCommandStatus']);
    });
});
