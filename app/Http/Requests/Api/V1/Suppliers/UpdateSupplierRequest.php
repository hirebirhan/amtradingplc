<?php

namespace App\Http\Requests\Api\V1\Suppliers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('update', $this->route('supplier')); }

    public function rules(): array
    {
        $supplier = $this->route('supplier');
        return [
            'name'       => ['sometimes', 'string', 'max:255'],
            'email'      => ['nullable', 'email', Rule::unique('suppliers', 'email')->ignore($supplier)],
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
