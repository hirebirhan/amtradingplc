{{-- Clean Warehouses Management Page --}}
<div>
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Warehouses</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Manage warehouse locations, track inventory levels, and monitor stock movements
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @can('warehouses.create')
            <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">New Warehouse</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm">
        <!-- Enhanced Filters Section -->
        <div class="card-header border-bottom">
            <div class="row g-3">
                <!-- Search -->
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="Search warehouses..." wire:model.live.debounce.300ms="search">
                        @if($search)
                            <button class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 text-muted" type="button" wire:click="$set('search', '')">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>
                </div>
                
                <!-- Branch Filter -->
                <div class="col-6 col-md-3 col-lg-4">
                    <select class="form-select" wire:model.live="branchFilter">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Desktop Table View -->
            <div class="d-none d-lg-block">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-3">Warehouse</th>
                                <th>Location</th>

                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($warehouses as $warehouse)
                            <tr>
                                                <td class="px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle avatar-sm">
                            <i class="bi bi-warehouse"></i>
                        </div>
                        <div>
                            <div class="fw-medium small">{{ $warehouse->name }}</div>
                            <div class="text-secondary small">{{ $warehouse->code ?? '' }}</div>
                        </div>
                    </div>
                </td>
                <td><span class="small">{{ $warehouse->address ?? '-' }}</span></td>

                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.warehouses.show', $warehouse) }}" class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.warehouses.edit', $warehouse) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-house-x display-6 text-secondary mb-3"></i>
                                        <h6 class="fw-medium">No warehouses found</h6>
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
            </div>

            <!-- Mobile Card View -->
            <div class="d-lg-none">
                @forelse($warehouses as $warehouse)
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle avatar-sm">
                                    <i class="bi bi-warehouse"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">{{ $warehouse->name }}</div>
                                    @if($warehouse->code)
                                        <div class="text-secondary small">{{ $warehouse->code }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge bg-primary-subtle text-primary-emphasis">{{ $warehouse->stocks_count ?? 0 }} items</span>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            @if($warehouse->branch)
                                <div class="text-secondary small">
                                    <i class="bi bi-building me-1"></i>{{ $warehouse->branch->name }}
                                </div>
                            @endif
                            <div class="text-secondary small">
                                <i class="bi bi-box-seam me-1"></i>{{ $warehouse->stocks_count ?? 0 }} items
                                @if($warehouse->stocks_count > 0)
                                    ({{ number_format($warehouse->stocks->sum('quantity') ?? 0) }} units)
                                @endif
                            </div>
                        </div>
                        
                        <div class="d-flex gap-1">
                            @can('warehouses.view')
                            <a href="{{ route('admin.warehouses.show', $warehouse->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                            @endcan
                            @can('warehouses.edit')
                            <a href="{{ route('admin.warehouses.edit', $warehouse->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            @endcan
                            @can('warehouses.delete')
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                wire:click="deleteWarehouse({{ $warehouse->id }})"
                                wire:confirm="Are you sure you want to delete {{ $warehouse->name }}?">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-warehouse text-secondary" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-secondary">No warehouses found</h5>
                        @if($search || $branchFilter)
                            <p class="text-secondary">Try adjusting your search criteria</p>
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="$set('search', ''); $set('branchFilter', '');">
                                Clear Filters
                            </button>
                        @else
                            <p class="text-secondary">Start by adding your first warehouse</p>
                            @can('warehouses.create')
                            <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i> Add First Warehouse
                            </a>
                            @else
                            <p class="small text-secondary">Contact your administrator for access</p>
                            @endcan
                        @endif
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Pagination -->
        @if($warehouses->hasPages())
            <div class="d-flex justify-content-center py-3 border-top">
                {{ $warehouses->links() }}
            </div>
        @endif
    </div>
</div>