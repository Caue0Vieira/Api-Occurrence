<?php

declare(strict_types=1);

namespace Application\DTOs;

readonly class ListOccurrencesFilter
{
    public function __construct(
        public ?string $status,
        public ?string $type,
        public ?string $dateFrom,
        public ?string $dateTo,
        public int $limit,
        public int $page,
    ) {
    }
}


