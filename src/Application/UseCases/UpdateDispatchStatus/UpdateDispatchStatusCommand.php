<?php

declare(strict_types=1);

namespace Application\UseCases\UpdateDispatchStatus;

final readonly class UpdateDispatchStatusCommand
{
    public function __construct(
        public string $dispatchId,
        public string $statusCode,
        public string $source,
        public string $idempotencyKey = '',
    ) {
    }

    public function toPayload(): array
    {
        return [
            'dispatchId' => $this->dispatchId,
            'statusCode' => $this->statusCode,
        ];
    }
}

