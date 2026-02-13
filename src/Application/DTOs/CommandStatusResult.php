<?php

declare(strict_types=1);

namespace Application\DTOs;

final readonly class CommandStatusResult
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
            'commandId' => $this->commandId,
            'status' => $this->status,
            'result' => $this->result,
            'errorMessage' => $this->errorMessage,
            'processedAt' => $this->processedAt,
        ];
    }
}

