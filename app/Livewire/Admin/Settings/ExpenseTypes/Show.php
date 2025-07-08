<?php

namespace App\Livewire\Admin\Settings\ExpenseTypes;

use App\Models\ExpenseType;
use Livewire\Component;

class Show extends Component
{
    public ExpenseType $expenseType;
    
    public function mount(ExpenseType $expenseType)
    {
        $this->expenseType = $expenseType->load(['branch', 'creator']);
    }
    
    public function delete()
    {
        // Check if expense type is being used by any expenses
        if ($this->expenseType->expenses()->count() > 0) {
            session()->flash('error', 'Cannot delete: This expense type is being used by existing expenses.');
            return;
        }
        
        $this->expenseType->delete();
        session()->flash('success', 'Expense type deleted successfully!');
        return redirect()->route('admin.settings.expense-types');
    }
    
    public function render()
    {
        return view('livewire.admin.settings.expense-types.show')->layout('layouts.app');
    }
} 