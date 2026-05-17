<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Credits;

use Illuminate\Foundation\Http\FormRequest;

class ClosingOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('credits.edit');
    }

    public function rules(): array
    {
        return [
            'negotiated_prices'          => ['required', 'array', 'min:1'],
            'negotiated_prices.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'negotiated_prices.*.price'   => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'negotiated_prices.required'          => 'At least one negotiated price is required.',
            'negotiated_prices.*.item_id.exists'  => 'Item :input does not exist.',
            'negotiated_prices.*.price.min'       => 'Negotiated price must be 0 or greater.',
        ];
    }
}
