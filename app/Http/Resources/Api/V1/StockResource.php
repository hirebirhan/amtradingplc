<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'quantity'            => (float) $this->quantity,
            'piece_count'         => (int) $this->piece_count,
            'total_units'         => (float) $this->total_units,
            'current_piece_units' => (float) $this->current_piece_units,
            'item'                => new ItemResource($this->whenLoaded('item')),
            'warehouse'           => new WarehouseResource($this->whenLoaded('warehouse')),
            'branch'              => new BranchResource($this->whenLoaded('branch')),
        ];
    }
}
