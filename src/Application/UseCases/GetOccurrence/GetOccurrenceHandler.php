<?php

declare(strict_types=1);

namespace Application\UseCases\GetOccurrence;

use Domain\Occurrence\Entities\Occurrence;
use Domain\Occurrence\Exceptions\OccurrenceNotFoundException;
use Domain\Occurrence\Services\OccurrenceService;
use Domain\Shared\ValueObjects\Uuid;

final readonly class GetOccurrenceHandler
{
    public function __construct(
        private OccurrenceService $occurrenceService,
    ) {
    }

    public function handle(GetOccurrenceQuery $query): Occurrence
    {
        $occurrence = $this->occurrenceService->findByIdWithDispatches(
            Uuid::fromString($query->occurrenceId)
        );

        if ($occurrence === null) {
            throw OccurrenceNotFoundException::withId($query->occurrenceId);
        }

        return $occurrence;
    }
}

