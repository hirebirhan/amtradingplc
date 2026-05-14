<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'quantity' => (float) $this->quantity,
            'unit_cost' => (float) $this->unit_cost,
            'subtotal'  => (float) $this->subtotal,
            'discount'  => (float) $this->discount,
            'notes'     => $this->notes,
            'item'      => new ItemResource($this->whenLoaded('item')),
        ];
    }
}
