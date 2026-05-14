<?php

namespace App\Http\Requests\Api\V1\Categories;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('update', $this->route('category')); }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'code'        => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'parent_id'   => ['nullable', 'exists:categories,id'],
            'is_active'   => ['boolean'],
        ];
    }
}
