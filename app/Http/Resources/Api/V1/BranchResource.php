<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'code'       => $this->code,
            'address'    => $this->address,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'is_active'  => (bool) $this->is_active,
            'warehouses' => WarehouseResource::collection($this->whenLoaded('warehouses')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
