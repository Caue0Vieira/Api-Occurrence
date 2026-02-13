<?php

declare(strict_types=1);

namespace Application\UseCases\ListOccurrences;

use Application\DTOs\ListOccurrencesResult;
use Application\Ports\OccurrenceListCacheInterface;
use Domain\Occurrence\Services\OccurrenceService;

readonly class ListOccurrencesHandler
{
    public function __construct(
        private OccurrenceService $occurrenceService,
        private OccurrenceListCacheInterface $occurrenceListCache,
    ) {
    }

    public function handle(ListOccurrencesQuery $query): ListOccurrencesResult
    {
        $perPage = max(1, min($query->limit, 200));
        $page = max(1, $query->page);
        $normalizedQuery = new ListOccurrencesQuery(
            status: $query->status,
            type: $query->type,
            limit: $perPage,
            page: $page,
        );

        $cachedResult = $this->occurrenceListCache->get($normalizedQuery);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        $paginator = $this->occurrenceService->listOccurrences(
            statusCode: $query->status,
            typeCode: $query->type,
            perPage: $perPage,
            page: $page,
        );

        $result = new ListOccurrencesResult(
            occurrences: $paginator->items(),
            total: $paginator->total(),
            page: $paginator->currentPage(),
            limit: $paginator->perPage(),
        );

        $this->occurrenceListCache->put($normalizedQuery, $result);

        return $result;
    }
}

