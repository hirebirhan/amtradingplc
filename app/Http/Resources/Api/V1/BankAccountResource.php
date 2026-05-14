<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'account_name'   => $this->account_name,
            'account_number' => $this->account_number,
            'bank_name'      => $this->bank_name,
            'branch_name'    => $this->branch_name,
            'swift_code'     => $this->swift_code,
            'currency'       => $this->currency,
            'balance'        => (float) $this->balance,
            'is_active'      => (bool) $this->is_active,
            'is_default'     => (bool) $this->is_default,
            'branch'         => new BranchResource($this->whenLoaded('branch')),
            'warehouse'      => new WarehouseResource($this->whenLoaded('warehouse')),
        ];
    }
}
