<?php

declare(strict_types=1);

namespace Application\UseCases\GetOccurrence;

/**
 * Query: Buscar Ocorrência por ID
 *
 * DTO para buscar uma ocorrência específica com seus despachos.
 */
final readonly class GetOccurrenceQuery
{
    public function __construct(
        public string $occurrenceId,
    ) {
    }
}

