<?php

namespace App\Http\Requests\Api\V1\Expenses;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('expenses.create');
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:100'],
            'expense_type_id' => ['nullable', 'exists:expense_types,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(PaymentMethod::forOperationalPaymentValues())],
            'expense_date' => ['required', 'date'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'note' => ['nullable', 'string'],
            'is_recurring' => ['boolean'],
        ];
    }
}
