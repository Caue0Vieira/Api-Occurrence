<?php

declare(strict_types=1);

namespace Domain\Idempotency\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exceção quando Idempotency-Key é reutilizada para o mesmo comando (idempotência)
 */
class DuplicateCommandException extends RuntimeException
{
    public function __construct(
        string $message = 'Comando duplicado - esta requisição já foi processada anteriormente',
        int $code = 409,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function withCommandId(string $commandId): self
    {
        return new self(
            "Comando duplicado - esta requisição já foi processada anteriormente (command_id: {$commandId})"
        );
    }
}

