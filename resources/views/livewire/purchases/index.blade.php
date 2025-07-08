{{-- Clean Purchases Management Page --}}
<div class="py-4">
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Purchase Management</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Manage purchase orders and supplier transactions
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                @can('purchases.create')
                <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>
                    <span class="d-none d-sm-inline">New Purchase</span>
                </a>
                @endcan
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Filters -->
            <div class="p-4 border-bottom">
            <!-- Row 1: Search and Status -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" placeholder="Search purchases..." wire:model.live.debounce.300ms="search">
                        @if($search)
                            <button class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2" type="button" wire:click="$set('search', '')" style="background: none; border: none;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">All Status</option>
                        <option value="paid">Paid</option>
                        <option value="partial">Partially Paid</option>
                        <option value="pending">Pending</option>
                        <option value="due">Due</option>
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
            
            <!-- Row 2: Date Range -->
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <input type="date" class="form-control" wire:model.live="dateFilter" placeholder="Filter by date">
                </div>
            </div>

                @if($search || $statusFilter || $dateFilter || $branchFilter || $warehouseFilter)
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
                            <th class="px-4 py-3 cursor-pointer fw-semibold text-dark" wire:click="sortBy('supplier_id')">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Supplier</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-3 py-3 cursor-pointer fw-semibold text-dark" wire:click="sortBy('warehouse_id')">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Warehouse</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-3 py-3 cursor-pointer fw-semibold text-dark" wire:click="sortBy('purchase_date')">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Date</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-3 py-3 text-start cursor-pointer fw-semibold text-dark" wire:click="sortBy('total_amount')">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Total (ETB)</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-3 py-3 text-center cursor-pointer fw-semibold text-dark" wire:click="sortBy('payment_status')">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span>Status</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-4 py-3 text-end fw-semibold text-dark">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="fw-medium">{{ $purchase->supplier->name ?? 'N/A' }}</span>
                                </td>
                                <td class="px-3 py-3">{{ $purchase->warehouse->name ?? 'N/A' }}</td>
                                <td class="px-3 py-3">{{ $purchase->purchase_date->format('M d, Y') ?? 'N/A' }}</td>
                                <td class="px-3 py-3 text-start">{{ number_format($purchase->total_amount, 2) ?? 'N/A' }}</td>
                                <td class="px-3 py-3 text-center">
                                    @php
                                        $status = \App\Enums\PaymentStatus::tryFrom($purchase->payment_status);
                                    @endphp
                                    @if($status)
                                        <span class="{{ $status->badgeClass() }}">
                                            {{ $status->label() }}
                                        </span>
                                    @else
                                        {{ ucfirst($purchase->payment_status) }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        @can('view', $purchase)
                                            <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-outline-info" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endcan
                                        @can('update', $purchase)
                                            <a href="{{ route('admin.purchases.edit', $purchase->id) }}" class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $purchase)
                                            <button 
                                                type="button" 
                                                class="btn btn-outline-danger" 
                                                title="Delete"
                                                wire:click="delete({{ $purchase->id }})"
                                                wire:confirm="Are you sure you want to delete purchase {{ $purchase->reference_no }}? This will permanently remove the purchase and all associated records (credits, payments) and reverse the stock adjustments. This action cannot be undone."
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-cart-x display-6 text-secondary mb-3"></i>
                                        <h6 class="fw-medium">No purchases found</h6>
                                        @if($search || $statusFilter || $dateFilter || $branchFilter || $warehouseFilter)
                                            <p class="text-secondary small">Try adjusting your search criteria</p>
                                            <button type="button" class="btn btn-outline-secondary btn-sm mt-2" wire:click="clearFilters">
                                                <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                                            </button>
                                        @else
                                            <p class="text-secondary small">Start by creating your first purchase</p>
                                            @can('purchases.create')
                                                <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary btn-sm mt-2">
                                                    <i class="bi bi-plus-lg me-1"></i>Create First Purchase
                                                </a>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($purchases->hasPages())
                <div class="border-top px-4 py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <!-- Results Info -->
                        <div class="small text-secondary">
                            Showing {{ $purchases->firstItem() ?? 0 }} to {{ $purchases->lastItem() ?? 0 }} of {{ $purchases->total() }} results
                        </div>
                        
                        <!-- Per Page Selector and Pagination -->
                        <div class="d-flex align-items-center gap-3">
                            <!-- Per Page Selector -->
                            <div class="d-flex align-items-center gap-2">
                                <select wire:model.live="perPage" id="perPage" class="form-select form-select-sm" style="width: auto;">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            
                            <!-- Pagination Links -->
                            <div>
                                {{ $purchases->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        // Note: The custom JavaScript for the delete modal has been removed 
        // in favor of Livewire's built-in wire:confirm functionality.
    </script>
    @endpush