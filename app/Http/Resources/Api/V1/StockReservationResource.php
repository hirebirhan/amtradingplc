<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'location_type'  => $this->location_type,
            'location_id'    => $this->location_id,
            'location_name'  => $this->location_name,
            'quantity'       => (float) $this->quantity,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'expires_at'     => $this->expires_at?->toISOString(),
            'is_expired'     => $this->isExpired(),
            'item'           => new ItemResource($this->whenLoaded('item')),
            'creator'        => new UserResource($this->whenLoaded('creator')),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
