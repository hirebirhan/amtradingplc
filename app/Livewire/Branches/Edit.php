<?php

namespace App\Livewire\Branches;

use App\Models\Branch;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('Edit Branch')]
class Edit extends Component
{
    public Branch $branch;

    public $name;
    public $address;
    public $phone;
    public $email;
    public $isActive;

    public function mount(Branch $branch)
    {
        $this->branch = $branch;
        $this->name = $branch->name;
        $this->address = $branch->address;
        $this->phone = $branch->phone;
        $this->email = $branch->email;
        $this->isActive = $branch->is_active;
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:branches,name,' . $this->branch->id,
            'address' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:branches,email,' . $this->branch->id,
            'isActive' => 'boolean',
        ];
    }

    public function save()
    {
        $this->validate();

        try {
            $this->branch->update([
                'name' => $this->name,
                'address' => $this->address,
                'phone' => $this->phone,
                'email' => $this->email,
                'is_active' => $this->isActive,
            ]);

            $this->dispatch('notify', type: 'success', message: 'Branch updated successfully.');
            return redirect()->route('admin.branches.index');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Error updating branch: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.branches.edit', [
            'active' => 'branches',
        ]);
    }
}