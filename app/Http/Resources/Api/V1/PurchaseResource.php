<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'reference_no'       => $this->reference_no,
            'status'             => $this->status,
            'payment_status'     => $this->payment_status,
            'payment_method'     => $this->payment_method,
            'purchase_date'      => $this->purchase_date?->toDateString(),
            'amounts' => [
                'total'    => (float) $this->total_amount,
                'paid'     => (float) $this->paid_amount,
                'due'      => (float) $this->due_amount,
                'advance'  => (float) $this->advance_amount,
                'tax'      => (float) $this->tax,
                'discount' => (float) $this->discount,
            ],
            'transaction_number' => $this->transaction_number,
            'notes'              => $this->notes,
            'supplier'           => new SupplierResource($this->whenLoaded('supplier')),
            'branch'             => new BranchResource($this->whenLoaded('branch')),
            'warehouse'          => new WarehouseResource($this->whenLoaded('warehouse')),
            'items'              => PurchaseItemResource::collection($this->whenLoaded('items')),
            'created_at'         => $this->created_at?->toISOString(),
        ];
    }
}
