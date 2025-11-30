<?php

namespace App\Livewire\SalesCreditPayment;

use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\BankAccount;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
class Create extends Component
{
    public Credit $credit;
    public $form = [
        'amount' => 0,
        'payment_method' => 'cash',
        'payment_date' => '',
        'notes' => '',
        'transaction_number' => '',
        'bank_account_id' => '',
    ];

    public $bankAccounts = [];
    public $showConfirmModal = false;

    protected function rules()
    {
        $rules = [
            'form.amount' => 'required|numeric|min:0.01|max:' . $this->credit->balance,
            'form.payment_method' => 'required|in:cash,bank_transfer,telebirr',
            'form.payment_date' => 'required|date',
            'form.notes' => 'nullable|string|max:500',
        ];

        if ($this->form['payment_method'] === 'telebirr') {
            $rules['form.transaction_number'] = 'required|string|min:5';
        }

        if ($this->form['payment_method'] === 'bank_transfer') {
            $rules['form.bank_account_id'] = 'required|exists:bank_accounts,id';
        }

        return $rules;
    }

    public function mount(Credit $credit)
    {
        $this->credit = $credit->load(['customer', 'sale']);
        
        if ($this->credit->credit_type !== 'receivable') {
            abort(404, 'Credit not found or not a receivable credit.');
        }

        $this->form = [
            'amount' => $this->credit->balance,
            'payment_method' => 'cash',
            'payment_date' => now()->format('Y-m-d'),
            'notes' => '',
            'transaction_number' => '',
            'bank_account_id' => '',
        ];

        $this->bankAccounts = BankAccount::where('is_active', true)->orderBy('account_name')->get();
    }

    public function validateAndShowModal()
    {
        $this->validate();
        $this->showConfirmModal = true;
    }

    public function confirmPayment()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $payment = $this->credit->addPayment(
                $this->form['amount'],
                $this->form['payment_method'],
                $this->generatePaymentReference(),
                $this->form['notes'],
                $this->form['payment_date']
            );

            if ($this->form['payment_method'] === 'telebirr') {
                $payment->update(['transaction_number' => $this->form['transaction_number']]);
            }

            if ($this->form['payment_method'] === 'bank_transfer') {
                $payment->update(['bank_account_id' => $this->form['bank_account_id']]);
            }

            DB::commit();

            session()->flash('success', 'Payment recorded successfully!');
            return redirect()->route('admin.credits.show', $this->credit);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    private function generatePaymentReference()
    {
        return 'PAY-' . $this->credit->reference_no . '-' . now()->format('YmdHis');
    }

    public function render()
    {
        return view('livewire.sales-credit-payment.create');
    }
}