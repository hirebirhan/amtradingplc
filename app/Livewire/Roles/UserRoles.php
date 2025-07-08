<?php

namespace App\Livewire\Roles;

use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Livewire\WithPagination;

class UserRoles extends Component
{
    use WithPagination;

    public $search = '';
    public $filterByRole = '';
    public $filterByStatus = '';
    public $selectedUser = null;
    public $showAssignModal = false;
    public $userRoles = [];
    public $availableRoles = [];

    protected $queryString = ['search', 'filterByRole', 'filterByStatus'];

    public function mount()
    {
        $this->availableRoles = Role::all();
    }

    public function render()
    {
        $query = User::with(['roles', 'branch', 'warehouse']);
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterByRole) {
            $query->whereHas('roles', function($q) {
                $q->where('name', $this->filterByRole);
            });
        }

        if ($this->filterByStatus) {
            $query->where('is_active', $this->filterByStatus === 'active');
        }

        $users = $query->paginate(15);

        $stats = [
            'total_users' => User::count(),
            'users_with_roles' => User::whereHas('roles')->count(),
            'users_without_roles' => User::whereDoesntHave('roles')->count(),
            'active_users' => User::where('is_active', true)->count(),
        ];

        return view('livewire.roles.user-roles', [
            'users' => $users,
            'stats' => $stats,
            'allRoles' => Role::all(),
        ]);
    }

    public function assignRoles($userId)
    {
        $this->selectedUser = User::with('roles')->find($userId);
        $this->userRoles = $this->selectedUser->roles->pluck('name')->toArray();
        $this->showAssignModal = true;
    }

    public function updateUserRoles()
    {
        if (!$this->selectedUser) {
            return;
        }

        $this->selectedUser->syncRoles($this->userRoles);
        
        session()->flash('message', 'User roles updated successfully.');
        $this->showAssignModal = false;
        $this->selectedUser = null;
        $this->userRoles = [];
        
        // Clear permission cache
        app()['cache']->forget('spatie.permission.cache');
    }

    public function removeUserRole($userId, $roleName)
    {
        $user = User::find($userId);
        if ($user && $user->hasRole($roleName)) {
            $user->removeRole($roleName);
            session()->flash('message', "Role '{$roleName}' removed from user successfully.");
            
            // Clear permission cache
            app()['cache']->forget('spatie.permission.cache');
        }
    }

    public function assignRoleToUser($userId, $roleName)
    {
        $user = User::find($userId);
        if ($user && !$user->hasRole($roleName)) {
            $user->assignRole($roleName);
            session()->flash('message', "Role '{$roleName}' assigned to user successfully.");
            
            // Clear permission cache
            app()['cache']->forget('spatie.permission.cache');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterByRole()
    {
        $this->resetPage();
    }

    public function updatingFilterByStatus()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterByRole = '';
        $this->filterByStatus = '';
        $this->resetPage();
    }

    public function getUserStatusBadge($user)
    {
        if (!$user->is_active) {
            return '<span class="badge bg-danger">Inactive</span>';
        }
        
        if ($user->roles->isEmpty()) {
            return '<span class="badge bg-warning">No Roles</span>';
        }
        
        return '<span class="badge bg-success">Active</span>';
    }

    public function getUserAssignment($user)
    {
        if ($user->branch && $user->warehouse) {
            return "Branch: {$user->branch->name}, Warehouse: {$user->warehouse->name}";
        } elseif ($user->branch) {
            return "Branch: {$user->branch->name}";
        } elseif ($user->warehouse) {
            return "Warehouse: {$user->warehouse->name}";
        }
        
        return 'No assignment';
    }
} 