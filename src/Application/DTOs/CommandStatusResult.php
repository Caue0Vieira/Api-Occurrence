<?php

declare(strict_types=1);

namespace Application\DTOs;

readonly class CommandStatusResult
{
    public function __construct(
        public string $commandId,
        public string $status,
        public ?array $result,
        public ?string $errorMessage,
        public ?string $processedAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'command_id' => $this->commandId,
            'status' => $this->status,
            'result' => $this->result,
            'error_message' => $this->errorMessage,
            'processed_at' => $this->processedAt,
        ];
    }
}