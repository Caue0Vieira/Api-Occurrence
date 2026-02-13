<?php

declare(strict_types=1);

namespace Domain\Dispatch\Repositories;

use Domain\Dispatch\Collections\DispatchCollection;
use Domain\Dispatch\Entities\Dispatch;
use Domain\Shared\ValueObjects\Uuid;

interface DispatchRepositoryInterface
{
    public function findById(Uuid $id): ?Dispatch;

    /**
     * @param Uuid $occurrenceId
     * @return DispatchCollection
     */
    public function findByOccurrenceId(Uuid $occurrenceId): DispatchCollection;

    /**
     * @param Uuid $occurrenceId
     * @return DispatchCollection
     */
    public function findActiveByOccurrenceId(Uuid $occurrenceId): DispatchCollection;
}

