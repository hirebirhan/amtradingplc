<?php

namespace App\Livewire\Employees;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Warehouse;
use App\Enums\Department;
use App\Enums\Status;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Layout('components.layouts.app')]
class Edit extends Component
{
    use WithFileUploads;

    public $employeeId;
    public $employee;
    
    public $firstName;
    public $lastName;
    public $email;
    public $phone;
    public $position;
    public $department;
    public $hireDate;
    public $birthDate;
    public $address;
    public $emergencyContact;
    public $emergencyPhone;
    public $branchId;
    public $warehouseId;
    public $empId;
    public $notes;
    public $baseSalary;
    public $allowance;

    public function mount(Employee $employee)
    {
        $this->employee = $employee;
        $this->employeeId = $employee->id;
        $this->firstName = $employee->first_name;
        $this->lastName = $employee->last_name;
        $this->email = $employee->email;
        $this->phone = $employee->phone;
        $this->position = $employee->position;
        $this->department = $employee->department?->value;
        $this->hireDate = $employee->hire_date ? $employee->hire_date->format('Y-m-d') : null;
        $this->birthDate = $employee->birth_date ? $employee->birth_date->format('Y-m-d') : null;
        $this->address = $employee->address;
        $this->emergencyContact = $employee->emergency_contact;
        $this->emergencyPhone = $employee->emergency_phone;
        $this->branchId = $employee->branch_id;
        $this->warehouseId = $employee->warehouse_id;
        $this->empId = $employee->employee_id;
        $this->notes = $employee->notes;
        $this->baseSalary = $employee->base_salary;
        $this->allowance = $employee->allowance;
    }

    protected function rules()
    {
        return [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:employees,email,' . $this->employeeId,
            'phone' => 'nullable|string|max:20',
            'position' => 'required|string|max:100',
            'department' => 'required|string|in:' . implode(',', Department::values()),
            'hireDate' => 'required|date|before_or_equal:today',
            'birthDate' => 'nullable|date|before:today|before:hireDate',
            'address' => 'nullable|string',
            'emergencyContact' => 'nullable|string|max:255',
            'emergencyPhone' => 'nullable|string|max:20',
            'branchId' => 'nullable|exists:branches,id',
            'warehouseId' => 'nullable|exists:warehouses,id',
            'empId' => 'required|string|max:50|unique:employees,employee_id,' . $this->employeeId,
            'notes' => 'nullable|string',
            'baseSalary' => 'required|numeric|min:0',
            'allowance' => 'nullable|numeric|min:0',
        ];
    }

    protected $validationAttributes = [
        'firstName' => 'first name',
        'lastName' => 'last name',
        'empId' => 'employee ID',
        'branchId' => 'branch',
        'warehouseId' => 'warehouse',
        'baseSalary' => 'base salary',
        'birthDate' => 'birth date',
        'hireDate' => 'hire date',
    ];

    protected $messages = [
        'branchId.required_without' => 'Please select either a branch or warehouse for the employee.',
        'warehouseId.required_without' => 'Please select either a branch or warehouse for the employee.',
        'birthDate.before' => 'Birth date must be a date in the past.',
        'birthDate.before:hireDate' => 'Birth date must be before hire date.',
        'hireDate.before_or_equal' => 'Hire date cannot be in the future.',
        'email.unique' => 'This email address is already registered to another employee.',
    ];

    public function render()
    {
        return view('livewire.employees.edit', [
            'branches' => Branch::orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'departments' => Department::toArray(),
        ])->title('Edit Employee - ' . $this->employee->full_name);
    }

    public function updatedBranchId()
    {
        if ($this->branchId) {
            $this->warehouseId = null;
        }
    }

    public function updatedWarehouseId()
    {
        if ($this->warehouseId) {
            $this->branchId = null;
        }
    }

    public function update()
    {
        try {
            DB::beginTransaction();

            $validated = $this->validate();

            $this->employee->update([
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'position' => $validated['position'],
                'department' => $validated['department'],
                'hire_date' => $validated['hireDate'],
                'birth_date' => $validated['birthDate'],
                'address' => $validated['address'],
                'emergency_contact' => $validated['emergencyContact'],
                'emergency_phone' => $validated['emergencyPhone'],
                'branch_id' => $validated['branchId'],
                'warehouse_id' => $validated['warehouseId'],
                'employee_id' => $validated['empId'],
                'notes' => $validated['notes'],
                'base_salary' => $validated['baseSalary'],
                'allowance' => $validated['allowance'],
            ]);

            DB::commit();

            session()->flash('message', 'Employee successfully updated.');
            
            return redirect()->route('admin.employees.show', $this->employee);
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Error updating employee: ' . $e->getMessage(), [
                'exception' => $e,
                'employee_id' => $this->employeeId,
                'data' => $this->only(array_keys($this->rules()))
            ]);

            session()->flash('error', 'An error occurred while updating the employee. ' . 
                ($e->getMessage() ?: 'Please try again or contact support if the problem persists.'));
            
            return null;
        }
    }
} 