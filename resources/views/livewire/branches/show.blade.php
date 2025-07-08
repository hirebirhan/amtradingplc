{{-- Modern Branch Details Page --}}
<div>
    <!-- Simple Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h2 class="fw-bold mb-1">{{ $branch->name }}</h2>
            <p class="text-muted mb-0">Branch Details</p>
        </div>
        
        <div class="d-flex gap-2">
            @can('branches.edit')
            <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            @endcan
            <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Branch Information -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Branch Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small">Name</div>
                        <div class="fw-medium">{{ $branch->name }}</div>
                    </div>

                    @if($branch->code)
                    <div class="mb-3">
                        <div class="text-muted small">Code</div>
                        <div class="fw-medium">{{ $branch->code }}</div>
                    </div>
                    @endif

                    @if($branch->address)
                    <div class="mb-3">
                        <div class="text-muted small">Address</div>
                        <div class="fw-medium">{{ $branch->address }}</div>
                    </div>
                    @endif

                    @if($branch->phone)
                    <div class="mb-3">
                        <div class="text-muted small">Phone</div>
                        <div class="fw-medium">
                            <a href="tel:{{ $branch->phone }}" class="text-decoration-none">{{ $branch->phone }}</a>
                        </div>
                    </div>
                    @endif

                    @if($branch->email)
                    <div class="mb-3">
                        <div class="text-muted small">Email</div>
                        <div class="fw-medium">
                            <a href="mailto:{{ $branch->email }}" class="text-decoration-none">{{ $branch->email }}</a>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <div class="text-muted small">Created</div>
                        <div class="fw-medium">{{ $branch->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warehouses -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Warehouses ({{ count($warehouses) }})</h5>
                    @can('warehouses.create')
                    <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus me-1"></i>Add Warehouse
                    </a>
                    @endcan
                </div>

                <div class="card-body p-0">
                    @if(count($warehouses) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Address</th>
                                        <th>Manager</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($warehouses as $warehouse)
                                        <tr>
                                            <td class="fw-medium">{{ $warehouse->name }}</td>
                                            <td class="text-muted">{{ $warehouse->address ?: '—' }}</td>
                                            <td class="text-muted">{{ $warehouse->manager_name ?: '—' }}</td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    @can('warehouses.view')
                                                    <a href="{{ route('admin.warehouses.show', $warehouse) }}" class="btn btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    @endcan
                                                    @can('warehouses.edit')
                                                    <a href="{{ route('admin.warehouses.edit', $warehouse) }}" class="btn btn-outline-secondary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted mb-3">No warehouses found.</p>
                            @can('warehouses.create')
                            <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus me-1"></i>Add Warehouse
                            </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>