<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $item->name }}</h4>
            <p class="text-secondary mb-0 small">{{ $item->sku }} • {{ $item->category->name ?? 'No Category' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
            @can('update', $item)
                <a href="{{ route('admin.items.edit', $item->id) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
        </div>
    </div>

    <!-- Cards Row -->
    <div class="row g-4">
        <!-- General Information Card -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 py-3">
                    <h6 class="fw-semibold mb-0 text-secondary">
                        <i class="bi bi-info-circle me-2"></i>General Information
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">SKU:</span>
                        <span class="fw-medium">{{ $item->sku }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Category:</span>
                        <span class="fw-medium">{{ $item->category->name ?? 'No Category' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Unit:</span>
                        <span class="fw-medium">{{ $item->unit }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Status:</span>
                        <span>
                            @if($item->is_active)
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">Inactive</span>
                            @endif
                        </span>
                    </div>
                    @if($item->barcode)
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted">Barcode:</span>
                            <span class="fw-medium">{{ $item->barcode }}</span>
                        </div>
                    @endif
                    @if($item->description)
                        <div class="d-flex justify-content-between align-items-start py-2">
                            <span class="text-muted">Description:</span>
                            <span class="fw-medium text-end">{{ $item->description }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Total Stock:</span>
                        <span class="fw-medium text-primary">{{ $totalStock }} {{ $item->unit }}</span>
                    </div>
                    @if($item->unit_quantity)
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted">Total Items:</span>
                            <span class="fw-medium text-primary">{{ $totalStock * $item->unit_quantity }} {{ $item->item_unit ?? 'units' }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Reorder Level:</span>
                        <span class="fw-medium">{{ $item->reorder_level }} {{ $item->unit }}</span>
                    </div>
                    @if($isLowStock)
                        <div class="alert alert-warning py-2 small mb-0 mt-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>Low stock alert - Current stock is below reorder level
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pricing Information Card -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 py-3">
                    <h6 class="fw-semibold mb-0 text-secondary">
                        <i class="bi bi-currency-dollar me-2"></i>Pricing Information
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Cost Price (per {{ $item->unit }}):</span>
                        <span class="fw-medium text-info">ETB {{ number_format($item->cost_price, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Selling Price (per {{ $item->unit }}):</span>
                        <span class="fw-medium text-success">ETB {{ number_format($item->selling_price, 2) }}</span>
                    </div>
                    @if($item->cost_price_per_unit)
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted">Cost Price (per {{ $item->item_unit ?? 'unit' }}):</span>
                            <span class="fw-medium text-info">ETB {{ number_format($item->cost_price_per_unit, 2) }}</span>
                        </div>
                    @endif
                    @if($item->selling_price_per_unit)
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted">Selling Price (per {{ $item->item_unit ?? 'unit' }}):</span>
                            <span class="fw-medium text-success">ETB {{ number_format($item->selling_price_per_unit, 2) }}</span>
                        </div>
                    @endif
                    @if($item->unit_quantity)
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted">Units per {{ $item->unit }}:</span>
                            <span class="fw-medium">{{ $item->unit_quantity }} {{ $item->item_unit ?? 'units' }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Profit Margin:</span>
                        <span class="fw-medium text-secondary">{{ number_format($margin, 1) }}%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Profit per {{ $item->unit }}:</span>
                        <span class="fw-medium text-secondary">ETB {{ number_format($item->selling_price - $item->cost_price, 2) }}</span>
                    </div>
                    @if($item->unit_quantity)
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted">Profit per {{ $item->item_unit ?? 'unit' }}:</span>
                            <span class="fw-medium text-secondary">ETB {{ number_format(($item->selling_price - $item->cost_price) / $item->unit_quantity, 2) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Stock by Warehouse -->
    @if($stocks->count() > 0)
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header border-0 py-3">
                <h6 class="fw-medium mb-0">Stock by Warehouse</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th class="py-2 px-3 fw-semibold text-dark">Warehouse</th>
                                <th class="text-center py-2 px-3 fw-semibold text-dark">Quantity</th>
                                @if($item->unit_quantity)
                                    <th class="text-center py-2 px-3 fw-semibold text-dark">Items</th>
                                @endif
                                <th class="text-center py-2 px-3 fw-semibold text-dark">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stocks as $stock)
                                <tr>
                                    <td class="py-2 px-3">{{ $stock->warehouse->name }}</td>
                                    <td class="text-center py-2 px-3 fw-medium">{{ $stock->quantity }} {{ $item->unit }}</td>
                                    @if($item->unit_quantity)
                                        <td class="text-center py-2 px-3 text-muted small">{{ $stock->quantity * $item->unit_quantity }} {{ $item->item_unit ?? 'units' }}</td>
                                    @endif
                                    <td class="text-center py-2 px-3">
                                        @if($stock->quantity <= 0)
                                            <span class="badge bg-danger-subtle text-danger">Out of Stock</span>
                                        @elseif($stock->quantity <= $item->reorder_level)
                                            <span class="badge bg-warning-subtle text-warning">Low Stock</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success">In Stock</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
