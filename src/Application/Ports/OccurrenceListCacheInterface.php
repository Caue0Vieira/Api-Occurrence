<?php

declare(strict_types=1);

namespace Application\Ports;

use Application\DTOs\ListOccurrencesResult;
use Application\UseCases\ListOccurrences\ListOccurrencesQuery;

interface OccurrenceListCacheInterface
{
    public function get(ListOccurrencesQuery $query): ?ListOccurrencesResult;

    public function put(ListOccurrencesQuery $query, ListOccurrencesResult $result): void;
}


