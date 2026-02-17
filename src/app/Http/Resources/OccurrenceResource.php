<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Domain\Occurrence\Entities\Occurrence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OccurrenceResource extends JsonResource
{
    public $resource;

    public function toArray(Request $request): array
    {
        $occurrence = $this->resource;

        return [
            'data' => [
                'id' => $occurrence->id()->toString(),
                'external_id' => $occurrence->externalId(),
                'type_code' => $occurrence->typeCode(),
                'type_name' => $occurrence->typeName(),
                'type_category' => $occurrence->typeCategory(),
                'status_code' => $occurrence->statusCode(),
                'status_name' => $occurrence->statusName(),
                'description' => $occurrence->description(),
                'reported_at' => $occurrence->reportedAt()->format(DATE_ATOM),
                'created_at' => $occurrence->createdAt()->format(DATE_ATOM),
                'updated_at' => $occurrence->updatedAt()->format(DATE_ATOM),
                'dispatches' => array_map(static fn ($d) => [
                    'id' => $d->id()->toString(),
                    'resource_code' => $d->resourceCode(),
                    'status_code' => $d->statusCode(),
                    'status_name' => $d->statusName(),
                    'created_at' => $d->createdAt()->format(DATE_ATOM),
                ], $occurrence->dispatches()),
            ],
        ];
    }
}
