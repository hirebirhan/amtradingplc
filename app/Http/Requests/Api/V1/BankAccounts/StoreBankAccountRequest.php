<?php

namespace App\Http\Requests\Api\V1\BankAccounts;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('bank-accounts.create'); }

    public function rules(): array
    {
        return [
            'account_name'   => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100', 'unique:bank_accounts,account_number'],
            'bank_name'      => ['required', 'string', 'max:255'],
            'branch_name'    => ['nullable', 'string', 'max:255'],
            'swift_code'     => ['nullable', 'string', 'max:50'],
            'currency'       => ['nullable', 'string', 'max:10'],
            'is_active'      => ['boolean'],
            'is_default'     => ['boolean'],
            'branch_id'      => ['nullable', 'exists:branches,id'],
            'warehouse_id'   => ['nullable', 'exists:warehouses,id'],
            'notes'          => ['nullable', 'string'],
        ];
    }
}
