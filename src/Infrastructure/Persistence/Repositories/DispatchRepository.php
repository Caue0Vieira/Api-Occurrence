<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Repositories;

use Domain\Dispatch\Collections\DispatchCollection;
use Domain\Dispatch\Entities\Dispatch;
use Domain\Dispatch\Repositories\DispatchRepositoryInterface;
use Domain\Shared\ValueObjects\Uuid;
use Exception;
use Illuminate\Support\Facades\DB;

class DispatchRepository implements DispatchRepositoryInterface
{
    public function findById(Uuid $id): ?Dispatch
    {
        $row = DB::table('dispatches')
            ->select(
                'dispatches.*',
                'dispatch_status.name as status_name',
                'dispatch_status.is_active as status_is_active'
            )
            ->leftJoin('dispatch_status', 'dispatches.status_code', '=', 'dispatch_status.code')
            ->where('dispatches.id', $id->toString())
            ->first();

        return $row ? Dispatch::fromArray((array)$row) : null;
    }

    public function findByOccurrenceId(Uuid $occurrenceId): DispatchCollection
    {
        $rows = DB::table('dispatches')
            ->select(
                'dispatches.*',
                'dispatch_status.name as status_name',
                'dispatch_status.is_active as status_is_active'
            )
            ->leftJoin('dispatch_status', 'dispatches.status_code', '=', 'dispatch_status.code')
            ->where('dispatches.occurrence_id', $occurrenceId->toString())
            ->orderBy('dispatches.created_at', 'desc')
            ->get();

        $dispatches = array_map(/**
         * @throws Exception
         */ static fn($row) => Dispatch::fromArray((array)$row), $rows->all());

        return new DispatchCollection($dispatches);
    }

    public function findActiveByOccurrenceId(Uuid $occurrenceId): DispatchCollection
    {
        $rows = DB::table('dispatches')
            ->select(
                'dispatches.*',
                'dispatch_status.name as status_name',
                'dispatch_status.is_active as status_is_active'
            )
            ->leftJoin('dispatch_status', 'dispatches.status_code', '=', 'dispatch_status.code')
            ->where('dispatches.occurrence_id', $occurrenceId->toString())
            ->where('dispatches.status_code', '!=', 'closed')
            ->orderBy('dispatches.created_at', 'desc')
            ->get();

        $dispatches = array_map(/**
         * @throws Exception
         */ static fn($row) => Dispatch::fromArray((array)$row), $rows->all());

        return new DispatchCollection($dispatches);
    }
}
