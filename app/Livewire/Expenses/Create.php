<?php

namespace App\Livewire\Expenses;

use Livewire\Component;
use App\Models\Expense;
use App\Models\ExpenseType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Create extends Component
{
    public $form = [
        'reference_no' => '',
        'expense_type_id' => '',
        'amount' => '',
        'description' => '',
        'expense_date' => '',
        'payment_method' => '',
        'is_recurring' => false,
        'recurring_frequency' => '',
        'recurring_end_date' => '',
    ];
    
    public $expenseTypes = [];

    public function mount()
    {
        $this->form['expense_date'] = now()->format('Y-m-d');
        $this->form['reference_no'] = 'EXP-' . Str::random(8);
        $this->expenseTypes = ExpenseType::where('is_active', true)->orderBy('name')->get();
    }

    public function save()
    {
        $this->validate([
            'form.reference_no' => [
                'required',
                'string',
                'max:50',
                'unique:expenses,reference_no',
                'regex:/^EXP-[a-zA-Z0-9]+$/'
            ],
            'form.expense_type_id' => [
                'required',
                'exists:expense_types,id'
            ],
            'form.amount' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999.99',
                'regex:/^\d+(\.\d{1,2})?$/'
            ],
            'form.description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'form.expense_date' => [
                'required',
                'date',
                'before_or_equal:today'
            ],
            'form.payment_method' => [
                'required',
                'string',
                'in:cash,bank_transfer,check,credit_card'
            ],
            'form.is_recurring' => [
                'required',
                'boolean'
            ],
            'form.recurring_frequency' => [
                'required_if:form.is_recurring,true',
                'nullable',
                'string',
                'in:daily,weekly,monthly,yearly'
            ],
            'form.recurring_end_date' => [
                'required_if:form.is_recurring,true',
                'nullable',
                'date',
                'after:form.expense_date'
            ]
        ], [
            'form.reference_no.required' => 'The reference number is required.',
            'form.reference_no.unique' => 'This reference number is already in use.',
            'form.reference_no.regex' => 'Reference number must start with EXP- followed by alphanumeric characters.',
            'form.expense_type_id.required' => 'Please select an expense type.',
            'form.expense_type_id.exists' => 'The selected expense type is invalid.',
            'form.amount.required' => 'The amount is required.',
            'form.amount.min' => 'The amount must be greater than 0.',
            'form.amount.max' => 'The amount cannot exceed 9,999,999.99.',
            'form.amount.regex' => 'The amount must be a valid number with up to 2 decimal places.',
            'form.expense_date.required' => 'The expense date is required.',
            'form.expense_date.before_or_equal' => 'The expense date cannot be in the future.',
            'form.payment_method.required' => 'Please select a payment method.',
            'form.payment_method.in' => 'Please select a valid payment method.',
            'form.recurring_frequency.required_if' => 'Recurring frequency is required for recurring expenses.',
            'form.recurring_end_date.required_if' => 'Next recurrence date is required for recurring expenses.',
            'form.recurring_end_date.after' => 'Next recurrence date must be after the expense date.'
        ]);

        $user = Auth::user();

        if (!$user->branch_id) {
            $this->addError('form.branch_id', 'You must be assigned to a branch to create an expense.');
            return;
        }

        try {
            $expense = new Expense();
            $expense->fill($this->form);
            $expense->user_id = $user->id;
            $expense->branch_id = $user->branch_id;
            $expense->save();

            session()->flash('success', 'Expense created successfully.');
            return redirect()->route('admin.expenses.show', $expense);
        } catch (\Exception $e) {
            $this->addError('form.save', 'Failed to create expense: ' . $e->getMessage());
            return;
        }
    }

    public function cancel()
    {
        return redirect()->route('admin.expenses.index');
    }

    public function render()
    {
        return view('livewire.expenses.create')->layout('layouts.app');
    }
}