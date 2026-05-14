<?php

namespace App\Http\Requests\Api\V1\Items;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('items.create'); }

    public function rules(): array
    {
        return [
            'name'                   => ['required', 'string', 'max:255'],
            'sku'                    => ['nullable', 'string', 'max:100', 'unique:items,sku'],
            'barcode'                => ['nullable', 'string', 'max:100', 'unique:items,barcode'],
            'category_id'            => ['required', 'exists:categories,id'],
            'brand'                  => ['nullable', 'string', 'max:255'],
            'description'            => ['nullable', 'string'],
            'unit'                   => ['nullable', 'string', 'max:50'],
            'item_unit'              => ['nullable', 'string', 'max:50'],
            'unit_quantity'          => ['nullable', 'integer', 'min:1'],
            'reorder_level'          => ['nullable', 'integer', 'min:0'],
            'cost_price'             => ['nullable', 'numeric', 'min:0'],
            'selling_price'          => ['nullable', 'numeric', 'min:0'],
            'cost_price_per_unit'    => ['nullable', 'numeric', 'min:0'],
            'selling_price_per_unit' => ['nullable', 'numeric', 'min:0'],
            'is_active'              => ['boolean'],
        ];
    }
}
