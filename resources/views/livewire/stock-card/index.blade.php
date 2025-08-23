{{-- Clean Stock Card Management Page --}}
<div>
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Stock Card</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Track all stock movements and balances for inventory items
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @can('stock-card.create')
            <button class="btn btn-outline-secondary btn-sm" wire:click="toggleAddForm">
                {{ $showAddForm ? 'Cancel' : 'Add Entry' }}
            </button>
            @endcan
            @if($selectedItem)
            <button class="btn btn-outline-info btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>
                <span class="d-none d-sm-inline">Print</span>
            </button>
            <button class="btn btn-outline-secondary btn-sm" wire:click="exportStockCard">
                <i class="bi bi-download me-1"></i>
                <span class="d-none d-sm-inline">Export</span>
            </button>
            @endif
            <button class="btn btn-outline-primary btn-sm" wire:click="clearFilters">
                <i class="bi bi-arrow-clockwise me-1"></i>
                <span class="d-none d-sm-inline">Clear</span>
            </button>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Manual Entry Form -->
    @if($showAddForm)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <h6 class="mb-0 fw-semibold">Add Stock Movement</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small">Item <span class="text-danger">*</span></label>
                    <select class="form-select @error('newEntry.item_id') is-invalid @enderror" wire:model="newEntry.item_id">
                        <option value="">Select Item</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    @error('newEntry.item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label small">Warehouse <span class="text-danger">*</span></label>
                    <select class="form-select @error('newEntry.warehouse_id') is-invalid @enderror" wire:model="newEntry.warehouse_id">
                        <option value="">Select Warehouse</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    @error('newEntry.warehouse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label small">Movement Type <span class="text-danger">*</span></label>
                    <select class="form-select @error('newEntry.movement_type') is-invalid @enderror" wire:model="newEntry.movement_type">
                        <option value="in">Stock In</option>
                        <option value="out">Stock Out</option>
                    </select>
                    @error('newEntry.movement_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label small">Reference Number <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control @error('newEntry.reference_number') is-invalid @enderror" 
                               wire:model="newEntry.reference_number" placeholder="PO-2024-0001 or SO-2024-0001">
                        <button class="btn btn-outline-secondary" type="button" wire:click="generateReferenceNumber">
                            Generate
                        </button>
                    </div>
                    @error('newEntry.reference_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label small">Quantity <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="form-control @error('newEntry.quantity') is-invalid @enderror" 
                           wire:model="newEntry.quantity" placeholder="0.00">
                    @error('newEntry.quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label small">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('newEntry.date') is-invalid @enderror" 
                           wire:model="newEntry.date">
                    @error('newEntry.date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label small">Description</label>
                    <textarea class="form-control @error('newEntry.description') is-invalid @enderror" 
                              wire:model="newEntry.description" rows="2" placeholder="Optional description"></textarea>
                    @error('newEntry.description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" wire:click="saveEntry">
                            Save Entry
                        </button>
                        <button type="button" class="btn btn-secondary" wire:click="resetNewEntry">
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Main Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Filters -->
            <div class="p-4 border-bottom">
                <!-- Row 1: Item and Type -->
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label small">Item <span class="text-danger">*</span></label>
                        <select class="form-select" wire:model.live="itemFilter">
                            <option value="">Select an item to view stock card</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small">Type</label>
                        <select class="form-select" wire:model.live="typeFilter">
                            <option value="">All Types</option>
                            <option value="purchase">Purchase</option>
                            <option value="sale">Sale</option>
                            <option value="transfer">Transfer</option>
                            <option value="manual">Manual</option>
                            <option value="initial">Initial Stock</option>
                        </select>
                    </div>
                </div>
                
                <!-- Row 2: Date Range -->
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Date From</label>
                        <input type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Date To</label>
                        <input type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                
                </div>

                @if($itemFilter || $typeFilter || $dateFrom || $dateTo)
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearFilters">
                        <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm ms-2" wire:click="testFiltering">
                        <i class="bi bi-bug me-1"></i>Test Filter
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm ms-2" wire:click="verifyItemFilter">
                        <i class="bi bi-check-circle me-1"></i>Verify Item Filter
                    </button>
                </div>
                @endif
                
                <!-- Debug Info (remove in production) -->
                @if($itemFilter)
                <div class="mt-2">
                    <small class="text-muted">
                        Debug: Filtering by Item ID: {{ $itemFilter }} | 
                        Selected Item: {{ $selectedItem ? $selectedItem->name : 'Unknown' }} | 
                        Type: {{ $typeFilter ?: 'All' }} | 
                        Date Range: {{ $dateFrom ?: 'Any' }} - {{ $dateTo ?: 'Any' }}
                    </small>
                </div>
                @endif
            </div>

            @if($selectedItem)
                <!-- Stock Card Content -->
                <div class="p-4">
                    <!-- Item Information -->
                    <div class="mb-4">
                        <div class="mb-2">
                            <span class="fw-medium">- Article:</span> {{ $selectedItem->name }}
                        </div>
                        <div class="mb-2">
                            <span class="fw-medium">- Cost Price:</span> {{ number_format($selectedItem->cost_price, 2) }} ETB
                        </div>
                        <div class="mb-2">
                            <span class="fw-medium">- Selling Price:</span> {{ number_format($selectedItem->selling_price, 2) }} ETB
                        </div>
                    </div>

                    @if($stockMovements->count() > 0)
                        <!-- Traditional Stock Card Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-3 py-3 fw-semibold text-dark">Date</th>
                                        <th class="px-3 py-3 fw-semibold text-dark">Item</th>
                                        <th class="px-3 py-3 fw-semibold text-dark">Amount</th>
                                        <th class="px-3 py-3 fw-semibold text-dark">Type</th>
                                        <th class="px-3 py-3 fw-semibold text-dark">Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($stockMovements as $movement)
                                        <tr>
                                            <td class="px-3 py-3">
                                                <span class="small">{{ $movement->created_at->format('M d, Y') }}</span>
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="d-flex flex-column">
                                                    <span class="fw-medium">{{ $movement->item->name }}</span>
                                                    <span class="text-secondary small">{{ $movement->item->sku }}</span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-3">
                                                @if($movement->reference_type === 'purchase' && $movement->reference && isset($movement->reference->total_amount))
                                                    <span class="fw-medium text-primary">{{ number_format($movement->reference->total_amount, 2) }} ETB</span>
                                                @elseif($movement->reference_type === 'sale' && $movement->reference && isset($movement->reference->total_amount))
                                                    <span class="fw-medium text-success">{{ number_format($movement->reference->total_amount, 2) }} ETB</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3">
                                                @switch($movement->reference_type)
                                                    @case('purchase')
                                                        <span class="badge bg-primary-subtle text-primary-emphasis">Purchase</span>
                                                        @break
                                                    @case('sale')
                                                        <span class="badge bg-success-subtle text-success-emphasis">Sale</span>
                                                        @break
                                                    @case('transfer')
                                                        <span class="badge bg-warning-subtle text-warning-emphasis">Transfer</span>
                                                        @break
                                                    @case('manual')
                                                        <span class="badge bg-secondary-subtle text-secondary-emphasis">Manual</span>
                                                        @break
                                                    @case('initial')
                                                        <span class="badge bg-info-subtle text-info-emphasis">Initial</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ ucfirst($movement->reference_type) }}</span>
                                                @endswitch
                                            </td>
                                            <td class="px-3 py-3">
                                                @if($movement->reference_type === 'purchase' && $movement->reference)
                                                    <a href="{{ route('admin.purchases.show', $movement->reference_id) }}" class="text-decoration-none hover-lift">
                                                        <span class="fw-medium text-primary">{{ $movement->reference->reference_no ?? 'Purchase' }}</span>
                                                    </a>
                                                @elseif($movement->reference_type === 'sale' && $movement->reference)
                                                    <a href="{{ route('admin.sales.show', $movement->reference_id) }}" class="text-decoration-none hover-lift">
                                                        <span class="fw-medium text-success">{{ $movement->reference->reference_no ?? 'Sale' }}</span>
                                                    </a>
                                                @elseif($movement->reference_type === 'transfer' && $movement->reference)
                                                    <a href="{{ route('admin.transfers.show', $movement->reference_id) }}" class="text-decoration-none hover-lift">
                                                        <span class="fw-medium text-warning">{{ $movement->reference->reference_code ?? 'Transfer' }}</span>
                                                    </a>
                                                @elseif($movement->reference_type === 'manual')
                                                    <span class="text-muted">{{ $movement->description ?? 'Manual Entry' }}</span>
                                                @else
                                                    <span class="text-muted">{{ ucfirst($movement->reference_type) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <div class="text-muted">No stock movements found</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted small">
                                Showing {{ $stockMovements->firstItem() ?? 0 }} to {{ $stockMovements->lastItem() ?? 0 }} of {{ $stockMovements->total() }} entries
                            </div>
                            <div>
                                {{ $stockMovements->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="text-muted mb-3" style="font-size: 3rem;">📋</div>
                            <h5 class="text-muted">No stock movements found</h5>
                            <p class="text-muted">No movements found for this item in the selected date range</p>
                            @can('stock-card.create')
                            <button class="btn btn-primary" wire:click="toggleAddForm">
                                Add First Entry
                            </button>
                            @endcan
                        </div>
                    @endif
                </div>
            @else
                <!-- No Item Selected -->
                <div class="p-4 text-center py-5">
                    <div class="text-muted mb-3" style="font-size: 4rem;">📦</div>
                    <h5 class="text-muted">Select an Item</h5>
                    <p class="text-muted">Choose an item from the dropdown above to view its stock card</p>
                </div>
            @endif
        </div>
    </div>
</div> 