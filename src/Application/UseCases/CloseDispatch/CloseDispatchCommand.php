<?php

declare(strict_types=1);

namespace Application\UseCases\CloseDispatch;

final readonly class CloseDispatchCommand
{
    public function __construct(
        public string $dispatchId,
        public string $idempotencyKey,
        public string $source,
    ) {
    }

    public function toPayload(): array
    {
        return ['dispatchId' => $this->dispatchId];
    }
}

