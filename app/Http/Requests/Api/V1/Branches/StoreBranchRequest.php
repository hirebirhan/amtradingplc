<?php

namespace App\Http\Requests\Api\V1\Branches;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('branches.create'); }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255', 'unique:branches,name'],
            'code'      => ['required', 'string', 'max:50', 'unique:branches,code'],
            'address'   => ['nullable', 'string', 'max:500'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
