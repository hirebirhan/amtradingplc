<?php

namespace App\Http\Requests\Api\V1\Warehouses;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('warehouses.create'); }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255', 'unique:warehouses,name'],
            'code'         => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'address'      => ['nullable', 'string', 'max:500'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'branch_ids'   => ['nullable', 'array'],
            'branch_ids.*' => ['exists:branches,id'],
        ];
    }
}
