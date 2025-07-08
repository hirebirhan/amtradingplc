<?php

namespace App\Livewire\Roles;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Livewire\WithPagination;

class Permissions extends Component
{
    use WithPagination;

    public $name;
    public $description;
    public $search = '';
    public $filterByGroup = '';
    public $showModal = false;
    public $editingPermission = null;

    protected $queryString = ['search', 'filterByGroup'];

    protected $rules = [
        'name' => 'required|min:3|max:100|unique:permissions,name',
        'description' => 'nullable|max:255',
    ];

    public function render()
    {
        $query = Permission::query();
        
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->filterByGroup) {
            $query->where('name', 'like', $this->filterByGroup . '.%');
        }

        $permissions = $query->paginate(15);

        // Group permissions for better display
        $groupedPermissions = $permissions->getCollection()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        // Get available groups for filter
        $allGroups = Permission::all()->map(function ($permission) {
            return explode('.', $permission->name)[0];
        })->unique()->sort();

        $stats = [
            'total_permissions' => Permission::count(),
            'total_groups' => Permission::all()->groupBy(function ($p) { return explode('.', $p->name)[0]; })->count(),
            'permissions_in_roles' => Permission::whereHas('roles')->count(),
            'unused_permissions' => Permission::whereDoesntHave('roles')->count(),
        ];

        return view('livewire.roles.permissions', [
            'permissions' => $permissions,
            'groupedPermissions' => $groupedPermissions,
            'allGroups' => $allGroups,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->editingPermission = null;
        $this->showModal = true;
    }

    public function store()
    {
        $rules = $this->rules;
        
        if ($this->editingPermission) {
            $permission = Permission::find($this->editingPermission);
            $rules['name'] = 'required|min:3|max:100|unique:permissions,name,' . $permission->id;
        }
        
        $this->validate($rules);

        if ($this->editingPermission) {
            $permission = Permission::find($this->editingPermission);
            $permission->update([
                'name' => $this->name,
                'description' => $this->description
            ]);
            session()->flash('message', 'Permission updated successfully.');
        } else {
            Permission::create([
                'name' => $this->name,
                'description' => $this->description
            ]);
            session()->flash('message', 'Permission created successfully.');
        }

        $this->resetInputFields();
        $this->showModal = false;
        
        // Clear permission cache
        app()['cache']->forget('spatie.permission.cache');
    }

    public function edit($id)
    {
        $permission = Permission::find($id);
        $this->editingPermission = $id;
        $this->name = $permission->name;
        $this->description = $permission->description ?? '';
        $this->showModal = true;
    }

    public function deletePermission($permissionId)
    {
        $permission = Permission::find($permissionId);
        
        if (!$permission) {
            session()->flash('error', 'Permission not found.');
            return;
        }

        // Check if permission is assigned to any roles
        $rolesCount = $permission->roles->count();
        
        if ($rolesCount > 0) {
            session()->flash('error', 'Cannot delete permission that is assigned to roles. Please remove from roles first.');
        } else {
            $permission->delete();
            session()->flash('message', 'Permission deleted successfully.');
            
            // Clear permission cache
            app()['cache']->forget('spatie.permission.cache');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterByGroup()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterByGroup = '';
        $this->resetPage();
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->description = '';
        $this->editingPermission = null;
    }

    public function getPermissionIcon($permissionName)
    {
        $icons = [
            'users' => 'fa-users',
            'roles' => 'fa-shield-alt',
            'items' => 'fa-box',
            'categories' => 'fa-tags',
            'branches' => 'fa-building',
            'warehouses' => 'fa-warehouse',
            'employees' => 'fa-user-tie',
            'stock' => 'fa-cubes',
            'purchases' => 'fa-shopping-cart',
            'sales' => 'fa-cash-register',
            'customers' => 'fa-user-friends',
            'suppliers' => 'fa-truck',
            'returns' => 'fa-undo',
            'transfers' => 'fa-exchange-alt',
            'reports' => 'fa-chart-bar',
            'settings' => 'fa-cog',
            'payments' => 'fa-credit-card',
            'credits' => 'fa-file-invoice-dollar',
            'expenses' => 'fa-receipt'
        ];

        $group = explode('.', $permissionName)[0];
        return $icons[$group] ?? 'fa-key';
    }
} 