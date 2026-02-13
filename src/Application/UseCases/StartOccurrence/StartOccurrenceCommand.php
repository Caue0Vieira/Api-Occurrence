<?php

declare(strict_types=1);

namespace Application\UseCases\StartOccurrence;

final readonly class StartOccurrenceCommand
{
    public function __construct(
        public string $occurrenceId,
        public string $idempotencyKey,
        public string $source,
    ) {
    }

    public function toPayload(): array
    {
        return ['occurrenceId' => $this->occurrenceId];
    }
}

