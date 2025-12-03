{{-- Clean Items Management Page --}}
<div>
    @if($stockFilter === 'low')
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <div>
                <strong>Low Stock Items Report</strong>
                <p class="mb-0">Showing items that are below their reorder level. Take action to replenish inventory.</p>
            </div>
        </div>
        <button type="button" class="btn-close" wire:click="$set('stockFilter', '')" aria-label="Close"></button>
    </div>
    @endif

    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4 p-3 border rounded">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Inventory Items</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Manage your product inventory, stock levels, and pricing
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @can('items.create')
            <div class="d-flex gap-2">
                <a href="{{ route('admin.items.import') }}" class="btn btn-outline-primary">
                    <i class="bi bi-upload me-2"></i>Import
                </a>
                <a href="{{ route('admin.items.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>New Item
                </a>
            </div>
            @endcan
        </div>
    </div>

    @can('items.view')
    <!-- Main Card -->
    <div class="card border shadow-sm">
        <div class="card-body p-0">
        <!-- Stock Statistics & Filters -->
            <div class="p-4 border-bottom">
            <!-- Stock Statistics -->
            @php $stats = $this->getStockStatistics($items); @endphp
            <div class="mb-3">
                <div class="d-flex flex-wrap gap-4 small">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-currency-dollar text-primary me-1"></i>
                        <span><strong>Stock Value:</strong> {{ $stats['stock_value'] }} ETB</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-box-seam text-info me-1"></i>
                        <span><strong>Items:</strong> {{ $stats['total_items'] }}</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle text-success me-1"></i>
                        <span><strong>In Stock:</strong> {{ $stats['in_stock'] }}</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                        <span><strong>Low:</strong> {{ $stats['low_stock'] }}</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-x-circle text-danger me-1"></i>
                        <span><strong>Out:</strong> {{ $stats['out_of_stock'] }}</span>
                    </div>
                </div>
            </div>
            <!-- Row 1: Search and Category -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Search by name, SKU, barcode...">
                        @if($search)
                            <button class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2" type="button" wire:click="$set('search', '')" style="background: none; border: none;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select wire:model.live="categoryFilter" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select wire:model.live="stockFilter" class="form-select">
                        <option value="">All Purchase Statuses</option>
                        <option value="in">Has Purchases</option>
                        <option value="low">Low Purchase Qty</option>
                        <option value="out">No Purchases</option>
                    </select>
                </div>
            </div>
            
            <!-- Row 2: Branch, Warehouse, and Toggle -->
            <div class="row g-3">
                @if(auth()->user()->canAccessLocationFilters())
                    <div class="col-6 col-md-4">
                        <select wire:model.live="branchFilter" class="form-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <select wire:model.live="warehouseFilter" class="form-select">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center h-100">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model.live="hideZeroStock" id="hideZeroStock">
                                <label class="form-check-label" for="hideZeroStock" title="Hide items with zero purchases">
                                    Hide items with zero purchases
                                </label>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="col-12">
                        <div class="d-flex align-items-center h-100">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model.live="hideZeroStock" id="hideZeroStock">
                                <label class="form-check-label" for="hideZeroStock" title="Hide items with zero purchases">
                                    Hide items with zero purchases
                                </label>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

                @if($search || $categoryFilter || $stockFilter || $hideZeroStock || (auth()->user()->canAccessLocationFilters() && ($branchFilter || $warehouseFilter)))
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearFilters">
                        <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                    </button>
                </div>
                @endif
        </div>

            <!-- Table Section -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 cursor-pointer fw-semibold text-dark" wire:click="sortBy('name')">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Item</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-3 py-3 text-center fw-semibold text-dark">Unit</th>
                            <th class="px-3 py-3 text-center fw-semibold text-dark">Unit Qty</th>
                            <th class="px-3 py-3 text-start cursor-pointer fw-semibold text-dark" wire:click="sortBy('cost_price')">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Cost (ETB)</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-3 py-3 text-start cursor-pointer fw-semibold text-dark" wire:click="sortBy('selling_price')">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Total Purchase (ETB)</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-3 py-3 text-start fw-semibold text-dark">
                                <span>Sales Amount (ETB)</span>
                            </th>
                            <th class="px-3 py-3 text-center cursor-pointer fw-semibold text-dark" wire:click="sortByStock">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span>Pcs Available</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-3 py-3 text-center fw-semibold text-dark">Status</th>
                            <th class="px-4 py-3 text-end fw-semibold text-dark pe-5">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr wire:key="item-{{ $item->id }}">
                                <td class="px-4 py-3">
                                    <span class="fw-medium">
                                        {{ $item->name }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    {{ $item->unit ?? 'pcs' }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    {{ $item->unit_quantity ?? 1 }}
                                </td>
                                <td class="px-3 py-3 text-start">{{ number_format($item->cost_price, 2) }}</td>
                                <td class="px-3 py-3 text-start">{{ number_format($item->total_purchase_amount, 2) }}</td>
                                <td class="px-3 py-3 text-start">{{ number_format($item->total_sales_amount, 2) }}</td>
                                <td class="px-3 py-3 text-center">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="fw-medium">{{ number_format($this->getItemPiecesAvailable($item)) }}</span>
                                            <span class="text-secondary">pcs</span>
                                        </div>
                                        @if($item->unit_quantity > 1)
                                        <div class="d-flex align-items-center gap-1 mt-1">
                                            <small class="text-muted">{{ number_format($this->getItemUnitsAvailable($item), 2) }} units</small>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @php $status = $this->getStockStatusText($item) @endphp
                                    <span class="{{ $status['class'] }}">{{ $status['text'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-end pe-5">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.items.show', $item) }}" class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.items.edit', $item) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @can('items.delete')
                                        <button type="button" class="btn btn-outline-danger" title="Delete" wire:click="confirmDelete({{ $item->id }})" wire:key="delete-{{ $item->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-box-seam display-6 text-secondary mb-3"></i>
                                        <h6 class="fw-medium">No items found</h6>
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
            <div class="border-top px-4 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <div class="text-muted small">
                        Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} results
                    </div>
                    <div class="d-flex flex-column flex-md-row align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label mb-0 small text-muted">Show:</label>
                            <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                                <option value="250" {{ $perPage == 250 ? 'selected' : '' }}>250</option>
                                <option value="500" {{ $perPage == 500 ? 'selected' : '' }}>500</option>
                            </select>
                        </div>
                        @if($items->hasPages())
                            @php
                                $current = $items->currentPage();
                                $last = $items->lastPage();
                                $start = max(1, $current - 5);
                                $end = min($last, $current + 4);
                                if ($current <= 6) { $start = 1; $end = min($last, 10); }
                                if ($current > $last - 5) { $start = max(1, $last - 9); $end = $last; }
                            @endphp
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    @if ($items->onFirstPage())
                                        <li class="page-item disabled"><span class="page-link">‹</span></li>
                                    @else
                                        <li class="page-item"><a class="page-link" href="{{ $items->previousPageUrl() }}">‹</a></li>
                                    @endif
                                    
                                    @if ($start > 1)
                                        <li class="page-item"><a class="page-link" href="{{ $items->url(1) }}">1</a></li>
                                        @if ($start > 2)<li class="page-item disabled"><span class="page-link">...</span></li>@endif
                                    @endif
                                    
                                    @for ($page = $start; $page <= $end; $page++)
                                        @if ($page == $current)
                                            <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                                        @else
                                            <li class="page-item"><a class="page-link" href="{{ $items->url($page) }}">{{ $page }}</a></li>
                                        @endif
                                    @endfor
                                    
                                    @if ($end < $last)
                                        @if ($end < $last - 1)<li class="page-item disabled"><span class="page-link">...</span></li>@endif
                                        <li class="page-item"><a class="page-link" href="{{ $items->url($last) }}">{{ $last }}</a></li>
                                    @endif
                                    
                                    @if ($items->hasMorePages())
                                        <li class="page-item"><a class="page-link" href="{{ $items->nextPageUrl() }}">›</a></li>
                                    @else
                                        <li class="page-item disabled"><span class="page-link">›</span></li>
                                    @endif
                                </ul>
                            </nav>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
    
    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal && $itemToDelete)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:key="delete-modal-{{ $itemToDelete->id }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Delete Item</h5>
                    <button type="button" class="btn-close" wire:click="closeDeleteModal"></button>
                </div>
                <div class="modal-body">
                    @if(!empty($deleteErrors))
                        <p class="mb-3">This item cannot be deleted:</p>
                    @else
                        <p class="mb-3">Are you sure you want to delete this item?</p>
                    @endif
                    
                    <div class="mb-3">
                        <div class="fw-medium">{{ $itemToDelete->name }}</div>
                        <small class="text-muted">SKU: {{ $itemToDelete->sku ?? 'N/A' }}</small>
                    </div>
                    
                    @if(!empty($deleteErrors))
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($deleteErrors as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <small>This action cannot be undone.</small>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeDeleteModal">
                        Cancel
                    </button>
                    @if(empty($deleteErrors))
                    <button type="button" class="btn btn-danger" wire:click="deleteItem">
                        Delete Item
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function changePerPage(value) {
    const url = new URL(window.location);
    url.searchParams.set('perPage', value);
    url.searchParams.delete('page'); // Reset to page 1
    window.location.href = url.toString();
}
</script>
