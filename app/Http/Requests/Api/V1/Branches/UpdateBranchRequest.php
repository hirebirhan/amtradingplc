<?php

namespace App\Http\Requests\Api\V1\Branches;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('update', $this->route('branch')); }

    public function rules(): array
    {
        $branch = $this->route('branch');
        return [
            'name'      => ['sometimes', 'string', 'max:255', Rule::unique('branches', 'name')->ignore($branch)],
            'code'      => ['sometimes', 'string', 'max:50', Rule::unique('branches', 'code')->ignore($branch)],
            'address'   => ['nullable', 'string', 'max:500'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
