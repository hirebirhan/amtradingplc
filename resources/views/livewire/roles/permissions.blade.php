<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Permissions Management</h1>
            <p class="text-muted mb-0">Manage system permissions and their assignments</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-shield-alt me-1"></i> Back to Roles
            </a>
            <button type="button" class="btn btn-primary" wire:click="create">
                <i class="fas fa-plus me-1"></i> Create Permission
            </button>
        </div>
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
                                <i class="fas fa-key text-primary fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total Permissions</div>
                            <div class="h5 mb-0 fw-bold">{{ $stats['total_permissions'] }}</div>
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
                                <i class="fas fa-layer-group text-success fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Permission Groups</div>
                            <div class="h5 mb-0 fw-bold">{{ $stats['total_groups'] }}</div>
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
                                <i class="fas fa-link text-info fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Assigned to Roles</div>
                            <div class="h5 mb-0 fw-bold">{{ $stats['permissions_in_roles'] }}</div>
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
                                <i class="fas fa-unlink text-warning fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Unused Permissions</div>
                            <div class="h5 mb-0 fw-bold">{{ $stats['unused_permissions'] }}</div>
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
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" placeholder="Search permissions..."
                            wire:model.live.debounce.300ms="search">
                    </div>
                </div>
                <div class="col-md-3 mt-2 mt-md-0">
                    <select class="form-select rounded-pill" wire:model.live="filterByGroup">
                        <option value="">All Groups</option>
                        @foreach($allGroups as $group)
                            <option value="{{ $group }}">{{ ucfirst($group) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 text-end mt-2 mt-md-0">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <small class="text-muted">{{ $permissions->total() }} permission{{ $permissions->total() !== 1 ? 's' : '' }}</small>
                        @if($search || $filterByGroup)
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" wire:click="resetFilters">
                                <i class="fas fa-times me-1"></i> Clear
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th scope="col" class="px-4">Permission</th>
                            <th scope="col" class="text-center">Group</th>
                            <th scope="col" class="text-center">Assigned Roles</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permissions as $permission)
                            <tr>
                                <td class="px-4">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            @php
                                                $icon = $this->getPermissionIcon($permission->name);
                                                $group = explode('.', $permission->name)[0];
                                            @endphp
                                            <div class="rounded-circle bg-primary bg-opacity-10 p-2">
                                                <i class="fas {{ $icon }} text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">{{ $permission->name }}</div>
                                            @if($permission->description)
                                                <div class="text-muted small">{{ Str::limit($permission->description, 60) }}</div>
                                            @else
                                                <div class="text-muted small fst-italic">{{ ucfirst(str_replace('.', ' ', $permission->name)) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        {{ ucfirst(explode('.', $permission->name)[0]) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($permission->roles->count() > 0)
                                        <div class="d-flex justify-content-center flex-wrap gap-1">
                                            @foreach($permission->roles->take(3) as $role)
                                                <span class="badge bg-primary fs-6">{{ $role->name }}</span>
                                            @endforeach
                                            @if($permission->roles->count() > 3)
                                                <span class="badge bg-secondary fs-6">+{{ $permission->roles->count() - 3 }} more</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">Unassigned</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($permission->roles->count() > 0)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-warning">Unused</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                            wire:click="edit({{ $permission->id }})"
                                            title="Edit Permission">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        @if($permission->roles->count() === 0)
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmPermissionDelete({{ $permission->id }}, '{{ $permission->name }}')"
                                                title="Delete Permission">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-key fa-3x mb-3 text-muted"></i>
                                        <h5 class="text-muted">No permissions found</h5>
                                        @if($search || $filterByGroup)
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
        @if($permissions->hasPages())
            <div class="card-footer bg-white">
                {{ $permissions->links() }}
            </div>
        @endif
    </div>

    <!-- Permission Form Modal -->
    <div class="modal fade" id="permissionFormModal" wire:ignore.self @if($showModal) style="display: block;" @endif tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>
                        {{ $editingPermission ? 'Edit Permission' : 'Create New Permission' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)" aria-label="Close"></button>
                </div>

                <form wire:submit.prevent="store">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Permission Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                id="name" wire:model="name" 
                                placeholder="e.g., users.view, items.create">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Use format: <code>resource.action</code> (e.g., users.view, items.create, reports.export)
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                id="description" wire:model="description" rows="3"
                                placeholder="Describe what this permission allows..."></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            {{ $editingPermission ? 'Update Permission' : 'Create Permission' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deletePermissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <div class="rounded-circle bg-danger bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px">
                            <i class="fas fa-exclamation-triangle text-danger fa-2x"></i>
                        </div>
                        <h5 class="modal-title fw-semibold">Confirm Delete</h5>
                    </div>
                    <p>Are you sure you want to delete the permission <span id="permissionToDelete" class="fw-medium text-danger"></span>?</p>
                    <p class="text-muted small">This action cannot be undone and will only work if the permission is not assigned to any roles.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger px-4" onclick="deletePermission()">
                        <i class="fas fa-trash me-1"></i> Delete Permission
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        let permissionIdToDelete = null;
        
        function confirmPermissionDelete(id, name) {
            permissionIdToDelete = id;
            document.getElementById('permissionToDelete').textContent = name;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deletePermissionModal'));
            deleteModal.show();
        }
        
        function deletePermission() {
            if (permissionIdToDelete) {
                @this.call('deletePermission', permissionIdToDelete);
                bootstrap.Modal.getInstance(document.getElementById('deletePermissionModal')).hide();
                permissionIdToDelete = null;
            }
        }

        // Handle modal states
        document.addEventListener('livewire:updated', () => {
            if (@this.showModal) {
                document.getElementById('permissionFormModal').style.display = 'block';
                document.body.classList.add('modal-open');
                
                if (!document.querySelector('.modal-backdrop')) {
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(backdrop);
                }
            } else {
                document.getElementById('permissionFormModal').style.display = 'none';
                document.body.classList.remove('modal-open');
                
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }
        });
    </script>
</div> 