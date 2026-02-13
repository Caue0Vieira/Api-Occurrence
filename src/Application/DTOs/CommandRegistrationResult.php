<?php

declare(strict_types=1);

namespace Application\DTOs;

final readonly class CommandRegistrationResult
{
    public function __construct(
        public string $commandId,
        public bool $shouldDispatch,
    ) {
    }
}

