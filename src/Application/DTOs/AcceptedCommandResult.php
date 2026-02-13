<?php

declare(strict_types=1);

namespace Application\DTOs;

final readonly class AcceptedCommandResult
{
    public function __construct(
        public string $commandId,
        public string $status = 'accepted',
    ) {
    }

    public function toArray(): array
    {
        return [
            'commandId' => $this->commandId,
            'status' => $this->status,
        ];
    }
}

