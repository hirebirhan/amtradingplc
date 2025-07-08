<x-app-layout>
    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="row align-items-center mb-4">
            <div class="col">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-tags text-primary fs-4"></i>
                    </div>
                    <div>
                        <h1 class="h3 fw-bold mb-1">{{ $category->name }}</h1>
                        <p class="text-body-secondary mb-0">Category details and associated items</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-2"></i>Edit Category
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Categories
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center py-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 w-75 h-75">
                            <i class="bi bi-box text-primary fs-4"></i>
                        </div>
                        <h6 class="fw-semibold mb-0">Total Items</h6>
                        <div class="h2 fw-bold mb-0">{{ $category->items_count }}</div>
                        <small class="text-body-secondary">Items in this category</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center py-4">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 w-75 h-75">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                        <h6 class="fw-semibold mb-0">In Stock</h6>
                        <div class="h2 fw-bold mb-0">{{ $inStockCount }}</div>
                        <small class="text-body-secondary">Available items</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center py-4">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 w-75 h-75">
                            <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                        </div>
                        <h6 class="fw-semibold mb-0">Low Stock</h6>
                        <div class="h2 fw-bold mb-0">{{ $lowStockCount }}</div>
                        <small class="text-body-secondary">Below alert level</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center py-4">
                        <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 w-75 h-75">
                            <i class="bi bi-currency-dollar text-info fs-4"></i>
                        </div>
                        <h6 class="fw-semibold mb-0">Total Value</h6>
                        <div class="h2 fw-bold mb-0">ETB {{ number_format($totalValue, 2) }}</div>
                        <small class="text-body-secondary">Inventory value</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Details -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-0 py-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-info bg-opacity-10 rounded-2 p-2">
                        <i class="bi bi-info-circle text-info"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Category Information</h5>
                        <small class="text-body-secondary">Basic category details</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Category Name</label>
                            <div class="form-control-plaintext">{{ $category->name }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <div class="form-control-plaintext">{{ $category->description ?? 'No description provided' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Created Date</label>
                            <div class="form-control-plaintext">{{ $category->created_at->format('M d, Y H:i') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Last Updated</label>
                            <div class="form-control-plaintext">{{ $category->updated_at->format('M d, Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 rounded-2 p-2">
                            <i class="bi bi-list-ul text-primary"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Category Items</h5>
                            <small class="text-body-secondary">All items in this category</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('admin.items.create', ['category' => $category->id]) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg me-2"></i>Add Item
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if($category->items->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th class="py-3 px-4 fw-semibold border-0">Item</th>
                                    <th class="py-3 px-4 fw-semibold border-0">SKU</th>
                                    <th class="py-3 px-4 fw-semibold border-0">Current Stock</th>
                                    <th class="py-3 px-4 fw-semibold border-0">Unit Price</th>
                                    <th class="py-3 px-4 fw-semibold border-0">Status</th>
                                    <th class="py-3 px-4 fw-semibold border-0 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($category->items as $item)
                                    <tr>
                                        <td class="py-3 px-4">
                                            <div class="fw-semibold">{{ $item->name }}</div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="text-body-secondary">{{ $item->sku ?? 'No SKU' }}</span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="h6 fw-bold">{{ $item->current_stock }}</span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="fw-semibold">ETB {{ number_format($item->unit_price, 2) }}</span>
                                        </td>
                                        <td class="py-3 px-4">
                                            @if($item->current_stock == 0)
                                                <span class="badge bg-danger bg-opacity-15 text-danger px-3 py-2">
                                                    Out of Stock
                                                </span>
                                            @elseif($item->current_stock <= $item->alert_quantity)
                                                <span class="badge bg-warning bg-opacity-15 text-warning px-3 py-2">
                                                    Low Stock
                                                </span>
                                            @else
                                                <span class="badge bg-success bg-opacity-15 text-success px-3 py-2">
                                                    In Stock
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-end">
                                            <div class="btn-group">
                                                <a href="{{ route('admin.items.show', $item) }}" class="btn btn-sm btn-outline-info" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.items.edit', $item) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 w-75 h-75">
                            <i class="bi bi-box text-secondary fs-3"></i>
                        </div>
                        <h6 class="fw-bold mb-2">No Items Found</h6>
                        <p class="text-body-secondary mb-3">This category doesn't have any items yet</p>
                        <a href="{{ route('admin.items.create', ['category' => $category->id]) }}" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>Add First Item
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
