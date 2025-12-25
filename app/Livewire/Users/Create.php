<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Models\Branch;
use App\Models\Warehouse;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Rules\PhoneNumber;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Layout('components.layouts.app')]
class Create extends Component
{
    use AuthorizesRequests;

    public $form = [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
        'phone' => '',
        'position' => '',
        'branch_id' => '',
        'warehouse_id' => '',
        'role' => '',
    ];

    public $isSubmitting = false;
    public $availableWarehouses = [];

    public function mount()
    {
        $currentUser = auth()->user();
        
        // Auto-select branch for branch managers
        if ($currentUser->isBranchManager() && $currentUser->branch_id) {
            $this->form['branch_id'] = $currentUser->branch_id;
            $this->loadWarehousesForBranch($currentUser->branch_id);
        }
    }

    protected function rules()
    {
        $currentUser = auth()->user();
        
        $rules = [
            'form.name' => 'required|string|max:255|min:2',
            'form.email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at')
            ],
            'form.password' => 'required|string|min:8|confirmed',
            'form.password_confirmation' => 'required|string|min:8',
            'form.phone' => ['nullable', 'string', 'max:20', new PhoneNumber()],
            'form.position' => 'nullable|string|max:100',
            'form.branch_id' => 'nullable|exists:branches,id',
            'form.warehouse_id' => [
                'nullable',
                'exists:warehouses,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->form['branch_id']) {
                        $branch = Branch::find($this->form['branch_id']);
                        if ($branch && !$branch->warehouses->contains('id', $value)) {
                            $fail('The selected warehouse must belong to the selected branch.');
                        }
                    }
                },
            ],
            'form.role' => 'required|exists:roles,name',
        ];
        
        // Additional validation for branch managers
        if ($currentUser->isBranchManager() && $currentUser->branch_id) {
            $rules['form.branch_id'][] = function ($attribute, $value, $fail) use ($currentUser) {
                if ($value != $currentUser->branch_id) {
                    $fail('You can only create users in your own branch.');
                }
            };
        }
        
        return $rules;
    }

    protected $messages = [
        'form.name.required' => 'User name is required.',
        'form.name.min' => 'User name must be at least 2 characters.',
        'form.name.max' => 'User name cannot exceed 255 characters.',
        'form.email.required' => 'Email address is required.',
        'form.email.email' => 'Please enter a valid email address.',
        'form.email.unique' => 'This email is already registered for another user.',
        'form.email.max' => 'Email cannot exceed 255 characters.',
        'form.password.required' => 'Password is required.',
        'form.password.min' => 'Password must be at least 8 characters.',
        'form.password.confirmed' => 'Password confirmation does not match.',
        'form.password_confirmation.required' => 'Password confirmation is required.',
        'form.password_confirmation.required' => 'Password confirmation is required.',
        'form.position.max' => 'Position cannot exceed 100 characters.',
        'form.branch_id.exists' => 'Selected branch is invalid.',
        'form.warehouse_id.exists' => 'Selected warehouse is invalid.',
        'form.role.required' => 'Please select a user role.',
        'form.role.exists' => 'Selected role is invalid.',
    ];

    public function updated($propertyName)
    {
        // Real-time validation for specific fields
        if (in_array($propertyName, [
            'form.name', 
            'form.email', 
            'form.password',
            'form.password_confirmation',
            'form.phone', 
            'form.position',
            'form.branch_id',
            'form.warehouse_id',
            'form.role'
        ])) {
            try {
                $this->validateOnly($propertyName);
            } catch (\Illuminate\Validation\ValidationException $e) {
                // Validation errors will be automatically displayed
            }
        }

        // Auto-format phone number
        if ($propertyName === 'form.phone') {
            $this->form['phone'] = $this->formatPhoneNumber($this->form['phone']);
        }

        // Auto-capitalize name
        if ($propertyName === 'form.name') {
            $this->form['name'] = ucwords(strtolower($this->form['name']));
        }

        // Auto-capitalize position
        if ($propertyName === 'form.position') {
            $this->form['position'] = ucwords(strtolower($this->form['position']));
        }

        // Handle branch change
        if ($propertyName === 'form.branch_id') {
            $this->form['warehouse_id'] = '';
            $this->loadWarehousesForBranch($this->form['branch_id']);
        }
    }

    private function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Basic Ethiopian phone number formatting
        if (strlen($phone) === 10 && !str_starts_with($phone, '+')) {
            $phone = '+251' . substr($phone, 1);
        }
        
        return $phone;
    }

    private function loadWarehousesForBranch($branchId)
    {
        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch) {
                $this->availableWarehouses = $branch->warehouses()
                    ->orderBy('warehouses.name')
                    ->get();
            } else {
                $this->availableWarehouses = [];
            }
        } else {
            $this->availableWarehouses = [];
        }
    }

    public function create()
    {
        // Prevent double submission
        if ($this->isSubmitting) {
            return;
        }

        // Check permission
        if (!Auth::user()->can('create', User::class)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'You are not authorized to create users.',
            ]);
            return;
        }

        $this->isSubmitting = true;

        try {
            // Validate form
            $this->validate();

            // Get the role
            $role = Role::findByName($this->form['role']);
            if (!$role) {
                throw new \Exception('Selected role not found');
            }

            DB::beginTransaction();

            // Prepare user data
            $userData = [
                'name' => $this->form['name'],
                'email' => $this->form['email'],
                'phone' => empty($this->form['phone']) ? null : $this->form['phone'],
                'password' => Hash::make($this->form['password']),
                'email_verified_at' => now(),
                'is_active' => $this->form['is_active'] ?? true,
                'branch_id' => $this->form['branch_id'] ?: null,
            ];

            $user = User::create($userData);

            // Assign role
            $user->assignRole($role);

            DB::commit();

            // Reset form
            $this->resetForm();

            $this->dispatch('toast', [
                'type' => 'success',
                'message' => 'User created successfully!',
            ]);

            return redirect()->route('admin.users.index');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->isSubmitting = false;
            throw $e;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->isSubmitting = false;
            
            \Log::error('User creation failed', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id(),
            ]);
            
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'An error occurred while creating the user. Please try again.',
            ]);
        }
    }

    public function resetForm()
    {
        $this->form = [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
            'phone' => '',
            'position' => '',
            'branch_id' => '',
            'warehouse_id' => '',
            'role' => '',
        ];
        
        $this->isSubmitting = false;
        $this->availableWarehouses = [];
        $this->resetValidation();
    }

    public function getRolesProperty()
    {
        return Role::orderBy('name')->get()->map(function($role) {
            return [
                'name' => $role->name,
                'label' => ucwords(str_replace('_', ' ', $role->name)),
                'description' => $this->getRoleDescription($role->name)
            ];
        });
    }

    private function getRoleDescription($roleName)
    {
        $descriptions = [
            'SuperAdmin' => 'Full system access with all permissions',
            'BranchManager' => 'Manages branch operations and staff',
            'WarehouseUser' => 'Handles warehouse operations and inventory',

            'Sales' => 'Manages sales transactions and customer relations'
        ];

        return $descriptions[$roleName] ?? 'Custom role with specific permissions';
    }

    public function render()
    {
        $currentUser = auth()->user();
        
        // Filter branches based on user role
        if ($currentUser->isBranchManager() && $currentUser->branch_id) {
            // Branch managers can only create users in their own branch
            $branches = Branch::where('id', $currentUser->branch_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            // Super admins can create users in any branch
            $branches = Branch::where('is_active', true)->orderBy('name')->get();
        }
        
        return view('livewire.users.create', [
            'roles' => $this->roles,
            'branches' => $branches,
            'warehouses' => $this->availableWarehouses,
        ])->title('Create User');
    }
}
