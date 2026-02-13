<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource: Dispatch
 */
class DispatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $dispatch = $this->resource;

        return [
            'id' => $dispatch->id,
            'occurrence_id' => $dispatch->occurrenceId,
            'resource_code' => $dispatch->resourceCode,
            'status' => $dispatch->status,
            'created_at' => $dispatch->createdAt,
            'updated_at' => $dispatch->updatedAt,
        ];
    }
}

