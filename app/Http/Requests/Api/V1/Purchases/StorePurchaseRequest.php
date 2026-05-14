<?php

namespace App\Http\Requests\Api\V1\Purchases;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can('purchases.create')) {
            return false;
        }

        // Branch managers / non-admins can only create for their own branch
        if ($branchId = $this->integer('branch_id')) {
            return $this->user()->hasAccessToBranch($branchId);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'purchase_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(PaymentMethod::forPurchasesValues())],
            'transaction_number' => ['required_if:payment_method,bank_transfer,telebirr', 'nullable', 'string', 'min:5', 'max:255'],
            'bank_account_id' => ['required_if:payment_method,bank_transfer', 'nullable', 'exists:bank_accounts,id'],
            'advance_amount' => ['required_if:payment_method,credit_advance', 'nullable', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0.01'],
            'items.*.subtotal' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
