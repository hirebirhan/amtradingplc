<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Branch;
use App\Models\ExpenseType;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseTypes extends Component
{
    use WithPagination;
    
    // Form properties
    public $expenseTypeId;
    public $name;
    public $description;
    public $is_active = true;
    public $branch_id;
    
    // UI state
    public $searchQuery = '';
    public $perPage = 10;
    
    // Validation rules
    protected $rules = [
        'name' => 'required|min:2|max:100',
        'description' => 'nullable|max:500',
        'is_active' => 'boolean',
        'branch_id' => 'nullable|exists:branches,id',
    ];
    
    public function mount()
    {
        // Initialize any required data
        $this->resetPage();
    }
    
    public function render()
    {
        $query = ExpenseType::query()
            ->with(['branch', 'creator'])
            ->when($this->searchQuery, function($q) {
                $q->where('name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('description', 'like', '%' . $this->searchQuery . '%');
            })
            ->orderBy('name');
            
        $expenseTypes = $query->paginate($this->perPage);
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        
        return view('livewire.admin.settings.expense-types', [
            'expenseTypes' => $expenseTypes,
            'branches' => $branches,
        ])->layout('layouts.app');
    }
    
    public function create()
    {
        $this->resetForm();
        $this->dispatch('showCreateModal');
    }
    
    public function store()
    {
        $this->validate();
        
        ExpenseType::create([
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'branch_id' => $this->branch_id,
            'created_by' => Auth::id(),
        ]);
        
        $this->resetForm();
        $this->dispatch('hideCreateModal');
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => 'Expense type created successfully!'
        ]);
    }
    
    public function edit(ExpenseType $expenseType)
    {
        $this->expenseTypeId = $expenseType->id;
        $this->name = $expenseType->name;
        $this->description = $expenseType->description;
        $this->is_active = $expenseType->is_active;
        $this->branch_id = $expenseType->branch_id;
        
        $this->dispatch('showEditModal');
    }
    
    public function update()
    {
        $this->validate();
        
        $expenseType = ExpenseType::findOrFail($this->expenseTypeId);
        $expenseType->update([
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'branch_id' => $this->branch_id,
        ]);
        
        $this->resetForm();
        $this->dispatch('hideEditModal');
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => 'Expense type updated successfully!'
        ]);
    }
    
    public function confirmDelete(ExpenseType $expenseType)
    {
        $this->expenseTypeId = $expenseType->id;
        $this->name = $expenseType->name;
        $this->dispatch('showDeleteModal');
    }
    
    public function delete()
    {
        $expenseType = ExpenseType::findOrFail($this->expenseTypeId);
        
        // Check if expense type is being used by any expenses
        if ($expenseType->expenses()->count() > 0) {
            $this->dispatch('hideDeleteModal');
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Cannot delete: This expense type is being used by existing expenses.'
            ]);
            return;
        }
        
        $expenseType->delete();
        $this->resetForm();
        $this->dispatch('hideDeleteModal');
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => 'Expense type deleted successfully!'
        ]);
    }
    
    public function cancel()
    {
        $this->resetForm();
    }
    
    private function resetForm()
    {
        $this->reset(['expenseTypeId', 'name', 'description', 'is_active', 'branch_id']);
        $this->resetValidation();
    }
    
    // Reset pagination when search changes
    public function updatedSearchQuery()
    {
        $this->resetPage();
    }
}
