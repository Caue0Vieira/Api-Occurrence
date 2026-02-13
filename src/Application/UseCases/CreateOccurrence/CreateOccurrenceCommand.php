<?php

declare(strict_types=1);

namespace Application\UseCases\CreateOccurrence;

final readonly class CreateOccurrenceCommand
{
    public function __construct(
        public string $externalId,
        public string $type,
        public string $description,
        public string $reportedAt,
        public string $idempotencyKey,
        public string $source,
    ) {
    }

    public function toPayload(): array
    {
        return [
            'externalId' => $this->externalId,
            'type' => $this->type,
            'description' => $this->description,
            'reportedAt' => $this->reportedAt,
        ];
    }
}

