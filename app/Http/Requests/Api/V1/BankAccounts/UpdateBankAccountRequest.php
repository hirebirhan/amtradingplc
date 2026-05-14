<?php

namespace App\Http\Requests\Api\V1\BankAccounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('update', $this->route('bank_account')); }

    public function rules(): array
    {
        $account = $this->route('bank_account');
        return [
            'account_name'   => ['sometimes', 'string', 'max:255'],
            'account_number' => ['sometimes', 'string', 'max:100', Rule::unique('bank_accounts', 'account_number')->ignore($account)],
            'bank_name'      => ['sometimes', 'string', 'max:255'],
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
