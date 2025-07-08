<?php

namespace App\Livewire\Expenses;

use Livewire\Component;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;

class Show extends Component
{
    public Expense $expense;

    public function mount(Expense $expense)
    {
        $this->expense = $expense;

        // Check if user has permission to view this expense
        if (!Auth::user()->hasRole('admin') && $expense->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        return view('livewire.expenses.show')->layout('layouts.app');
    }
}