<?php

namespace App\Services\Sales;

use Illuminate\Support\Facades\Auth;
use App\Enums\PaymentMethod;
use App\Models\User;

class FormValidationService
{
    private PaymentValidationService $paymentService;

    public function __construct(PaymentValidationService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function getRules(array $form, float $totalAmount): array
    {
        $isWalkingCustomer = !empty($form['is_walking_customer']) && $form['is_walking_customer'] !== '0' && $form['is_walking_customer'] !== 'false';
        
        $rules = [
            'form.sale_date' => 'required|date|before_or_equal:today',
            'form.customer_id' => $isWalkingCustomer ? 'nullable' : 'required|exists:customers,id',
            'form.payment_method' => ['required', \Illuminate\Validation\Rule::enum(PaymentMethod::class)],
            'form.tax' => 'nullable|numeric|min:0|max:100',
            'form.shipping' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
        ];

        $this->addLocationRules($rules);
        $this->addWarehousePermissionRules($rules);
        $this->addPaymentMethodRules($rules, $form, $totalAmount);

        return $rules;
    }

    public function getMessages(): array
    {
        return [
            'form.sale_date.before_or_equal' => 'Sale date cannot be in the future. Only current or past dates are allowed.',
            'form.customer_id.required' => 'Please select a customer or check walking customer.',
            'form.branch_id.required_without' => 'Please select either a branch or warehouse.',
            'form.warehouse_id.required_without' => 'Please select either a branch or warehouse.',
            'items.required' => 'Please add at least one item to the sale.',
            'items.min' => 'Please add at least one item to the sale.',
            'form.transaction_number.required' => 'Transaction number is required for this payment method.',
            'form.transaction_number.min' => 'Transaction number must be at least 5 characters.',
            'form.transaction_number.unique' => 'This transaction number has already been used.',
            'form.bank_account_id.required' => 'Please select a bank account.',
            'form.bank_account_id.exists' => 'Selected bank account is invalid.',
            'form.advance_amount.required' => 'Please enter an advance amount.',
            'form.advance_amount.lt' => 'Advance amount must be less than the total amount.',
        ];
    }

    private function addLocationRules(array &$rules): void
    {
        if (!Auth::user()->branch_id && !Auth::user()->warehouse_id) {
            $rules['form.branch_id'] = 'required_without:form.warehouse_id|nullable|exists:branches,id';
            $rules['form.warehouse_id'] = 'required_without:form.branch_id|nullable|exists:warehouses,id';
        }
    }

    private function addWarehousePermissionRules(array &$rules): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
            $rules['form.warehouse_id'] = [
                $rules['form.warehouse_id'] ?? 'nullable',
                function ($attribute, $value, $fail) {
                    if (!empty($value) && !\App\Facades\UserHelperFacade::hasAccessToWarehouse((int) $value)) {
                        $fail('You do not have permission to access this warehouse.');
                    }
                }
            ];
        }
    }

    private function addPaymentMethodRules(array &$rules, array $form, float $totalAmount): void
    {
        if ($form['payment_method'] === 'telebirr') {
            $rules['form.transaction_number'] = 'required|string|min:5|max:255|unique:sales,transaction_number';
        }

        if ($form['payment_method'] === 'bank_transfer') {
            $rules['form.bank_account_id'] = 'required|exists:bank_accounts,id';
            $rules['form.transaction_number'] = 'required|string|min:5|max:255|unique:sales,transaction_number';
        }

        if ($form['payment_method'] === 'credit_advance') {
            $rules['form.advance_amount'] = 'required|numeric|min:0.01|lt:' . $totalAmount;
        }
    }
}