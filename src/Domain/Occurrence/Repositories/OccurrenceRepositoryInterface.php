<?php

declare(strict_types=1);

namespace Domain\Occurrence\Repositories;

use Domain\Occurrence\Collections\OccurrenceStatusCollection;
use Domain\Occurrence\Collections\OccurrenceTypeCollection;
use Domain\Occurrence\Entities\Occurrence;
use Domain\Shared\ValueObjects\Uuid;
use Illuminate\Pagination\LengthAwarePaginator;

interface OccurrenceRepositoryInterface
{
    public function findById(Uuid $id): ?Occurrence;

    public function listOccurrences(?string $statusCode = null, ?string $typeCode = null, int $perPage = 50, int $page = 1): LengthAwarePaginator;

    public function findByIdWithDispatches(Uuid $id): ?Occurrence;

    public function findOccurrenceTypes(): OccurrenceTypeCollection;

    public function findOccurrenceStatuses(): OccurrenceStatusCollection;
}
