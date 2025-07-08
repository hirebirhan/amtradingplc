<?php

namespace App\Livewire\Users;

use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class Edit extends Component
{
    public User $user;
    
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $phone = '';
    public $position = '';
    public $branch_id = '';
    public $warehouse_id = '';
    public $selectedRoles = [];
    public $is_active = true;

    public $availableWarehouses = [];

    public function mount(User $user)
    {
        // Check if current user can edit this user
        $currentUser = auth()->user();
        if ($currentUser->isBranchManager() && $currentUser->branch_id) {
            // Branch managers can only edit users from their own branch
            if ($user->branch_id != $currentUser->branch_id && 
                !$user->warehouse?->branches->contains('id', $currentUser->branch_id)) {
                abort(403, 'You can only edit users from your own branch.');
            }
        }
        
        $this->user = $user->load(['roles', 'branch', 'warehouse']);
        
        // Fill component properties with user data
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->position = $user->position;
        $this->branch_id = $user->branch_id;
        $this->warehouse_id = $user->warehouse_id;
        $this->is_active = $user->is_active;
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
        
        // Load available warehouses if branch is selected
        if ($this->branch_id) {
            $this->loadWarehousesForBranch($this->branch_id);
        }
    }

    public function updatedBranchId($value)
    {
        // Ensure empty strings become null
        if ($value === '') {
            $this->branch_id = null;
        }
        
        $this->warehouse_id = '';
        $this->loadWarehousesForBranch($value);
    }

    public function updatedWarehouseId($value)
    {
        // Ensure empty strings become null
        if ($value === '') {
            $this->warehouse_id = null;
        }
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

    public function rules()
    {
        $currentUser = auth()->user();
        
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $this->user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:50',
            'branch_id' => 'nullable|exists:branches,id',
            'warehouse_id' => [
                'nullable',
                'exists:warehouses,id',
                function ($attribute, $value, $fail) {
                    // Convert empty string to null for validation
                    if ($value === '') {
                        $value = null;
                    }
                    
                    if ($value && $this->branch_id) {
                        // Check if warehouse belongs to the selected branch
                        $branch = Branch::find($this->branch_id);
                        if ($branch && !$branch->warehouses->contains('id', $value)) {
                            $fail('The selected warehouse must belong to the selected branch.');
                        }
                    }
                },
            ],
            'selectedRoles' => 'required|array|min:1',
            'selectedRoles.*' => 'exists:roles,name',
            'is_active' => 'boolean',
        ];
        
        // Additional validation for branch managers
        if ($currentUser->isBranchManager() && $currentUser->branch_id) {
            $rules['branch_id'][] = function ($attribute, $value, $fail) use ($currentUser) {
                if ($value && $value != $currentUser->branch_id) {
                    $fail('You can only assign users to your own branch.');
                }
            };
        }
        
        return $rules;
    }

    public function save()
    {
        $currentUser = auth()->user();
        
        // Additional security check for branch managers
        if ($currentUser->isBranchManager() && $currentUser->branch_id) {
            if ($this->branch_id && $this->branch_id != $currentUser->branch_id) {
                $this->dispatch('toast', [
                    'type' => 'error',
                    'message' => 'You can only assign users to your own branch.'
                ]);
                return;
            }
        }
        
        $validated = $this->validate();

        // Remove password_confirmation from validated data
        unset($validated['password_confirmation']);

        // Handle password update
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Convert empty strings to null for foreign key fields
        $validated['branch_id'] = $validated['branch_id'] ?: null;
        $validated['warehouse_id'] = $validated['warehouse_id'] ?: null;

        // Extract roles
        $roles = $validated['selectedRoles'];
        unset($validated['selectedRoles']);

        // Update user
        $this->user->update($validated);

        // Sync roles
        $this->user->syncRoles($roles);

        // Flash message and redirect
        $this->dispatch('toast', [
            'type' => 'success',
            'message' => "User '{$this->user->name}' updated successfully."
        ]);
        
        return redirect()->route('admin.users.show', $this->user);
    }

    public function render()
    {
        $currentUser = auth()->user();
        
        // Filter branches based on user role
        if ($currentUser->isBranchManager() && $currentUser->branch_id) {
            // Branch managers can only assign users to their own branch
            $branches = Branch::where('id', $currentUser->branch_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            // Super admins can assign users to any branch
            $branches = Branch::where('is_active', true)->orderBy('name')->get();
        }
        
        return view('livewire.users.edit', [
            'branches' => $branches,
            'warehouses' => $this->availableWarehouses,
            'roles' => Role::all(),
        ])->title('Edit User - ' . $this->user->name);
    }
}
