<?php

namespace App\Http\Requests\Api\V1\Customers;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('customers.create'); }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['nullable', 'email', 'max:255', 'unique:customers,email'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'address'       => ['nullable', 'string', 'max:500'],
            'city'          => ['nullable', 'string', 'max:100'],
            'country'       => ['nullable', 'string', 'max:100'],
            'customer_type' => ['nullable', 'string', 'max:50'],
            'credit_limit'  => ['nullable', 'numeric', 'min:0'],
            'branch_id'     => ['nullable', 'exists:branches,id'],
            'is_active'     => ['boolean'],
            'notes'         => ['nullable', 'string'],
        ];
    }
}
