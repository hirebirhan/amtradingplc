<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use Livewire\Component;

class Show extends Component
{
    public $employee;

    public function mount(Employee $employee)
    {
        $this->employee = $employee;
    }

    public function render()
    {
        return view('livewire.employees.show');
    }
} 