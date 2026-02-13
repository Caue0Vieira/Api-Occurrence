<?php

declare(strict_types=1);

namespace Domain\Idempotency\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exceção quando Idempotency-Key é reutilizada com payload diferente
 * ou em contexto inválido.
 */
class IdempotencyConflictException extends RuntimeException
{
    public function __construct(
        string     $message = 'Idempotency-Key reutilizada com payload diferente para o mesmo escopo',
        int        $code = 409,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function withPayloadMismatch(string $idempotencyKey, string $scopeKey): self
    {
        return new self(
            "Idempotency-Key '$idempotencyKey' já foi utilizada com payload diferente para o escopo '$scopeKey'"
        );
    }

}

