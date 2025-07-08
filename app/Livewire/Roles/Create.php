<?php

declare(strict_types=1);

namespace App\Livewire\Roles;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class Create extends Component
{
    public string $name = '';
    public string $description = '';
    public array $selectedPermissions = [];
    public array $allPermissions = [];
    public bool $isSubmitting = false;

    protected $rules = [
        'name' => 'required|min:3|max:100|unique:roles,name|regex:/^[a-zA-Z0-9\s\-_]+$/',
        'description' => 'nullable|max:255',
        'selectedPermissions' => 'required|array|min:1',
        'selectedPermissions.*' => 'exists:permissions,name',
    ];

    protected $messages = [
        'name.required' => 'Role name is required.',
        'name.min' => 'Role name must be at least 3 characters.',
        'name.max' => 'Role name cannot exceed 100 characters.',
        'name.unique' => 'A role with this name already exists.',
        'name.regex' => 'Role name can only contain letters, numbers, spaces, hyphens, and underscores.',
        'description.max' => 'Description cannot exceed 255 characters.',
        'selectedPermissions.required' => 'Please select at least one permission.',
        'selectedPermissions.min' => 'Please select at least one permission.',
        'selectedPermissions.*.exists' => 'One or more selected permissions are invalid.',
    ];

    public function mount(): void
    {
        $this->loadPermissions();
    }

    private function loadPermissions(): void
    {
        try {
            $this->allPermissions = Permission::all()->groupBy(function ($permission) {
                return explode('.', $permission->name)[0];
            })->map(function ($permissions, $group) {
                return [
                    'group' => $group,
                    'permissions' => $permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'display_name' => $this->formatPermissionName($permission->name)
                        ];
                    })
                ];
            })->sortKeys()->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to load permissions for role creation', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            session()->flash('error', 'Failed to load permissions. Please refresh the page and try again.');
        }
    }

    private function formatPermissionName($permissionName): string
    {
        $parts = explode('.', $permissionName);
        $action = ucfirst($parts[1] ?? '');
        $resource = ucfirst($parts[0] ?? '');
        return "$action $resource";
    }

    public function getPermissionIcon($group): string
    {
        return match($group) {
            'users' => 'people',
            'roles' => 'shield',
            'permissions' => 'key',
            'items' => 'box',
            'categories' => 'tags',
            'suppliers' => 'truck',
            'customers' => 'person-lines-fill',
            'employees' => 'person-badge',
            'branches' => 'building',
            'warehouses' => 'archive',
            'purchases' => 'cart-plus',
            'sales' => 'cart-check',
            'transfers' => 'arrow-left-right',
            'credits' => 'credit-card',
            'expenses' => 'cash-stack',
            'bank-accounts' => 'bank',
            'reports' => 'graph-up',
            'activities' => 'activity',
            'stock-card' => 'clipboard-data',
            'returns' => 'arrow-return-left',
            default => 'gear'
        };
    }

    public function updatedName(): void
    {
        $this->validateOnly('name');
    }

    public function updatedDescription(): void
    {
        $this->validateOnly('description');
    }

    public function updatedSelectedPermissions(): void
    {
        $this->validateOnly('selectedPermissions');
    }

    public function store(): void
    {
        try {
            $this->isSubmitting = true;
            
            // Validate all fields
            $this->validate();
            
            // Check if role name is reserved
            $reservedNames = ['SuperAdmin', 'BranchManager', 'WarehouseUser', 'Sales'];
            if (in_array($this->name, $reservedNames)) {
                $this->addError('name', 'This role name is reserved for system use. Please choose a different name.');
                return;
            }

            // Create the role
            $role = Role::create([
                'name' => trim($this->name),
                'description' => trim($this->description),
            ]);

            // Assign permissions
            $role->syncPermissions($this->selectedPermissions);

            // Log the action
            Log::info('Role created successfully', [
                'role_name' => $role->name,
                'permissions_count' => count($this->selectedPermissions),
                'created_by' => auth()->id(),
                'role_id' => $role->id
            ]);

            // Success message
            session()->flash('success', "Role '{$role->name}' created successfully with " . count($this->selectedPermissions) . " permissions assigned.");

            // Redirect to roles index
            $this->redirect(route('admin.roles.index'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors are handled by Livewire automatically
            Log::warning('Role creation validation failed', [
                'errors' => $e->errors(),
                'user_id' => auth()->id()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create role', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'role_data' => [
                    'name' => $this->name,
                    'permissions_count' => count($this->selectedPermissions)
                ]
            ]);
            
            session()->flash('error', 'Failed to create role. Please try again or contact support if the problem persists.');
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function cancel(): void
    {
        if (!empty($this->name) || !empty($this->description) || !empty($this->selectedPermissions)) {
            // Show confirmation dialog
            $this->dispatch('show-confirmation', [
                'title' => 'Discard Changes?',
                'message' => 'You have unsaved changes. Are you sure you want to leave?',
                'confirmText' => 'Yes, Discard',
                'cancelText' => 'Stay',
                'action' => 'cancel-role-creation'
            ]);
        } else {
            $this->redirect(route('admin.roles.index'));
        }
    }

    public function confirmCancel(): void
    {
        $this->redirect(route('admin.roles.index'));
    }

    public function render()
    {
        return view('livewire.roles.create');
    }
} 