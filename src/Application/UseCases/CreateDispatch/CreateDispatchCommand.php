<?php

declare(strict_types=1);

namespace Application\UseCases\CreateDispatch;

final readonly class CreateDispatchCommand
{
    public function __construct(
        public string $occurrenceId,
        public string $resourceCode,
        public string $idempotencyKey,
        public string $source,
    ) {
    }

    public function toPayload(): array
    {
        return [
            'occurrenceId' => $this->occurrenceId,
            'resourceCode' => $this->resourceCode,
        ];
    }
}

