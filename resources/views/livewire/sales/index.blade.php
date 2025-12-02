{{-- Clean Sales Management Page --}}
<div>
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Sales Management</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Track and manage all sales transactions
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                @can('sales.create')
                <a href="{{ route('admin.sales.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>
                    <span class="d-none d-sm-inline">New Sale</span>
                </a>
                @endcan
                @if(auth()->user()->isSuperAdmin())
                <button wire:click="createMissingCredits" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i>
                    <span class="d-none d-sm-inline">Sync Credits</span>
                </button>
                @endif
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
                        <input type="text" class="form-control border-start-0" placeholder="Search sales..." wire:model.live.debounce.300ms="search">
                        @if($search)
                            <button class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 bg-transparent border-0" type="button" wire:click="$set('search', '')">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" wire:model.live="status">
                        <option value="">All Status</option>
                        @foreach(\App\Enums\PaymentStatus::cases() as $paymentStatus)
                            <option value="{{ $paymentStatus->value }}">{{ $paymentStatus->label() }}</option>
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
             
            </div>
            
            <!-- Row 2: Date Range -->
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <input type="date" class="form-control" wire:model.live="dateFrom" placeholder="From date">
                </div>
                <div class="col-6 col-md-3">
                    <input type="date" class="form-control" wire:model.live="dateTo" placeholder="To date">
                </div>
            </div>
            
            @if($search || $status || $dateFrom || $dateTo || $branchFilter || $warehouseFilter)
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
                            <th class="py-3 px-4 fw-semibold text-dark">Customer</th>
                            <th class="py-3 px-3 fw-semibold text-dark">Date</th>
                            <th class="text-center py-3 px-3 fw-semibold text-dark">Items</th>
                            <th class="text-end py-3 px-3 fw-semibold text-dark">Total (ETB)</th>
                            <th class="text-center py-3 px-3 fw-semibold text-dark">Status</th>
                            <th class="text-end py-3 px-4 fw-semibold text-dark" style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            <tr>
                                <td class="py-3 px-4">
                                    @if($sale->is_walking_customer)
                                        <div class="fw-medium">
                                            <span class="badge bg-info-subtle text-info-emphasis">
                                                <i class="bi bi-person-walking me-1"></i>Walking Customer
                                            </span>
                                        </div>
                                    @else
                                        <div class="fw-medium">{{ $sale->customer->name ?? 'N/A' }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-3">{{ $sale->sale_date->format('M d, Y') }}</td>
                                <td class="text-center py-3 px-3">
                                    {{ $sale->items->count() }}
                                </td>
                                <td class="text-end py-3 px-3">
                                    <span class="fw-semibold">{{ number_format($sale->total_amount, 2) }}</span>
                                </td>
                                <td class="text-center py-3 px-3">
                                    @php
                                        $status = \App\Enums\PaymentStatus::tryFrom($sale->payment_status);
                                    @endphp
                                    @if($status)
                                        <span class="badge bg-{{ $status->color() }}-subtle text-{{ $status->color() }}-emphasis rounded-1">
                                            {{ $status->label() }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-1">{{ ucfirst($sale->payment_status) }}</span>
                                    @endif
                                </td>
                                <td class="text-end py-3 px-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.sales.show', $sale) }}" class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($sale->payment_status === 'due' || $sale->payment_status === 'partial')
                                            @php
                                                $credit = $sale->credit;
                                            @endphp
                                            @if($credit)
                                                <a href="{{ route('admin.credits.payments.create', $credit) }}" class="btn btn-outline-success" title="Make Payment">
                                                    <i class="bi bi-credit-card"></i>
                                                </a>
                                            @endif
                                        @endif
                                        <a href="{{ route('admin.sales.print', $sale) }}" class="btn btn-outline-secondary" target="_blank" title="Print">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-cart-x display-6 text-secondary mb-3"></i>
                                        <h6 class="fw-medium">No sales found</h6>
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
            @if($sales->hasPages())
                <div class="border-top px-4 py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <!-- Results Info -->
                        <div class="small text-secondary">
                            Showing {{ $sales->firstItem() ?? 0 }} to {{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }} results
                        </div>
                        
                        <!-- Pagination Links -->
                        <div>
                            {{ $sales->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>