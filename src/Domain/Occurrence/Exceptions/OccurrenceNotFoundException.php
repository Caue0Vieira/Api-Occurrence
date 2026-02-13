<?php

declare(strict_types=1);

namespace Domain\Occurrence\Exceptions;

use Domain\Shared\Exceptions\DomainException;

final class OccurrenceNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self("Occurrence not found with ID: {$id}", 404);
    }

    public static function withExternalId(string $externalId): self
    {
        return new self("Occurrence not found with external ID: {$externalId}", 404);
    }
}

