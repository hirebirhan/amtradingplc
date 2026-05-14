<?php

namespace App\Http\Requests\Api\V1\Items;

use Illuminate\Foundation\Http\FormRequest;

class ImportItemsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('items.create'); }

    public function rules(): array
    {
        return [
            'file'        => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ];
    }
}
