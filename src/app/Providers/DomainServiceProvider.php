<?php

declare(strict_types=1);

namespace App\Providers;

use Application\Ports\OccurrenceListCacheInterface;
use Domain\Idempotency\Repositories\CommandInboxReadRepositoryInterface;
use Domain\Idempotency\Repositories\CommandInboxWriteRepositoryInterface;
use Domain\Idempotency\Services\CommandService;
use Domain\Outbox\Repositories\OutboxWriteRepositoryInterface;
use Domain\Dispatch\Repositories\DispatchRepositoryInterface;
use Domain\Dispatch\Service\DispatchService;
use Domain\Occurrence\Repositories\OccurrenceRepositoryInterface;
use Domain\Occurrence\Services\OccurrenceService;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Persistence\Repositories\CommandInboxReadRepository;
use Infrastructure\Persistence\Repositories\CommandInboxWriteRepository;
use Infrastructure\Persistence\Repositories\DispatchRepository;
use Infrastructure\Persistence\Repositories\OccurrenceRepository;
use Infrastructure\Persistence\Repositories\OutboxWriteRepository;
use Infrastructure\Cache\OccurrenceListRedisCache;

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
            OutboxWriteRepositoryInterface::class,
            OutboxWriteRepository::class
        );
        $this->app->bind(
            OccurrenceListCacheInterface::class,
            OccurrenceListRedisCache::class
        );

        // Domain Services (Regras de Negócio)
        $this->app->singleton(OccurrenceService::class);
        $this->app->singleton(DispatchService::class);
        $this->app->singleton(CommandService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

