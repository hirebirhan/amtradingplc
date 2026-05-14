<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'reference_no'        => $this->reference_no,
            'status'              => $this->status,
            'payment_status'      => $this->payment_status,
            'payment_method'      => $this->payment_method,
            'is_walking_customer' => (bool) $this->is_walking_customer,
            'sale_date'           => $this->sale_date?->toDateString(),
            'amounts' => [
                'total'    => (float) $this->total_amount,
                'paid'     => (float) $this->paid_amount,
                'due'      => (float) $this->due_amount,
                'advance'  => (float) $this->advance_amount,
                'tax'      => (float) $this->tax,
                'discount' => (float) $this->discount,
                'shipping' => (float) $this->shipping,
            ],
            'transaction_number' => $this->transaction_number,
            'notes'              => $this->notes,
            'customer'           => new CustomerResource($this->whenLoaded('customer')),
            'branch'             => new BranchResource($this->whenLoaded('branch')),
            'warehouse'          => new WarehouseResource($this->whenLoaded('warehouse')),
            'items'              => SaleItemResource::collection($this->whenLoaded('items')),
            'credit'             => new CreditResource($this->whenLoaded('credit')),
            'created_at'         => $this->created_at?->toISOString(),
        ];
    }
}
