<?php

declare(strict_types=1);

namespace App\Providers;

use Application\UseCases\CloseDispatch\CloseDispatchHandler;
use Application\UseCases\CreateDispatch\CreateDispatchHandler;
use Application\UseCases\CreateOccurrence\CreateOccurrenceHandler;
use Application\UseCases\GetCommandStatus\GetCommandStatusHandler;
use Application\UseCases\GetOccurrence\GetOccurrenceHandler;
use Application\UseCases\ListOccurrences\ListOccurrencesHandler;
use Application\UseCases\ResolveOccurrence\ResolveOccurrenceHandler;
use Application\UseCases\StartOccurrence\StartOccurrenceHandler;
use Application\UseCases\UpdateDispatchStatus\UpdateDispatchStatusHandler;
use Application\Ports\OccurrenceListCacheInterface;
use Domain\Idempotency\Repositories\CommandInboxReadRepositoryInterface;
use Domain\Idempotency\Repositories\CommandInboxWriteRepositoryInterface;
use Domain\Idempotency\Services\CommandService;
use Domain\Dispatch\Repositories\DispatchRepositoryInterface;
use Domain\Dispatch\Service\DispatchService;
use Domain\Occurrence\Repositories\OccurrenceRepositoryInterface;
use Domain\Occurrence\Services\OccurrenceService;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Persistence\Repositories\CommandInboxReadRepository;
use Infrastructure\Persistence\Repositories\CommandInboxWriteRepository;
use Infrastructure\Persistence\Repositories\DispatchRepository;
use Infrastructure\Persistence\Repositories\OccurrenceRepository;
use Infrastructure\Cache\OccurrenceListRedisCache;
use L5Swagger\L5SwaggerServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repositories (Domain → Infrastructure)
        $this->app->bind(
            OccurrenceRepositoryInterface::class,
            OccurrenceRepository::class
        );

        $this->app->bind(
            DispatchRepositoryInterface::class,
            DispatchRepository::class
        );

        $this->app->bind(
            CommandInboxReadRepositoryInterface::class,
            CommandInboxReadRepository::class
        );
        $this->app->bind(
            CommandInboxWriteRepositoryInterface::class,
            CommandInboxWriteRepository::class
        );
        $this->app->bind(
            OccurrenceListCacheInterface::class,
            OccurrenceListRedisCache::class
        );

        // Domain Services (Regras de Negócio)
        $this->app->singleton(OccurrenceService::class);
        $this->app->singleton(DispatchService::class);
        $this->app->singleton(CommandService::class);

        // Use Cases (Singletons para reuso)
        $this->app->singleton(CreateOccurrenceHandler::class);
        $this->app->singleton(GetOccurrenceHandler::class);
        $this->app->singleton(GetCommandStatusHandler::class);
        $this->app->singleton(StartOccurrenceHandler::class);
        $this->app->singleton(ResolveOccurrenceHandler::class);
        $this->app->singleton(CreateDispatchHandler::class);
        $this->app->singleton(CloseDispatchHandler::class);
        $this->app->singleton(UpdateDispatchStatusHandler::class);
        $this->app->singleton(ListOccurrencesHandler::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

