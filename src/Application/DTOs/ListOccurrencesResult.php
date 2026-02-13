<?php

declare(strict_types=1);

namespace Application\DTOs;

use Domain\Occurrence\Entities\Occurrence;

readonly class ListOccurrencesResult
{
    /**
     * @param Occurrence[] $occurrences
     */
    public function __construct(
        public array $occurrences,
        public int $total,
        public int $page,
        public int $limit,
    ) {
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(static fn (Occurrence $occurrence) => $occurrence->toArray(), $this->occurrences),
            'meta' => [
                'total' => $this->total,
                'page' => $this->page,
                'limit' => $this->limit,
                'pages' => (int) ceil($this->total / max(1, $this->limit)),
            ],
        ];
    }
}

