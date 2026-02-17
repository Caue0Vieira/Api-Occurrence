<?php

declare(strict_types=1);

namespace Domain\Shared\Exceptions;

use RuntimeException;
use Throwable;

class DomainException extends RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 500,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

