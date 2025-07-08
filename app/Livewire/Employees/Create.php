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
class Create extends Component
{
    use WithFileUploads;

    // Basic Information
    public $firstName;
    public $lastName;
    public $email;
    public $phone;
    public $birthDate;
    public $address;

    // Employment Information
    public $position;
    public $department;
    public $hireDate;
    public $branchId;
    public $warehouseId;

    // Salary Information
    public $baseSalary;
    public $allowance;

    // Emergency Contact
    public $emergencyContact;
    public $emergencyPhone;
    public $notes;

    public function mount()
    {
        // Set default hire date to today
        $this->hireDate = now()->format('Y-m-d');
    }

    protected function rules()
    {
        return [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email',
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
            'notes' => 'nullable|string',
            'baseSalary' => 'required|numeric|min:0',
            'allowance' => 'nullable|numeric|min:0',
        ];
    }

    protected $validationAttributes = [
        'firstName' => 'first name',
        'lastName' => 'last name',
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
        return view('livewire.employees.create', [
            'branches' => Branch::orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'departments' => Department::toArray(),
        ])->title('Create Employee');
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

    public function save()
    {
        try {
            DB::beginTransaction();

            $validated = $this->validate();

            // Create employee record
            $employee = Employee::create([
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
                'status' => Status::ACTIVE->value,
                'notes' => $validated['notes'],
                'base_salary' => $validated['baseSalary'],
                'allowance' => $validated['allowance'],
            ]);

            DB::commit();

            session()->flash('message', 'Employee successfully created.');
            
            return redirect()->route('admin.employees.index');
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Error creating employee: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $this->only(array_keys($this->rules()))
            ]);

            session()->flash('error', 'An error occurred while creating the employee. ' . 
                ($e->getMessage() ?: 'Please try again or contact support if the problem persists.'));
            
            return null;
        }
    }
} 