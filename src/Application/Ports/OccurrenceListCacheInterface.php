<?php

declare(strict_types=1);

namespace Application\Ports;

use Application\DTOs\ListOccurrencesFilter;
use Application\DTOs\ListOccurrencesResult;

interface OccurrenceListCacheInterface
{
    public function get(ListOccurrencesFilter $filter): ?ListOccurrencesResult;

    public function put(ListOccurrencesFilter $filter, ListOccurrencesResult $result): void;
}


