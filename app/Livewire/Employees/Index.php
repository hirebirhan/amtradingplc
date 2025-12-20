<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'last_name';
    public $sortDirection = 'asc';
    public $filters = [
        'department' => '',
    ];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilters()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Employee::query()
            ->where('status', 'active')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('employee_id', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filters['department'], function ($query, $department) {
                $query->where('department', $department);
            });

        $employees = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $departments = Employee::distinct('department')->pluck('department')->filter()->values();

        return view('livewire.employees.index', [
            'employees' => $employees,
            'departments' => $departments,
        ]);
    }

    public function deleteEmployee($id)
    {
        // Check permission
        if (!auth()->user()->can('employees.delete')) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'You do not have permission to delete employees.'
            ]);
            return;
        }

        try {
            $employee = Employee::findOrFail($id);
            $employeeName = $employee->first_name . ' ' . $employee->last_name;
            $employee->delete();
            
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => "Employee '{$employeeName}' successfully deleted."
            ]);
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Error deleting employee: ' . $e->getMessage()
            ]);
        }
    }
} 