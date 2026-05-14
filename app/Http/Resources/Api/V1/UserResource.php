<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'position'     => $this->position,
            'is_active'    => (bool) $this->is_active,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'branch'       => $this->whenLoaded('branch', fn () => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
                'code' => $this->branch->code,
            ]),
            'warehouse'    => $this->whenLoaded('warehouse', fn () => [
                'id'   => $this->warehouse->id,
                'name' => $this->warehouse->name,
                'code' => $this->warehouse->code,
            ]),
            'roles'        => $this->whenLoaded('roles', fn () =>
                $this->roles->pluck('name')
            ),
        ];
    }
}
