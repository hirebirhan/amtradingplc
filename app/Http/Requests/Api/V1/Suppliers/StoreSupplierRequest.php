<?php

namespace App\Http\Requests\Api\V1\Suppliers;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('suppliers.create'); }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['nullable', 'email', 'max:255', 'unique:suppliers,email'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'address'    => ['nullable', 'string', 'max:500'],
            'city'       => ['nullable', 'string', 'max:100'],
            'country'    => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'branch_id'  => ['nullable', 'exists:branches,id'],
            'is_active'  => ['boolean'],
            'notes'      => ['nullable', 'string'],
        ];
    }
}
