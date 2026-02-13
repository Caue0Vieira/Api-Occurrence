<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Repositories;

use Domain\Occurrence\Collections\OccurrenceStatusCollection;
use Domain\Occurrence\Collections\OccurrenceTypeCollection;
use Domain\Occurrence\Entities\Occurrence;
use Domain\Occurrence\Entities\OccurrenceStatus;
use Domain\Occurrence\Entities\OccurrenceType;
use Domain\Occurrence\Repositories\OccurrenceRepositoryInterface;
use Domain\Shared\ValueObjects\Uuid;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OccurrenceRepository implements OccurrenceRepositoryInterface
{
    public function findById(Uuid $id): ?Occurrence
    {
        $row = DB::table('occurrences')
            ->select(
                'occurrences.*',
                'occurrence_types.name as type_name',
                'occurrence_types.category as type_category',
                'occurrence_status.name as status_name',
                'occurrence_status.is_final as status_is_final'
            )
            ->leftJoin('occurrence_types', 'occurrences.type_code', '=', 'occurrence_types.code')
            ->leftJoin('occurrence_status', 'occurrences.status_code', '=', 'occurrence_status.code')
            ->where('occurrences.id', $id->toString())
            ->first();

        return $row ? Occurrence::fromArray((array) $row) : null;
    }

    public function listOccurrences(
        ?string $statusCode = null,
        ?string $typeCode = null,
        int $perPage = 50,
        int $page = 1
    ): LengthAwarePaginator {
        $baseQuery = DB::table('occurrences');

        if ($statusCode !== null) {
            $baseQuery->where('occurrences.status_code', $statusCode);
        }

        if ($typeCode !== null) {
            $baseQuery->where('occurrences.type_code', $typeCode);
        }

        // Contagem total sem JOINs para melhor performance
        $total = (clone $baseQuery)->count();

        // Query com JOINs para buscar os dados
        $query = $baseQuery
            ->select(
                'occurrences.*',
                'occurrence_types.name as type_name',
                'occurrence_types.category as type_category',
                'occurrence_status.name as status_name',
                'occurrence_status.is_final as status_is_final'
            )
            ->leftJoin('occurrence_types', 'occurrences.type_code', '=', 'occurrence_types.code')
            ->leftJoin('occurrence_status', 'occurrences.status_code', '=', 'occurrence_status.code')
            ->orderBy('occurrences.created_at', 'desc')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage);

        $items = $query
            ->get()
            ->map(fn ($row) => Occurrence::fromArray((array) $row))
            ->all();

        $path = request()?->url() ?? '/';
        $queryParams = request()?->query() ?? [];

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $path,
                'query' => $queryParams,
            ]
        );
    }

    public function findByIdWithDispatches(Uuid $id): ?Occurrence
    {
        $occ = $this->findById($id);
        if (!$occ) {
            return null;
        }

        $dispatchRows = DB::table('dispatches')
            ->select(
                'dispatches.*',
                'dispatch_status.name as status_name',
                'dispatch_status.is_active as status_is_active'
            )
            ->leftJoin('dispatch_status', 'dispatches.status_code', '=', 'dispatch_status.code')
            ->where('dispatches.occurrence_id', $id->toString())
            ->orderBy('dispatches.created_at', 'desc')
            ->get();

        $data = $occ->toArray();
        $data['dispatches'] = array_map(fn ($r) => (array) $r, $dispatchRows->all());

        return Occurrence::fromArray($data);
    }

    public function findOccurrenceTypes(): OccurrenceTypeCollection
    {
        $rows = DB::table('occurrence_types')
            ->select('code', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $types = array_map(
            static fn($row) => OccurrenceType::fromArray([...(array)$row]),
            $rows->all()
        );

        return new OccurrenceTypeCollection($types);
    }

    public function findOccurrenceStatuses(): OccurrenceStatusCollection
    {
        $rows = DB::table('occurrence_status')
            ->select('code', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $status = array_map(
            static fn($row) => OccurrenceStatus::fromArray([...(array)$row]),
            $rows->all()
        );

        return new OccurrenceStatusCollection($status);
    }
}
