<?php

namespace App\Http\Requests\Api\V1\Employees;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('update', $this->route('employee')); }

    public function rules(): array
    {
        $employee = $this->route('employee');
        return [
            'first_name'   => ['sometimes', 'string', 'max:255'],
            'last_name'    => ['sometimes', 'string', 'max:255'],
            'email'        => ['nullable', 'email', Rule::unique('employees', 'email')->ignore($employee)],
            'phone'        => ['nullable', 'string', 'max:50'],
            'position'     => ['nullable', 'string', 'max:100'],
            'department'   => ['nullable', 'string', 'max:100'],
            'hire_date'    => ['nullable', 'date'],
            'branch_id'    => ['nullable', 'exists:branches,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'base_salary'  => ['nullable', 'numeric', 'min:0'],
            'allowance'    => ['nullable', 'numeric', 'min:0'],
            'status'       => ['nullable', 'string', 'in:active,inactive,suspended'],
        ];
    }
}
