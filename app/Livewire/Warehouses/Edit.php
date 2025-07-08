<?php

namespace App\Livewire\Warehouses;

use App\Models\Branch;
use App\Models\Warehouse;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('Edit Warehouse')]
class Edit extends Component
{
    public Warehouse $warehouse;

    public $name;
    public $code;
    public $address;
    public $manager_name;
    public $phone;
    public $branch_ids = [];

    public function mount(Warehouse $warehouse)
    {
        $this->warehouse = $warehouse;
        $this->name = $warehouse->name;
        $this->code = $warehouse->code;
        $this->address = $warehouse->address;
        $this->manager_name = $warehouse->manager_name;
        $this->phone = $warehouse->phone;
        $this->branch_ids = $warehouse->branches->pluck('id')->toArray();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'manager_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|regex:/^\+?[0-9\s\-\(\)]+$/',
            'branch_ids' => 'array',
            'branch_ids.*' => 'exists:branches,id',
        ];
    }

    public function update()
    {
        $validated = $this->validate();
        
        // Remove branch_ids from validated data
        $branch_ids = $validated['branch_ids'] ?? [];
        unset($validated['branch_ids']);

        // Update the warehouse
        $this->warehouse->update($validated);
        
        // Sync branches
        $this->warehouse->branches()->sync($branch_ids);

        // Use Livewire v3 notification dispatch
        $this->dispatch('notify', type: 'success', message: "Warehouse '{$this->warehouse->name}' updated successfully.");
        return redirect()->route('admin.warehouses.index');
    }

    public function render()
    {
        return view('livewire.warehouses.edit', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'active' => 'warehouses',
        ])->title('Edit Warehouse');
    }
}