<?php

namespace App\Http\Requests\Api\V1\Users;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('users.create'); }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'position'     => ['nullable', 'string', 'max:100'],
            'branch_id'    => ['nullable', 'exists:branches,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'is_active'    => ['boolean'],
            'roles'        => ['nullable', 'array'],
            'roles.*'      => ['string', 'exists:roles,name'],
        ];
    }
}
