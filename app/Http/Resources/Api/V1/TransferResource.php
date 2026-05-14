<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'reference_code' => $this->reference_code,
            'status'         => $this->status,
            'source_type'    => $this->source_type,
            'source_id'      => $this->source_id,
            'source_name'    => $this->source_location_name,
            'destination_type' => $this->destination_type,
            'destination_id'   => $this->destination_id,
            'destination_name' => $this->destination_location_name,
            'note'           => $this->note,
            'date_initiated' => $this->date_initiated?->toISOString(),
            'approved_at'    => $this->approved_at?->toISOString(),
            'items'          => TransferItemResource::collection($this->whenLoaded('items')),
            'creator'        => new UserResource($this->whenLoaded('creator')),
            'approved_by'    => new UserResource($this->whenLoaded('approvedBy')),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
