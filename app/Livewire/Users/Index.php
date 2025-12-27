<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Models\Branch;
use App\Models\Warehouse;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $branchFilter = '';
    public $warehouseFilter = '';
    public $showInactive = false;
    public $perPage = 10;
    public $sortField = 'name';
    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'branchFilter' => ['except' => ''],
        'warehouseFilter' => ['except' => ''],
        'showInactive' => ['except' => false],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function updatingBranchFilter()
    {
        $this->resetPage();
        // Clear warehouse filter if branch is changed
        if ($this->warehouseFilter) {
            $this->warehouseFilter = '';
        }
    }

    public function updatingWarehouseFilter()
    {
        $this->resetPage();
    }

    public function updatingShowInactive()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    /**
     * Get the total count of active users
     */
    public function getActiveUsersCountProperty()
    {
        $currentUser = auth()->user();
        
        if ($currentUser->isBranchManager() && $currentUser->branch_id) {
            return User::where('is_active', true)
                ->where(function($query) use ($currentUser) {
                    $query->where('branch_id', $currentUser->branch_id)
                          ->orWhereHas('warehouse.branches', function($branchQuery) use ($currentUser) {
                              $branchQuery->where('branches.id', $currentUser->branch_id);
                          });
                })
                ->count();
        }
        
        return User::where('is_active', true)->count();
    }

    /**
     * Get the total count of inactive users
     */
    public function getInactiveUsersCountProperty()
    {
        $currentUser = auth()->user();
        
        if ($currentUser->isBranchManager() && $currentUser->branch_id) {
            return User::where('is_active', false)
                ->where(function($query) use ($currentUser) {
                    $query->where('branch_id', $currentUser->branch_id)
                          ->orWhereHas('warehouse.branches', function($branchQuery) use ($currentUser) {
                              $branchQuery->where('branches.id', $currentUser->branch_id);
                          });
                })
                ->count();
        }
        
        return User::where('is_active', false)->count();
    }

    /**
     * Get the count of admins (users with SuperAdmin role)
     */
    public function getAdminUsersCountProperty()
    {
        $currentUser = auth()->user();
        
        if ($currentUser->isBranchManager() && $currentUser->branch_id) {
            return User::whereHas('roles', function($query) {
                $query->where('name', 'SuperAdmin');
            })
            ->where(function($query) use ($currentUser) {
                $query->where('branch_id', $currentUser->branch_id)
                      ->orWhereHas('warehouse.branches', function($branchQuery) use ($currentUser) {
                          $branchQuery->where('branches.id', $currentUser->branch_id);
                      });
            })
            ->count();
        }
        
        return User::whereHas('roles', function($query) {
            $query->where('name', 'SuperAdmin');
        })->count();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Get filtered users query based on search and filters
     */
    protected function getUsersQuery()
    {
        $currentUser = auth()->user();
        
        return User::query()
            ->with(['roles', 'branch', 'warehouse'])
            ->when(!$this->showInactive, function($query) {
                $query->where('is_active', true);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%')
                        ->orWhere('position', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('name', $this->roleFilter);
                });
            })
            ->when($this->branchFilter, function ($query) {
                $query->where('branch_id', $this->branchFilter);
            })
            ->when($this->warehouseFilter, function ($query) {
                $query->where('warehouse_id', $this->warehouseFilter);
            })
            // Apply role-based filtering for branch managers
            ->when($currentUser->isBranchManager() && $currentUser->branch_id, function($query) use ($currentUser) {
                $query->where(function($q) use ($currentUser) {
                    // Show users from their branch
                    $q->where('branch_id', $currentUser->branch_id)
                      // Also show users assigned to warehouses in their branch
                      ->orWhereHas('warehouse.branches', function($branchQuery) use ($currentUser) {
                          $branchQuery->where('branches.id', $currentUser->branch_id);
                      });
                });
            })
            ->orderBy($this->sortField, $this->sortDirection);
    }

    public function render()
    {
        $currentUser = auth()->user();
        $users = $this->getUsersQuery()->paginate($this->perPage);

        // Filter branches and warehouses based on user role
        if ($currentUser->isBranchManager() && $currentUser->branch_id) {
            // Branch managers only see their own branch
            $branches = Branch::where('id', $currentUser->branch_id)->orderBy('name')->get();
            
            // Show warehouses in their branch
            $warehouses = Warehouse::whereHas('branches', function($query) use ($currentUser) {
                $query->where('branches.id', $currentUser->branch_id);
            })->orderBy('name')->get();
        } else {
            // Super admins see all branches and warehouses
            $branches = Branch::orderBy('name')->get();
            $warehouses = $this->branchFilter
                ? Warehouse::whereHas('branches', function($query) {
                    $query->where('branches.id', $this->branchFilter);
                })->orderBy('name')->get()
                : Warehouse::orderBy('name')->get();
        }

        return view('livewire.users.index', [
            'users' => $users,
            'roles' => Role::all(),
            'branches' => $branches,
            'warehouses' => $warehouses,
            'activeUsersCount' => $this->activeUsersCount,
            'inactiveUsersCount' => $this->inactiveUsersCount,
            'adminUsersCount' => $this->adminUsersCount
        ])->title('User Management');
    }

    public function deleteUser(User $user)
    {
        // Check policy authorization
        if (!auth()->user()->can('delete', $user)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'You do not have permission to delete this user.']);
            return;
        }

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'You cannot delete your own account!']);
            return;
        }

        $user->delete();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'User deleted successfully.']);
    }

    public function toggleStatus(User $user)
    {
        // Check policy authorization
        if (!auth()->user()->can('update', $user)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'You do not have permission to modify this user.']);
            return;
        }

        // Prevent deactivating yourself
        if ($user->id === auth()->id() && $user->is_active) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'You cannot deactivate your own account!']);
            return;
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activated' : 'deactivated';
        $this->dispatch('notify', ['type' => 'success', 'message' => "User {$status} successfully."]);
    }
}
