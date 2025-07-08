<?php

namespace App\Livewire\Admin\Settings\ExpenseTypes;

use App\Models\Branch;
use App\Models\ExpenseType;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Create extends Component
{
    // Form properties
    public $name;
    public $description;
    public $is_active = true;
    public $branch_id;
    
    // Validation rules
    protected $rules = [
        'name' => 'required|min:2|max:100',
        'description' => 'nullable|max:500',
        'is_active' => 'boolean',
        'branch_id' => 'nullable|exists:branches,id',
    ];
    
    public function store()
    {
        $this->validate();
        
        $expenseType = ExpenseType::create([
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'branch_id' => $this->branch_id,
            'created_by' => Auth::id(),
        ]);
        
        session()->flash('success', 'Expense type created successfully!');
        return redirect()->route('admin.settings.expense-types.show', $expenseType);
    }
    
    public function cancel()
    {
        return redirect()->route('admin.settings.expense-types');
    }
    
    public function render()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        
        return view('livewire.admin.settings.expense-types.create', [
            'branches' => $branches,
        ])->layout('layouts.app');
    }
} 