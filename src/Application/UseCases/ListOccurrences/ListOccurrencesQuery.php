<?php

declare(strict_types=1);

namespace Application\UseCases\ListOccurrences;

final readonly class ListOccurrencesQuery
{
    public function __construct(
        public ?string $status,
        public ?string $type,
        public int $limit = 50,
        public int $page = 1,
    ) {
    }
}

