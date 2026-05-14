<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'quantity'    => (float) $this->quantity,
            'unit_price'  => (float) $this->unit_price,
            'sale_method' => $this->sale_method,
            'discount'    => (float) $this->discount,
            'subtotal'    => (float) $this->subtotal,
            'notes'       => $this->notes,
            'item'        => new ItemResource($this->whenLoaded('item')),
        ];
    }
}
