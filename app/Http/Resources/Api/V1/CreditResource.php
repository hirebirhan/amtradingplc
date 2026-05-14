<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'reference_no'   => $this->reference_no,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'credit_type'    => $this->credit_type,
            'status'         => $this->status,
            'amounts' => [
                'total'   => (float) $this->amount,
                'paid'    => (float) $this->paid_amount,
                'balance' => (float) $this->balance,
            ],
            'description' => $this->description,
            'credit_date' => $this->credit_date?->toDateString(),
            'due_date'    => $this->due_date?->toDateString(),
            'customer'    => new CustomerResource($this->whenLoaded('customer')),
            'supplier'    => new SupplierResource($this->whenLoaded('supplier')),
        ];
    }
}
