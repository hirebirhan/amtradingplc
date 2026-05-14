<?php

namespace App\Http\Requests\Api\V1\Transfers;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can('transfers.create')) {
            return false;
        }

        // Branch managers can only initiate from their own branch
        $sourceType = $this->input('source_type', 'branch');
        $sourceId   = $this->integer('source_id');

        if ($sourceType === 'branch' && $sourceId) {
            return $this->user()->hasAccessToBranch($sourceId);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'source_type'      => ['required', 'in:branch,warehouse'],
            'source_id'        => ['required', 'integer', 'min:1'],
            'destination_type' => ['required', 'in:branch,warehouse'],
            'destination_id'   => ['required', 'integer', 'min:1'],
            'note'             => ['nullable', 'string'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.item_id'  => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_type' => ['nullable', 'in:piece,unit'],
        ];
    }
}
