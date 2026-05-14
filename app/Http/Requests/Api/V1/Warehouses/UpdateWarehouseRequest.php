<?php

namespace App\Http\Requests\Api\V1\Warehouses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('update', $this->route('warehouse')); }

    public function rules(): array
    {
        $warehouse = $this->route('warehouse');
        return [
            'name'         => ['sometimes', 'string', 'max:255', Rule::unique('warehouses', 'name')->ignore($warehouse)],
            'code'         => ['sometimes', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($warehouse)],
            'address'      => ['nullable', 'string', 'max:500'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'branch_ids'   => ['nullable', 'array'],
            'branch_ids.*' => ['exists:branches,id'],
        ];
    }
}
