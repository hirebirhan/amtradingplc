{{-- Clean Roles Management Page --}}
<div>
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Role Management</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Manage user roles and permissions to control access to system features
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @can('roles.create')
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">New Role</span>
            </a>
            @endcan
        </div>
    </div>

    @can('roles.view')
    <!-- Main Content Card -->
    <div class="card border-0 shadow-sm">
        <!-- Filters -->
        <div class="p-4 border-bottom">
            <div class="row g-3">
                <!-- Search -->
                <div class="col-12 col-md-4">
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="Search roles..." wire:model.live.debounce.300ms="search">
                        @if($search)
                            <button class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 text-muted" type="button" wire:click="$set('search', '')">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>
                </div>
                
                <!-- Type Filter -->
                <div class="col-6 col-md-2">
                    <select class="form-select" wire:model.live="typeFilter">
                        <option value="">All Types</option>
                        <option value="system">System</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>

                
                <!-- Reset Button -->
                <div class="col-12 col-md-4">
                    <button class="btn btn-outline-secondary btn-sm w-100" wire:click="$set('search', ''); $set('typeFilter', '');">
                        <i class="bi bi-arrow-clockwise me-1"></i>
                        Reset Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Desktop Table View -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">Role Info</th>
                            <th class="text-center">Permissions</th>
                            <th class="text-center">Users</th>
                            <th class="text-center">Type</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="fw-medium small">{{ $role->name }}</span>
                                        </div>
                                    </div>
                                    @if($role->description)
                                        <div class="text-secondary small mt-1">{{ $role->description }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="text-primary-emphasis">
                                        {{ $role->permissions_count }} Permissions
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="text-info-emphasis">
                                        {{ $role->users_count }} Users
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if(in_array($role->name, ['SuperAdmin', 'BranchManager', 'WarehouseUser', 'Sales']))
                                        <span class="text-warning-emphasis">System</span>
                                    @else
                                        <span class="text-secondary-emphasis">Custom</span>
                                    @endif
                                </td>
                                <td class="text-end px-4 py-3">
                                    <div class="btn-group btn-group-sm">
                                        @can('roles.edit')
                                        <button type="button" class="btn btn-outline-primary" wire:click="edit({{ $role->id }})" title="Edit Role">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @endcan
                                        @if(!in_array($role->name, ['SuperAdmin', 'BranchManager', 'WarehouseUser', 'Sales']))
                                            @can('roles.delete')
                                            <button type="button" class="btn btn-outline-danger" title="Delete"
                                                wire:click="delete({{ $role->id }})"
                                                wire:confirm="Are you sure you want to delete {{ $role->name }}? This action cannot be undone.">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="mb-3">
                                            <i class="bi bi-shield-x text-secondary fs-1"></i>
                                        </div>
                                        <h5 class="text-secondary">No roles found</h5>
                                        @if($search || $typeFilter)
                                            <p class="text-secondary">Try adjusting your search criteria</p>
                                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="$set('search', ''); $set('typeFilter', '');">
                                                Clear Filters
                                            </button>
                                        @else
                                            <p class="text-secondary">Start by adding your first role</p>
                                            @can('roles.create')
                                            <button type="button" class="btn btn-primary btn-sm" wire:click="create">
                                                <i class="bi bi-plus-lg me-1"></i> Add First Role
                                            </button>
                                            @else
                                            <p class="small text-secondary">Contact your administrator for access</p>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($roles->hasPages())
                <div class="border-top py-3 px-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                        <div class="small text-secondary">
                            Showing {{ $roles->firstItem() ?? 0 }} to {{ $roles->lastItem() ?? 0 }} of {{ $roles->total() }} roles
                        </div>
                        <div>
                            {{ $roles->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @else
    <!-- Access Denied Message -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-lock text-secondary display-4"></i>
                    </div>
                    <h4 class="text-secondary mb-3">Access Denied</h4>
                    <p class="text-secondary mb-4">You don't have permission to view roles and permissions.</p>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    <!-- Role Form Modal -->
    @if($showModal)
                                <div class="modal fade show" id="roleFormModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title text-dark">
                        <i class="bi bi-shield me-2 text-primary"></i>
                        {{ $editingRole ? 'Edit Role' : 'Create New Role' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)" aria-label="Close"></button>
                </div>

                <form wire:submit="store">
                    <div class="modal-body">
                        <!-- Role Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-dark">Role Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                        wire:model="name" placeholder="Enter role name">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                        <div class="mb-3">
                                    <label class="form-label text-dark">Description</label>
                                    <input type="text" class="form-control" 
                                        wire:model="description" placeholder="Brief description of the role">
                                    </div>
                            </div>
                        </div>

                        <!-- Permissions Grid -->
                        <div class="row g-3">
                            @foreach($groupedPermissions as $group => $permissions)
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100 border">
                                        <div class="card-header bg-white border-bottom py-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                    <i class="bi bi-{{ $permissionIcons[$group] ?? 'gear' }} me-2 text-primary"></i>
                                                <strong class="text-capitalize text-dark">{{ $group }}</strong>
                                                </div>
                                                <span class="badge bg-primary">{{ count($permissions) }}</span>
                                            </div>
                                        </div>
                                        <div class="card-body p-3">
                                            @foreach($permissions as $permission)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                        id="perm_{{ $permission->id }}" 
                                                        value="{{ $permission->name }}" 
                                                        wire:model="selectedPermissions">
                                                    <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                        {{ $permission->display_name }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="modal-footer bg-white border-top">
                        <button type="button" class="btn btn-outline-secondary" wire:click="$set('showModal', false)">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            {{ $editingRole ? 'Update Role' : 'Create Role' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div> 