<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'quantity_before'  => (float) $this->quantity_before,
            'quantity_after'   => (float) $this->quantity_after,
            'quantity_change'  => (float) $this->quantity_change,
            'reference_type'   => $this->reference_type,
            'reference_id'     => $this->reference_id,
            'description'      => $this->description,
            'item'             => new ItemResource($this->whenLoaded('item')),
            'warehouse'        => new WarehouseResource($this->whenLoaded('warehouse')),
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
