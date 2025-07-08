<?php

namespace App\Livewire\Branches;

use App\Models\Branch;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('Branch Details')]
class Show extends Component
{
    public Branch $branch;

    public function mount(Branch $branch)
    {
        $this->branch = $branch;
    }

    public function render()
    {
        $warehouses = $this->branch->warehouses()->get();

        return view('livewire.branches.show', [
            'active' => 'branches',
            'warehouses' => $warehouses,
        ]);
    }
}