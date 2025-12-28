<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\CreditPayment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PurchaseValidationService
{
    public function validatePurchaseForm(array $formData, array $items, float $totalAmount): array
    {
        $rules = $this->buildValidationRules($formData, $totalAmount);
        $messages = $this->getValidationMessages();
        
        $validator = Validator::make([
            'form' => $formData,
            'items' => $items
        ], $rules, $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()->toArray(),
                'messages' => $validator->errors()->all()
            ];
        }

        return ['success' => true];
    }

    private function buildValidationRules(array $formData, float $totalAmount): array
    {
        $rules = [
            'form.purchase_date' => 'required|date',
            'form.supplier_id' => 'required|exists:suppliers,id',
            'form.branch_id' => 'required|exists:branches,id',
            'form.payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'form.tax' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
        ];

        $paymentMethod = $formData['payment_method'] ?? '';

        // Transaction number required for Telebirr and Bank Transfer
        if (in_array($paymentMethod, [PaymentMethod::TELEBIRR->value, PaymentMethod::BANK_TRANSFER->value])) {
            $rules['form.transaction_number'] = [
                'required',
                'string',
                'min:5',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($this->transactionNumberExists($value)) {
                        $fail('This transaction number has already been used.');
                    }
                },
            ];
        }

        // Bank account required for Bank Transfer
        if ($paymentMethod === PaymentMethod::BANK_TRANSFER->value) {
            $rules['form.bank_account_id'] = 'required|exists:bank_accounts,id';
        }

        // Advance amount validation for Credit with Advance
        if ($paymentMethod === PaymentMethod::CREDIT_ADVANCE->value) {
            $rules['form.advance_amount'] = 'required|numeric|min:0.01|max:' . $totalAmount;
        }

        return $rules;
    }

    private function getValidationMessages(): array
    {
        return [
            'form.supplier_id.required' => 'Please select a supplier.',
            'form.supplier_id.exists' => 'Selected supplier is invalid.',
            'form.branch_id.required' => 'Please select a branch.',
            'form.branch_id.exists' => 'Selected branch is invalid.',
            'form.payment_method.required' => 'Please select a payment method.',
            'form.transaction_number.required' => 'Transaction number is required for this payment method.',
            'form.transaction_number.min' => 'Transaction number must be at least 5 characters.',
            'form.bank_account_id.required' => 'Please select a bank account.',
            'form.bank_account_id.exists' => 'Selected bank account is invalid.',
            'form.advance_amount.required' => 'Advance amount is required.',
            'form.advance_amount.min' => 'Advance amount must be greater than zero.',
            'form.advance_amount.max' => 'Advance amount cannot exceed total amount.',
            'items.required' => 'Please add at least one item to the purchase.',
            'items.min' => 'Please add at least one item to the purchase.',
        ];
    }

    private function transactionNumberExists(string $transactionNumber): bool
    {
        if (empty($transactionNumber)) {
            return false;
        }

        return Purchase::where('transaction_number', $transactionNumber)->exists() ||
               Sale::where('transaction_number', $transactionNumber)->exists() ||
               (class_exists(SalePayment::class) && SalePayment::where('transaction_number', $transactionNumber)->exists()) ||
               (class_exists(CreditPayment::class) && CreditPayment::where('reference_no', $transactionNumber)->exists());
    }
}