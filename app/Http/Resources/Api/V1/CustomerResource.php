<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'address'       => $this->address,
            'city'          => $this->city,
            'country'       => $this->country,
            'customer_type' => $this->customer_type,
            'credit_limit'  => (float) $this->credit_limit,
            'balance'       => (float) $this->balance,
            'is_active'     => (bool) $this->is_active,
            'branch'        => new BranchResource($this->whenLoaded('branch')),
            'created_at'    => $this->created_at?->toISOString(),
        ];
    }
}
