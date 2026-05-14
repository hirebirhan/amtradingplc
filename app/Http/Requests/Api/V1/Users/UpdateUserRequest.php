<?php

namespace App\Http\Requests\Api\V1\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('update', $this->route('user')); }

    public function rules(): array
    {
        $user = $this->route('user');
        return [
            'name'         => ['sometimes', 'string', 'max:255'],
            'email'        => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user)],
            'password'     => ['nullable', 'string', 'min:8'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'position'     => ['nullable', 'string', 'max:100'],
            'branch_id'    => ['nullable', 'exists:branches,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'is_active'    => ['boolean'],
        ];
    }
}
