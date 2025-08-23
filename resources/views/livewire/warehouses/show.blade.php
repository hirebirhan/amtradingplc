<div>
    <div class="container-fluid">
        <!-- Enhanced Page Title -->
        <div class="d-flex justify-content-between align-items-center my-4">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 d-flex align-items-center justify-content-center avatar-lg">
                    <i class="fas fa-warehouse fa-lg text-primary"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-1">{{ $warehouse->name ?? 'Warehouse' }}</h4>
                    <div class="text-muted small">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        {{ $warehouse->address ?? 'No address specified' }}
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.warehouses.edit', $warehouse->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Edit
                </a>
                <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-box fa-lg text-primary"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Items</div>
                            <h5 class="mb-0 fw-bold">{{ $totalItemsCount ?? $stocks->total() ?? 0 }}</h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-exclamation-triangle fa-lg text-warning"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Low Stock Items</div>
                            <h5 class="mb-0 fw-bold">{{ $lowStockCount ?? 0 }}</h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-building fa-lg text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Branches Served</div>
                            <h5 class="mb-0 fw-bold">{{ isset($warehouse->branches) ? $warehouse->branches->count() : 'All' }}</h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-calendar-alt fa-lg text-info"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Created</div>
                            <h5 class="mb-0 fw-bold">{{ $warehouse->created_at ? $warehouse->created_at->format('M d, Y') : 'N/A' }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Warehouse Information Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header py-3 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold text-primary">
                                <i class="fas fa-info-circle me-2"></i>Warehouse Information
                            </h5>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item py-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3 d-flex align-items-center justify-content-center avatar-md">
                                        <i class="fas fa-barcode text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Warehouse Code</div>
                                        <div class="fw-medium">{{ $warehouse->code ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item py-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-info bg-opacity-10 p-2 me-3 d-flex align-items-center justify-content-center avatar-md">
                                        <i class="fas fa-map-marked-alt text-info"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Address</div>
                                        <div class="fw-medium">{{ $warehouse->address ?? 'Not specified' }}</div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Manager Information Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header py-3 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold text-primary">
                                <i class="fas fa-user-tie me-2"></i>Manager Details
                            </h5>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item py-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3 d-flex align-items-center justify-content-center avatar-md">
                                        <i class="fas fa-user text-success"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Manager Name</div>
                                        <div class="fw-medium">{{ $warehouse->manager_name ?? 'Not assigned' }}</div>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item py-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-warning bg-opacity-10 p-2 me-3 d-flex align-items-center justify-content-center avatar-md">
                                        <i class="fas fa-phone-alt text-warning"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Contact Phone</div>
                                        <div class="fw-medium">{{ $warehouse->phone ?? 'Not specified' }}</div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Branches Served Card -->
            <div class="col-lg-4 col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header py-3 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold text-primary">
                                <i class="fas fa-building me-2"></i>Branches Served
                            </h5>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(isset($warehouse->branches) && $warehouse->branches->count() > 0)
                            <div class="row g-2">
                                @foreach($warehouse->branches as $branch)
                                    <div class="col-6">
                                        <div class="border rounded p-2 d-flex align-items-center">
                                            <div class="rounded-circle bg-info bg-opacity-10 p-2 me-2 d-flex align-items-center justify-content-center avatar-sm">
                                                <i class="fas fa-building text-info small"></i>
                                            </div>
                                            <div class="small">{{ $branch->name ?? 'Unnamed' }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <div class="mb-2">
                                    <i class="fas fa-building text-muted fa-2x"></i>
                                </div>
                                <p class="text-muted mb-0">This warehouse serves all branches</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Low Stock Items Card -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header py-3 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold text-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Items
                            </h5>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($lowStockItems && $lowStockItems->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Current Stock</th>
                                            <th>Reorder Level</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($lowStockItems as $stock)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px">
                                                            <i class="fas fa-box text-primary small"></i>
                                                        </div>
                                                        <div>{{ $stock->item->name ?? 'Unknown Item' }}</div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger text-white">{{ number_format($stock->quantity, 2) }}</span>
                                                </td>
                                                <td>{{ $stock->item->reorder_level ?? 'N/A' }}</td>
                                                <td>
                                                    @if($stock->item && $stock->quantity < $stock->item->reorder_level)
                                                        <span class="badge bg-danger">Critical</span>
                                                    @else
                                                        <span class="badge bg-warning">Low</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-success">
                                                        <i class="fas fa-plus me-1"></i> Restock
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <div class="mb-2">
                                    <i class="fas fa-check-circle text-success fa-3x"></i>
                                </div>
                                <h6 class="fw-bold">All Items Sufficiently Stocked</h6>
                                <p class="text-muted mb-0">No low stock items detected in this warehouse</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Warehouse Stock Items -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header py-3 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-0 fw-bold text-primary">
                                    <i class="fas fa-boxes me-2"></i>Stock Items
                                </h5>
                                <p class="text-muted mb-0 small">
                                    Showing <span class="fw-medium">{{ $stocks->count() ?? 0 }}</span> of
                                    <span class="fw-medium">{{ $stocks->total() ?? 0 }}</span> items
                                </p>
                            </div>
                        
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($stocks->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-3 px-4">Item</th>
                                            <th class="py-3">Category</th>
                                            <th class="py-3">SKU</th>
                                            <th class="py-3 text-end">Quantity</th>
                                            <th class="py-3 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stocks as $stock)
                                            <tr class="align-middle">
                                                <td class="py-3 px-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px">
                                                            <i class="fas fa-box text-primary"></i>
                                                        </div>
                                                        <div>
                                                            @if($stock->item)
                                                                <h6 class="mb-0">{{ $stock->item->name }}</h6>
                                                                <span class="text-muted small">ID: {{ $stock->item->id }}</span>
                                                            @else
                                                                <h6 class="mb-0 text-muted">Missing Item</h6>
                                                                <span class="text-danger small">Item data unavailable</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    @if($stock->item && $stock->item->category)
                                                        <span class="badge bg-info bg-opacity-10 text-info px-2 py-1">
                                                            <i class="fas fa-tag me-1"></i>
                                                            {{ $stock->item->category->name }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1">
                                                            <i class="fas fa-question-circle me-1"></i>
                                                            Uncategorized
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge bg-light text-dark px-2 py-1">
                                                        {{ $stock->item->sku ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td class="py-3 text-end">
                                                    <span class="badge rounded-pill {{ $stock->quantity < 5 ? 'bg-danger' : 'bg-success' }} px-3 py-2 fs-6">
                                                        {{ number_format($stock->quantity, 2) }}
                                                    </span>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <div class="btn-group">
                                                        <a href="#" class="btn btn-sm btn-outline-primary" title="View History">
                                                            <i class="fas fa-history"></i>
                                                        </a>
                                                        <a href="#" class="btn btn-sm btn-outline-success" title="Add Stock">
                                                            <i class="fas fa-plus"></i>
                                                        </a>
                                                        <a href="#" class="btn btn-sm btn-outline-warning" title="Remove Stock">
                                                            <i class="fas fa-minus"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center bg-light p-3">
                                <div class="text-muted small">
                                    Showing {{ $stocks->firstItem() ?? 0 }} to {{ $stocks->lastItem() ?? 0 }} of {{ $stocks->total() ?? 0 }} items
                                </div>
                                <div>
                                    {{ $stocks->links() }}
                                </div>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="rounded-circle bg-light p-4 d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 80px; height: 80px">
                                    <i class="fas fa-box-open fa-2x text-muted"></i>
                                </div>
                                <h5>No Stock Items Found</h5>
                                <p class="text-muted mb-4">This warehouse doesn't have any items in stock yet.</p>
                                <a href="#" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Add First Stock Item
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>