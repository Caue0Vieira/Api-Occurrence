<?php

declare(strict_types=1);

namespace Infrastructure\Cache;

use Application\DTOs\ListOccurrencesFilter;
use Application\DTOs\ListOccurrencesResult;
use Application\Ports\OccurrenceListCacheInterface;
use Domain\Occurrence\Entities\Occurrence;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use JsonException;
use Throwable;

class OccurrenceListRedisCache implements OccurrenceListCacheInterface
{
    public function get(ListOccurrencesFilter $filter): ?ListOccurrencesResult
    {
        if (!config('api.occurrences_cache.enabled', true)) {
            return null;
        }

        try {
            $connection = Redis::connection(config('api.occurrences_cache.redis_connection', 'cache'));
            $version = (int) ($connection->get($this->versionKey()) ?? 1);
            $cacheKey = $this->cacheKey($filter, $version);
            $cached = $connection->get($cacheKey);

            if ($cached === null) {
                Log::debug('[Cache] Occurrence list cache miss', [
                    'cacheKey' => $cacheKey,
                ]);
                return null;
            }
            Log::debug('[Cache] Occurrence list cache hit', [
                'cacheKey' => $cacheKey,
            ]);

            $payload = json_decode((string) $cached, true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($payload) || !isset($payload['data'], $payload['meta']) || !is_array($payload['data']) || !is_array($payload['meta'])) {
                return null;
            }

            return new ListOccurrencesResult(
                occurrences: array_map(
                    static fn (array $occurrence) => Occurrence::fromArray($occurrence),
                    $payload['data']
                ),
                total: (int) ($payload['meta']['total'] ?? 0),
                page: (int) ($payload['meta']['page'] ?? 1),
                limit: (int) ($payload['meta']['limit'] ?? max(1, $filter->limit)),
            );
        } catch (Throwable $exception) {
            Log::warning('[Cache] Failed to read occurrence list cache, falling back to database', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function put(ListOccurrencesFilter $filter, ListOccurrencesResult $result): void
    {
        if (!config('api.occurrences_cache.enabled', true)) {
            return;
        }

        try {
            $connection = Redis::connection(config('api.occurrences_cache.redis_connection', 'cache'));
            $version = (int) ($connection->get($this->versionKey()) ?? 1);
            $cacheKey = $this->cacheKey($filter, $version);
            $ttl = (int) config('api.occurrences_cache.ttl_seconds', 60);
            $payload = json_encode($result->toArray(), JSON_THROW_ON_ERROR);

            $connection->setex($cacheKey, max(1, $ttl), $payload);
            Log::debug('[Cache] Occurrence list cache updated', [
                'cacheKey' => $cacheKey,
                'ttl' => $ttl,
            ]);
        } catch (Throwable $exception) {
            Log::warning('[Cache] Failed to write occurrence list cache', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function versionKey(): string
    {
        $prefix = (string) config('api.occurrences_cache.key_prefix', 'occurrences:list');

        return "{$prefix}:version";
    }

    private function cacheKey(ListOccurrencesFilter $filter, int $version): string
    {
        $prefix = (string) config('api.occurrences_cache.key_prefix', 'occurrences:list');

        try {
            $signature = json_encode([
                'status' => $filter->status,
                'type' => $filter->type,
                'limit' => $filter->limit,
                'page' => $filter->page,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $signature = sprintf('%s|%s|%d|%d', $filter->status, $filter->type, $filter->limit, $filter->page);
        }

        return sprintf('%s:v%d:%s', $prefix, $version, sha1($signature));
    }
}


