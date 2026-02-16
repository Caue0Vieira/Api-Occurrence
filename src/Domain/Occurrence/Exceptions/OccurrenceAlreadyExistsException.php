<?php

declare(strict_types=1);

namespace Domain\Occurrence\Exceptions;

use Domain\Shared\Exceptions\DomainException;

class OccurrenceAlreadyExistsException extends DomainException
{
    public static function withExternalId(string $externalId): self
    {
        return new self(
            "Occurrence with external ID '{$externalId}' already exists",
            409
        );
    }
}