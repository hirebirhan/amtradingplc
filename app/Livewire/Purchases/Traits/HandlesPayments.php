<?php

namespace App\Livewire\Purchases\Traits;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\SalePayment;
use App\Models\CreditPayment;

trait HandlesPayments
{
    public function updatedFormPaymentMethod($value)
    {
        $this->form['transaction_number'] = '';
        $this->form['bank_account_id'] = '';
        $this->form['receiver_bank_name'] = '';
        $this->form['receiver_account_number'] = '';
        $this->form['receipt_url'] = '';
        $this->form['receipt_image'] = '';
        $this->form['advance_amount'] = 0;

        if (in_array($value, [PaymentMethod::CASH->value, PaymentMethod::BANK_TRANSFER->value, PaymentMethod::TELEBIRR->value], true)) {
            $this->form['payment_status'] = PaymentStatus::PAID->value;
        } elseif ($value === PaymentMethod::CREDIT_ADVANCE->value) {
            $this->form['payment_status'] = PaymentStatus::PARTIAL->value;
            if ($this->totalAmount > 0) {
                $this->form['advance_amount'] = round($this->totalAmount * 0.2, 2);
            }
        } elseif ($value === PaymentMethod::FULL_CREDIT->value) {
            $this->form['payment_status'] = PaymentStatus::DUE->value;
        }

        $this->updateTotals();
    }

    public function updatedFormTransactionNumber($value)
    {
        $this->resetErrorBag('form.transaction_number');
    }

    private function transactionNumberExists(string $transactionNumber): bool
    {
        if ($transactionNumber === '') {
            return false;
        }

        if (Purchase::where('transaction_number', $transactionNumber)->exists()) {
            return true;
        }

        if (Sale::where('transaction_number', $transactionNumber)->exists()) {
            return true;
        }

        $salePaymentExists = class_exists(SalePayment::class)
            ? SalePayment::where('transaction_number', $transactionNumber)->exists()
            : false;
        if ($salePaymentExists) {
            return true;
        }

        $creditPaymentExists = class_exists(CreditPayment::class)
            ? CreditPayment::where('reference_no', $transactionNumber)->exists()
            : false;

        return $creditPaymentExists;
    }

    private function setPaymentAmounts($purchase)
    {
        if ($this->form['payment_method'] === PaymentMethod::FULL_CREDIT->value) {
            $purchase->paid_amount = 0;
            $purchase->due_amount = $this->totalAmount;
            $purchase->payment_status = PaymentStatus::DUE->value;
        } elseif ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value) {
            $purchase->paid_amount = $this->form['advance_amount'];
            $purchase->due_amount = $this->totalAmount - $this->form['advance_amount'];
            $purchase->payment_status = PaymentStatus::PARTIAL->value;
        } else {
            $purchase->paid_amount = $this->totalAmount;
            $purchase->due_amount = 0;
            $purchase->payment_status = PaymentStatus::PAID->value;
        }
    }

    private function setPaymentFields($purchase)
    {
        if (in_array($this->form['payment_method'], [PaymentMethod::BANK_TRANSFER->value, PaymentMethod::TELEBIRR->value], true)) {
            $purchase->transaction_number = $this->form['transaction_number'] ?? null;
        }

        if ($this->form['payment_method'] === PaymentMethod::TELEBIRR->value) {
            $purchase->receiver_account_holder = $this->form['receiver_account_holder'] ?? null;
        }

        if ($this->form['payment_method'] === PaymentMethod::BANK_TRANSFER->value) {
            $purchase->bank_account_id = $this->form['bank_account_id'] ?? null;
        }
    }
}