<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'reference_no'   => $this->reference_no,
            'category'       => $this->category,
            'amount'         => (float) $this->amount,
            'payment_method' => $this->payment_method,
            'expense_date'   => $this->expense_date?->toDateString(),
            'note'           => $this->note,
            'is_recurring'   => (bool) $this->is_recurring,
            'branch'         => new BranchResource($this->whenLoaded('branch')),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
