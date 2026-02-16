<?php

declare(strict_types=1);

namespace Application\DTOs;

readonly class AcceptedCommandResult
{
    public function __construct(
        public string $commandId,
        public string $status,
    ) {
    }

    public function toArray(): array
    {
        return [
            'command_id' => $this->commandId,
            'status' => $this->status,
        ];
    }
}