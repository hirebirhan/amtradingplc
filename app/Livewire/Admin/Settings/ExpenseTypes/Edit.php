<?php

namespace App\Livewire\Admin\Settings\ExpenseTypes;

use App\Models\Branch;
use App\Models\ExpenseType;
use Livewire\Component;

class Edit extends Component
{
    public ExpenseType $expenseType;
    
    // Form properties
    public $name;
    public $description;
    public $is_active;
    public $branch_id;
    
    // Validation rules
    protected $rules = [
        'name' => 'required|min:2|max:100',
        'description' => 'nullable|max:500',
        'is_active' => 'boolean',
        'branch_id' => 'nullable|exists:branches,id',
    ];
    
    public function mount(ExpenseType $expenseType)
    {
        $this->expenseType = $expenseType;
        $this->name = $expenseType->name;
        $this->description = $expenseType->description;
        $this->is_active = $expenseType->is_active;
        $this->branch_id = $expenseType->branch_id;
    }
    
    public function update()
    {
        $this->validate();
        
        $this->expenseType->update([
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'branch_id' => $this->branch_id,
        ]);
        
        session()->flash('success', 'Expense type updated successfully!');
        return redirect()->route('admin.settings.expense-types.show', $this->expenseType);
    }
    
    public function cancel()
    {
        return redirect()->route('admin.settings.expense-types.show', $this->expenseType);
    }
    
    public function render()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        
        return view('livewire.admin.settings.expense-types.edit', [
            'branches' => $branches,
        ])->layout('layouts.app');
    }
} 