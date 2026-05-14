<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'employee_id'        => $this->employee_id,
            'first_name'         => $this->first_name,
            'last_name'          => $this->last_name,
            'email'              => $this->email,
            'phone'              => $this->phone,
            'position'           => $this->position,
            'department'         => $this->department,
            'hire_date'          => $this->hire_date?->toDateString(),
            'status'             => $this->status,
            'base_salary'        => (float) $this->base_salary,
            'allowance'          => (float) $this->allowance,
            'branch'             => new BranchResource($this->whenLoaded('branch')),
            'warehouse'          => new WarehouseResource($this->whenLoaded('warehouse')),
        ];
    }
}
