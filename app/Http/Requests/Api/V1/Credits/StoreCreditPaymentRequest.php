<?php

namespace App\Http\Requests\Api\V1\Credits;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCreditPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('credits.edit');
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(PaymentMethod::forOperationalPaymentValues())],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
