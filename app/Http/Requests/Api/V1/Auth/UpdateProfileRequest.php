<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'phone'    => ['sometimes', 'nullable', 'string', 'max:50'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ];
    }
}
