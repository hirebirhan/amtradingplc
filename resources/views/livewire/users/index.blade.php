{{-- Clean Users Management Page --}}
<div>
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Users</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Manage system users, assign roles and permissions, and control access levels
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @can('users.create')
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">New User</span>
            </a>
            @endcan
        </div>
    </div>

    @can('users.view')
    <!-- Main Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Filters Section -->
            <div class="p-4 border-bottom">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" placeholder="Search users..." wire:model.live.debounce.300ms="search">
                            @if($search)
                                <button class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2" type="button" wire:click="$set('search', '')" style="background: none; border: none;">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="col-6 col-md-2">
                        <select class="form-select" wire:model.live="roleFilter">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <select class="form-select" wire:model.live="branchFilter">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <select class="form-select" wire:model.live="warehouseFilter">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <select class="form-select" wire:model.live="perPage">
                            <option value="10">10 per page</option>
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                        </select>
                    </div>
                </div>
                
                <!-- Additional Filters -->
                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model.live="showInactive" id="showInactive">
                                <label class="form-check-label small" for="showInactive">
                                    Show inactive users
                                </label>
                            </div>
                            @if($search || $roleFilter || $branchFilter || $warehouseFilter || $showInactive)
                                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearFilters">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Role</th>

                            <th class="text-end px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                                            <td class="px-4 py-3">
                    <span class="fw-medium small">{{ $user->name }}</span>
                </td>
                <td class="px-4 py-3"><span class="small">{{ $user->email }}</span></td>
                <td class="px-4 py-3"><span class="small">{{ $user->role }}</span></td>

                            <td class="text-end px-4 py-3">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="bi bi-person-x display-6 text-secondary mb-3"></i>
                                    <h6 class="fw-medium">No users found</h6>
                                    <p class="text-secondary small">Try adjusting your search criteria</p>
                                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" wire:click="clearFilters">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
                <div class="border-top px-4 py-3">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
    @endcan
</div>
