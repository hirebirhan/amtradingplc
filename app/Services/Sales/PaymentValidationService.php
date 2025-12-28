<?php

namespace App\Services\Sales;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\SalePayment;
use App\Models\CreditPayment;

class PaymentValidationService
{
    public function updatePaymentStatus(array &$form, float $totalAmount): void
    {
        switch ($form['payment_method']) {
            case 'cash':
            case 'bank_transfer':
            case 'telebirr':
                $form['payment_status'] = 'paid';
                break;
            case 'credit_advance':
                $form['payment_status'] = 'partial';
                if ($totalAmount > 0 && (empty($form['advance_amount']) || $form['advance_amount'] == 0)) {
                    $form['advance_amount'] = round($totalAmount * 0.2, 2); // Default 20%
                }
                break;
            case 'credit_full':
                $form['payment_status'] = 'due';
                break;
        }
    }

    public function isPaymentMethodValid(array $form, float $totalAmount): bool
    {
        switch ($form['payment_method']) {
            case 'telebirr':
                return !empty($form['transaction_number']) && 
                       strlen((string) $form['transaction_number']) >= 5;
            case 'bank_transfer':
                return !empty($form['transaction_number']) && 
                       strlen((string) $form['transaction_number']) >= 5 &&
                       !empty($form['bank_account_id']);
            case 'credit_advance':
                return $form['advance_amount'] > 0 && 
                       $form['advance_amount'] < $totalAmount;
            default:
                return true;
        }
    }

    public function transactionNumberExists(string $transactionNumber): bool
    {
        $number = trim($transactionNumber);

        if ($number === '') {
            return false;
        }

        if (Sale::where('transaction_number', $number)->exists()) {
            return true;
        }

        if (Purchase::where('transaction_number', $number)->exists()) {
            return true;
        }

        $existsInSalePayments = class_exists(SalePayment::class)
            ? SalePayment::where('reference_no', $number)->exists()
            : false;

        if ($existsInSalePayments) {
            return true;
        }

        $existsInCreditPayments = class_exists(CreditPayment::class)
            ? CreditPayment::where('reference_no', $number)->exists()
            : false;

        return $existsInCreditPayments;
    }

    public function calculateTotals(array $items, float $taxRate, float $shipping): array
    {
        $subtotal = collect($items)->sum('subtotal');
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $totalAmount = round($subtotal + $taxAmount + $shipping, 2);

        return [
            'subtotal' => $subtotal,
            'taxAmount' => $taxAmount,
            'totalAmount' => $totalAmount,
            'shippingAmount' => $shipping
        ];
    }
}