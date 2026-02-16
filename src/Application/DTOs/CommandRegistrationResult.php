<?php

declare(strict_types=1);

namespace Application\DTOs;

readonly class CommandRegistrationResult
{
    public function __construct(
        public string $commandId,
        public string $status,
        public bool $shouldDispatch,
        public bool $isNew = false,
    ) {
    }
}