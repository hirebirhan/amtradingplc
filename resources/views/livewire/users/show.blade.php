<div>
    <!-- Simple Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
        <div class="mb-2 mb-md-0">
            <h2 class="fw-bold mb-1">{{ $user->name }}</h2>
            <p class="text-muted mb-0">{{ $user->email }}</p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <!-- Single Card with All Information -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">User Details</h5>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="mb-2">
                        <div class="text-muted small">Name</div>
                        <div class="fw-medium">{{ $user->name }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2">
                        <div class="text-muted small">Email</div>
                        <div class="fw-medium">{{ $user->email }}</div>
                    </div>
                </div>
                @if($user->phone)
                <div class="col-md-6">
                    <div class="mb-2">
                        <div class="text-muted small">Phone</div>
                        <div class="fw-medium">{{ $user->phone }}</div>
                    </div>
                </div>
                @endif
                <div class="col-md-6">
                    <div class="mb-2">
                        <div class="text-muted small">Status</div>
                        <div>
                            @if($user->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2">
                        <div class="text-muted small">Created</div>
                        <div class="fw-medium">{{ $user->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2">
                        <div class="text-muted small">Roles</div>
                        <div>
                            @if($user->roles->count() > 0)
                                @foreach($user->roles as $role)
                                    <span class="badge bg-primary me-1">{{ $role->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No roles assigned</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2">
                        <div class="text-muted small">Branch</div>
                        <div class="fw-medium">
                            @if($user->branch)
                                {{ $user->branch->name }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2">
                        <div class="text-muted small">Warehouse</div>
                        <div class="fw-medium">
                            @if($user->warehouse)
                                {{ $user->warehouse->name }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                </div>
                @if($user->employee)
                <div class="col-md-6">
                    <div class="mb-2">
                        <div class="text-muted small">Employee Profile</div>
                        <div>
                            <a href="{{ route('admin.employees.show', $user->employee) }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-person me-1"></i>View Profile
                            </a>
                        </div>
                    </div>
                </div>
                @endif
                @if(auth()->user()->hasRole(['SuperAdmin', 'GeneralManager']))
                <div class="col-12">
                    <div class="alert alert-info mb-0 mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>System Access:</strong> You have access to all branches and warehouses
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
