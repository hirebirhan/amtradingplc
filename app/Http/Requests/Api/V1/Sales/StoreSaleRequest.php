<?php

namespace App\Http\Requests\Api\V1\Sales;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can('sales.create')) {
            return false;
        }

        // Non-admins can only create sales for their own branch
        if ($branchId = $this->integer('branch_id')) {
            return $this->user()->hasAccessToBranch($branchId);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'sale_date' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id', 'required_unless:is_walking_customer,true'],
            'is_walking_customer' => ['boolean'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'payment_method' => ['required', Rule::in(PaymentMethod::forSalesValues())],
            'transaction_number' => ['required_if:payment_method,bank_transfer,telebirr', 'nullable', 'string', 'min:5', 'max:255'],
            'bank_account_id' => ['required_if:payment_method,bank_transfer', 'nullable', 'exists:bank_accounts,id'],
            'advance_amount' => ['required_if:payment_method,credit_advance', 'nullable', 'numeric', 'min:0.01'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],
            'items.*.sale_method' => ['nullable', Rule::in(['piece', 'unit'])],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
