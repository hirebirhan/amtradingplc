<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'old_price'    => (float) $this->old_price,
            'new_price'    => (float) $this->new_price,
            'old_cost'     => (float) $this->old_cost,
            'new_cost'     => (float) $this->new_cost,
            'change_type'  => $this->change_type,
            'notes'        => $this->notes,
            'item'         => new ItemResource($this->whenLoaded('item')),
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
