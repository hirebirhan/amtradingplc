<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'quantity'    => (float) $this->quantity,
            'unit_type'   => $this->unit_type,
            'notes'       => $this->notes,
            'item'        => new ItemResource($this->whenLoaded('item')),
        ];
    }
}
