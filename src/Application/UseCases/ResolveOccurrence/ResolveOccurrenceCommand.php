<?php

declare(strict_types=1);

namespace Application\UseCases\ResolveOccurrence;

final readonly class ResolveOccurrenceCommand
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

