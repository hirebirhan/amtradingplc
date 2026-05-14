<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'amount'         => (float) $this->amount,
            'kind'           => $this->kind,
            'payment_method' => $this->payment_method,
            'reference_no'   => $this->reference_no,
            'payment_date'   => $this->payment_date?->toDateString(),
            'notes'          => $this->notes,
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
