<?php

declare(strict_types=1);

namespace Domain\Idempotency\Exceptions;

use RuntimeException;

final class CommandNotFoundException extends RuntimeException
{
    public static function withId(string $commandId): self
    {
        return new self("Command not found: {$commandId}");
    }
}

