<?php

namespace App\Livewire\Expenses;

use Livewire\Component;
use App\Models\Expense;
use App\Models\ExpenseType;
use Illuminate\Support\Facades\Auth;

class Edit extends Component
{
    public Expense $expense;
    public $form = [
        'reference_no' => '',
        'expense_type_id' => '',
        'amount' => '',
        'payment_method' => '',
        'expense_date' => '',
        'description' => '',
        'is_recurring' => false,
        'recurring_frequency' => '',
        'recurring_end_date' => '',
    ];
    
    public $expenseTypes = [];

    public function mount(Expense $expense)
    {
        $this->expense = $expense;

        // Check if user has permission to edit this expense
        if (!Auth::user()->hasRole('admin') && $expense->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        // Initialize form with expense data
        $this->form = [
            'reference_no' => $expense->reference_no,
            'expense_type_id' => $expense->expense_type_id,
            'amount' => $expense->amount,
            'payment_method' => $expense->payment_method,
            'expense_date' => $expense->expense_date->format('Y-m-d'),
            'description' => $expense->description,
            'is_recurring' => $expense->is_recurring,
            'recurring_frequency' => $expense->recurring_frequency,
            'recurring_end_date' => $expense->recurring_end_date?->format('Y-m-d'),
        ];
        
        $this->expenseTypes = ExpenseType::where('is_active', true)->orderBy('name')->get();
    }

    public function update()
    {
        $this->validate([
            'form.reference_no' => 'required|string',
            'form.expense_type_id' => 'required|exists:expense_types,id',
            'form.amount' => 'required|numeric|min:0',
            'form.payment_method' => 'required|in:cash,bank_transfer,check,credit_card',
            'form.expense_date' => 'required|date',
            'form.description' => 'nullable|string',
            'form.is_recurring' => 'boolean',
            'form.recurring_frequency' => 'required_if:form.is_recurring,true|in:daily,weekly,monthly,yearly',
            'form.recurring_end_date' => 'required_if:form.is_recurring,true|date|after:form.expense_date',
        ]);

        // Remove reference_no from update data since it should not be changed
        $updateData = $this->form;
        unset($updateData['reference_no']);
        
        $this->expense->update($updateData);

        session()->flash('success', 'Expense updated successfully.');
        return redirect()->route('admin.expenses.show', $this->expense);
    }

    public function cancel()
    {
        return redirect()->route('admin.expenses.show', $this->expense);
    }

    public function render()
    {
        return view('livewire.expenses.edit')->layout('layouts.app');
    }
}