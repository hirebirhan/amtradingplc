<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'code'         => $this->code,
            'address'      => $this->address,
            'manager_name' => $this->manager_name,
            'phone'        => $this->phone,
            'branches'     => BranchResource::collection($this->whenLoaded('branches')),
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
