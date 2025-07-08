<?php

namespace App\Livewire\Roles;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\WithPagination;
use App\Models\User;

class Index extends Component
{
    use WithPagination;

    public $name;
    public $description;
    public $selectedPermissions = [];
    public $allPermissions = [];
    public $editingRole = null;
    public $showModal = false;
    public $search = '';
    public $selectedRole = null;
    public $showPermissionsModal = false;
    public $typeFilter = '';
    public $perPage = 10;

    protected $queryString = ['search'];

    protected $rules = [
        'name' => 'required|min:3|max:100|unique:roles,name',
        'description' => 'nullable|max:255',
        'selectedPermissions' => 'required|array|min:1',
    ];

    public function mount()
    {
        $this->loadPermissions();
    }

    private function loadPermissions()
    {
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
        })->sortKeys();
    }

    private function formatPermissionName($permissionName)
    {
        $parts = explode('.', $permissionName);
        $action = ucfirst($parts[1] ?? '');
        $resource = ucfirst($parts[0] ?? '');
        return "{$action} {$resource}";
    }

    public function render()
    {
        $query = Role::withCount('permissions', 'users');
        
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $roles = $query->paginate(10);

        $stats = [
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'users_with_roles' => User::whereHas('roles')->count(),
            'system_roles' => Role::whereIn('name', ['SuperAdmin', 'BranchManager', 'WarehouseUser', 'Sales'])->count(),
        ];

        // Prepare grouped permissions for the modal
        $groupedPermissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        })->map(function ($permissions, $group) {
            return $permissions->map(function ($permission) {
                $permission->display_name = $this->formatPermissionName($permission->name);
                return $permission;
            });
        });

        // Define permission icons for each group
        $permissionIcons = [
            'users' => 'people',
            'roles' => 'shield',
            'permissions' => 'key',
            'branches' => 'building',
            'warehouses' => 'warehouse',
            'categories' => 'folder',
            'items' => 'box',
            'suppliers' => 'truck',
            'customers' => 'person',
            'employees' => 'person-badge',
            'sales' => 'cart',
            'purchases' => 'bag',
            'transfers' => 'arrow-left-right',
            'expenses' => 'receipt',
            'credits' => 'credit-card',
            'bank-accounts' => 'bank',
            'reports' => 'graph-up',
            'activities' => 'activity',
            'settings' => 'gear',
            'stock' => 'box-seam',
        ];

        return view('livewire.roles.index', [
            'roles' => $roles,
            'stats' => $stats,
            'groupedPermissions' => $groupedPermissions,
            'permissionIcons' => $permissionIcons,
        ]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->editingRole = null;
        $this->showModal = true;
    }

    public function store()
    {
        $rules = $this->rules;
        
        if ($this->editingRole) {
            $role = Role::find($this->editingRole);
            $rules['name'] = 'required|min:3|max:100|unique:roles,name,' . $role->id;
        }
        
        $this->validate($rules);

        if ($this->editingRole) {
            $role = Role::find($this->editingRole);
            $role->update([
                'name' => $this->name,
                'description' => $this->description
            ]);
            $role->syncPermissions($this->selectedPermissions);
            $this->dispatch('notify', type: 'success', message: 'Role updated successfully!');
        } else {
            $role = Role::create([
                'name' => $this->name,
                'description' => $this->description
            ]);
            $role->syncPermissions($this->selectedPermissions);
            $this->dispatch('notify', type: 'success', message: 'Role created successfully!');
        }

        $this->resetInputFields();
        $this->showModal = false;
        
        // Clear permission cache
        app()['cache']->forget('spatie.permission.cache');
    }

    public function edit($id)
    {
        $role = Role::find($id);
        $this->editingRole = $id;
        $this->name = $role->name;
        $this->description = $role->description ?? '';
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showModal = true;
    }

    public function viewPermissions($id)
    {
        $this->selectedRole = Role::with('permissions')->find($id);
        $this->showPermissionsModal = true;
    }

    public function deleteRole($roleIdToDelete = null)
    {
        if (!$roleIdToDelete) {
            $this->dispatch('notify', type: 'error', message: 'No role selected for deletion.');
            return;
        }
        
        $role = Role::find($roleIdToDelete);
        if (!$role) {
            $this->dispatch('notify', type: 'error', message: 'Role not found.');
            return;
        }

        // Prevent deletion of system roles
        $systemRoles = ['SuperAdmin', 'BranchManager', 'WarehouseUser', 'Sales'];
        if (in_array($role->name, $systemRoles)) {
            $this->dispatch('notify', type: 'error', message: 'Cannot delete system roles.');
            return;
        }
        
        $usersCount = $role->users->count();
        
        if ($usersCount > 0) {
            $this->dispatch('notify', type: 'error', message: 'Cannot delete role that is assigned to users. Please reassign users first.');
        } else {
            $role->delete();
            $this->dispatch('notify', type: 'success', message: 'Role deleted successfully!');
            
            // Clear permission cache
            app()['cache']->forget('spatie.permission.cache');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->description = '';
        $this->selectedPermissions = [];
        $this->editingRole = null;
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->resetPage();
    }
} 