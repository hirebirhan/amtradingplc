<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">User Role Assignments</h1>
            <p class="text-muted mb-0">Manage user role assignments and permissions</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-shield-alt me-1"></i> Back to Roles
        </a>
    </div>

    <!-- Alert Messages -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-6 col-md-6 col-xl-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="fas fa-users text-primary fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total Users</div>
                            <div class="h5 mb-0 fw-bold">{{ $stats['total_users'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-6 col-xl-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="fas fa-user-check text-success fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Users with Roles</div>
                            <div class="h5 mb-0 fw-bold">{{ $stats['users_with_roles'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-6 col-xl-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                <i class="fas fa-user-times text-warning fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Users without Roles</div>
                            <div class="h5 mb-0 fw-bold">{{ $stats['users_without_roles'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-6 col-xl-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                <i class="fas fa-user-circle text-info fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Active Users</div>
                            <div class="h5 mb-0 fw-bold">{{ $stats['active_users'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-lg-4 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" placeholder="Search users..."
                            wire:model.live.debounce.300ms="search">
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mt-2 mt-lg-0">
                    <select class="form-select rounded-pill" wire:model.live="filterByRole">
                        <option value="">All Roles</option>
                        @foreach($allRoles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6 mt-2 mt-lg-0">
                    <select class="form-select rounded-pill" wire:model.live="filterByStatus">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6 text-end mt-2 mt-lg-0">
                    <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                        <small class="text-muted">{{ $users->total() }} user{{ $users->total() !== 1 ? 's' : '' }}</small>
                        @if($search || $filterByRole || $filterByStatus)
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" wire:click="resetFilters">
                                <i class="fas fa-times me-1"></i> Clear
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th scope="col" class="px-4">User Information</th>
                            <th scope="col" class="text-center">Assignment</th>
                            <th scope="col" class="text-center">Roles</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-4">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="rounded-circle bg-primary bg-opacity-10 p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">{{ $user->name }}</div>
                                            <div class="text-muted small">{{ $user->email }}</div>
                                            @if($user->position)
                                                <div class="text-muted small">{{ $user->position }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <small class="text-muted">{{ $this->getUserAssignment($user) }}</small>
                                </td>
                                <td class="text-center">
                                    @if($user->roles->count() > 0)
                                        <div class="d-flex justify-content-center flex-wrap gap-1">
                                            @foreach($user->roles->take(2) as $role)
                                                <span class="badge bg-primary fs-6">{{ $role->name }}</span>
                                            @endforeach
                                            @if($user->roles->count() > 2)
                                                <span class="badge bg-secondary fs-6">+{{ $user->roles->count() - 2 }} more</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">No roles assigned</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {!! $this->getUserStatusBadge($user) !!}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                            wire:click="assignRoles({{ $user->id }})"
                                            title="Manage Roles">
                                            <i class="fas fa-user-cog"></i>
                                        </button>
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-info"
                                            title="View User">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-users fa-3x mb-3 text-muted"></i>
                                        <h5 class="text-muted">No users found</h5>
                                        @if($search || $filterByRole || $filterByStatus)
                                            <p class="text-muted">Try adjusting your search criteria</p>
                                            <button type="button" class="btn btn-outline-primary" wire:click="resetFilters">
                                                Clear Filters
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-white">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Assign Roles Modal -->
    @if($selectedUser)
        <div class="modal fade" id="assignRolesModal" wire:ignore.self @if($showAssignModal) style="display: block;" @endif tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title">
                            <i class="fas fa-user-cog me-2"></i>
                            Manage Roles for {{ $selectedUser->name }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="$set('showAssignModal', false)" aria-label="Close"></button>
                    </div>

                    <form wire:submit.prevent="updateUserRoles">
                        <div class="modal-body">
                            <!-- User Info -->
                            <div class="alert alert-info mb-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <div>
                                        <strong>{{ $selectedUser->name }}</strong> ({{ $selectedUser->email }})
                                        <br>
                                        <small>Current assignment: {{ $this->getUserAssignment($selectedUser) }}</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Role Selection -->
                            <div class="mb-3">
                                <label class="form-label h6">
                                    <i class="fas fa-shield-alt me-2"></i>Select Roles
                                </label>
                                <div class="row g-3">
                                    @foreach($availableRoles as $role)
                                        <div class="col-md-6">
                                            <div class="card border">
                                                <div class="card-body p-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                            id="role_{{ $role->id }}" 
                                                            value="{{ $role->name }}" 
                                                            wire:model="userRoles">
                                                        <label class="form-check-label fw-semibold" for="role_{{ $role->id }}">
                                                            {{ $role->name }}
                                                        </label>
                                                    </div>
                                                    @if($role->description)
                                                        <small class="text-muted">{{ $role->description }}</small>
                                                    @endif
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            {{ $role->permissions->count() }} permissions
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Current Permissions Preview -->
                            @if(!empty($userRoles))
                                <div class="mt-4">
                                    <h6 class="mb-3">
                                        <i class="fas fa-key me-2"></i>Permissions Preview
                                    </h6>
                                    @php
                                        $selectedRoles = $availableRoles->whereIn('name', $userRoles);
                                        $allPermissions = collect();
                                        foreach($selectedRoles as $role) {
                                            $allPermissions = $allPermissions->merge($role->permissions);
                                        }
                                        $uniquePermissions = $allPermissions->unique('id');
                                        $groupedPermissions = $uniquePermissions->groupBy(function ($permission) {
                                            return explode('.', $permission->name)[0];
                                        });
                                    @endphp
                                    
                                    <div class="row g-2">
                                        @foreach($groupedPermissions as $group => $permissions)
                                            <div class="col-md-6">
                                                <div class="card border-primary border">
                                                    <div class="card-header bg-primary bg-opacity-10 py-2">
                                                        <small class="fw-semibold text-capitalize">{{ $group }}</small>
                                                        <span class="badge bg-primary ms-2">{{ $permissions->count() }}</span>
                                                    </div>
                                                    <div class="card-body p-2">
                                                        @foreach($permissions->take(3) as $permission)
                                                            <div class="d-flex align-items-center mb-1">
                                                                <i class="fas fa-check text-success me-2 small"></i>
                                                                <small>{{ ucfirst(str_replace('.', ' ', $permission->name)) }}</small>
                                                            </div>
                                                        @endforeach
                                                        @if($permissions->count() > 3)
                                                            <small class="text-muted">... and {{ $permissions->count() - 3 }} more</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="$set('showAssignModal', false)">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Roles
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Scripts -->
    <script>
        // Handle modal states
        document.addEventListener('livewire:updated', () => {
            if (@this.showAssignModal) {
                document.getElementById('assignRolesModal').style.display = 'block';
                document.body.classList.add('modal-open');
                
                if (!document.querySelector('.modal-backdrop')) {
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(backdrop);
                }
            } else if (document.getElementById('assignRolesModal')) {
                document.getElementById('assignRolesModal').style.display = 'none';
                document.body.classList.remove('modal-open');
                
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }
        });
    </script>
</div> 