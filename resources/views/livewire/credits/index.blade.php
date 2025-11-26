<div>
    {{-- Clean Credits Management Page --}}
    
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Credits</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Track receivables and payables, monitor payment status, and manage credit relationships
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <!-- Export Dropdown -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download me-1"></i>
                    <span class="d-none d-sm-inline">Export</span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                    <li><button class="dropdown-item" wire:click="exportExcel">
                        <i class="bi bi-file-earmark-excel me-2 text-success"></i>Export Excel
                    </button></li>
                    <li><button class="dropdown-item" wire:click="exportCsv">
                        <i class="bi bi-file-earmark-text me-2 text-info"></i>Export CSV
                    </button></li>
                    <li><button class="dropdown-item" wire:click="exportPdf">
                        <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Export PDF
                    </button></li>
                </ul>
            </div>
            
            <button wire:click="toggleGrouping" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-collection me-1"></i>
                <span class="d-none d-sm-inline">Group View</span>
            </button>
            <button wire:click="togglePaidCredits" class="btn {{ $showPaidCredits ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                <i class="bi bi-eye me-1"></i>
                <span class="d-none d-sm-inline">{{ $showPaidCredits ? 'Hide Paid' : 'Show Paid' }}</span>
                @if($paidCount > 0)
                    <span class="badge bg-primary-subtle text-primary-emphasis ms-1">{{ $paidCount }}</span>
                @endif
            </button>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card border-0 shadow-sm">
        <!-- Clean Filters Section -->
        <div class="card-header border-bottom">
            <!-- Row 1: Main Filters -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" placeholder="Search credits..." wire:model.live.debounce.300ms="search">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select wire:model.live="filters.type" class="form-select">
                        <option value="">All Types</option>
                        <option value="receivable">Receivable</option>
                        <option value="payable">Payable</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select wire:model.live="filters.status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="partially_paid">Partially Paid</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" wire:model.live="filters.date_from" class="form-control" placeholder="From">
                </div>
                <div class="col-6 col-md-3">
                    <input type="date" wire:model.live="filters.date_to" class="form-control" placeholder="To">
                </div>
            </div>
            
            @if($search || $filters['type'] || $filters['status'] || $filters['date_from'] || $filters['date_to'])
            <div class="d-flex justify-content-end">
                <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                </button>
            </div>
            @endif
            
            <!-- Party selection (Customer or Supplier) -->
            @if($filters['type'] === 'receivable')
                <div class="dropdown w-100 mt-2" wire:key="customer-dropdown">
                    <div class="input-group">
                        <span class="input-group-text bg-success bg-opacity-10 text-success">
                            <i class="bi bi-person"></i>
                        </span>
                        @if($selectedCustomer)
                            <input type="text" readonly class="form-control form-control-sm" value="{{ $selectedCustomer->name }}">
                            <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="clearCustomer">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @else
                            <input type="text" class="form-control form-control-sm dropdown-toggle" 
                                wire:model.live.debounce.300ms="customerSearch" 
                                placeholder="Search customer..." 
                                data-bs-toggle="dropdown" 
                                autocomplete="off">
                            <ul class="dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;">
                                @forelse($customers as $customer)
                                    <li><a class="dropdown-item text-wrap" href="#" wire:click.prevent="selectCustomer({{ $customer['id'] }})">{{ $customer['name'] }}</a></li>
                                @empty
                                    <li><span class="dropdown-item">No customers found</span></li>
                                @endforelse
                            </ul>
                        @endif
                    </div>
                </div>
            @elseif($filters['type'] === 'payable')
                <div class="dropdown w-100 mt-2" wire:key="supplier-dropdown">
                    <div class="input-group">
                        <span class="input-group-text bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-building"></i>
                        </span>
                        @if($selectedSupplier)
                            <input type="text" readonly class="form-control form-control-sm" value="{{ $selectedSupplier->name }}">
                            <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="clearSupplier">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @else
                            <input type="text" class="form-control form-control-sm dropdown-toggle" 
                                wire:model.live.debounce.300ms="supplierSearch" 
                                placeholder="Search supplier..." 
                                data-bs-toggle="dropdown" 
                                autocomplete="off">
                            <ul class="dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;">
                                @forelse($suppliers as $supplier)
                                    <li><a class="dropdown-item text-wrap" href="#" wire:click.prevent="selectSupplier({{ $supplier['id'] }})">{{ $supplier['name'] }}</a></li>
                                @empty
                                    <li><span class="dropdown-item">No suppliers found</span></li>
                                @endforelse
                            </ul>
                        @endif
                    </div>
                </div>
            @elseif(!$filters['type'])
                <div class="row g-2 mt-2">
                    <div class="col-md-6">
                        <div class="dropdown w-100" wire:key="customer-dropdown-combo">
                            <div class="input-group">
                                <span class="input-group-text bg-success bg-opacity-10 text-success">
                                    <i class="bi bi-person"></i>
                                </span>
                                @if($selectedCustomer)
                                    <input type="text" readonly class="form-control form-control-sm" value="{{ $selectedCustomer->name }}">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="clearCustomer">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                @else
                                    <input type="text" class="form-control form-control-sm dropdown-toggle" 
                                        wire:model.live.debounce.300ms="customerSearch" 
                                        placeholder="Customer..." 
                                        data-bs-toggle="dropdown" 
                                        autocomplete="off">
                                    <ul class="dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;">
                                        @forelse($customers as $customer)
                                            <li><a class="dropdown-item text-wrap" href="#" wire:click.prevent="selectCustomer({{ $customer['id'] }})">{{ $customer['name'] }}</a></li>
                                        @empty
                                            <li><span class="dropdown-item">No results</span></li>
                                        @endforelse
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="dropdown w-100" wire:key="supplier-dropdown-combo">
                            <div class="input-group">
                                <span class="input-group-text bg-danger bg-opacity-10 text-danger">
                                    <i class="bi bi-building"></i>
                                </span>
                                @if($selectedSupplier)
                                    <input type="text" readonly class="form-control form-control-sm" value="{{ $selectedSupplier->name }}">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="clearSupplier">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                @else
                                    <input type="text" class="form-control form-control-sm dropdown-toggle" 
                                        wire:model.live.debounce.300ms="supplierSearch" 
                                        placeholder="Supplier..." 
                                        data-bs-toggle="dropdown" 
                                        autocomplete="off">
                                    <ul class="dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;">
                                        @forelse($suppliers as $supplier)
                                            <li><a class="dropdown-item text-wrap" href="#" wire:click.prevent="selectSupplier({{ $supplier['id'] }})">{{ $supplier['name'] }}</a></li>
                                        @empty
                                            <li><span class="dropdown-item">No results</span></li>
                                        @endforelse
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="card-body p-0">
            <!-- Table Section -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-dark">Type</th>
                            <th class="py-3 px-3 fw-semibold text-dark">Party</th>
                            <th class="text-end py-3 px-3 fw-semibold text-dark">Amount (ETB)</th>
                            <th class="text-end py-3 px-3 fw-semibold text-dark">Paid (ETB)</th>
                            <th class="text-end py-3 px-3 fw-semibold text-dark">Balance (ETB)</th>
                            <th class="text-center py-3 px-3 fw-semibold text-dark">Status</th>
                            <th class="text-end py-3 px-4 fw-semibold text-dark" style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($credits as $credit)
                            <tr>
                                <td class="py-3 px-4">
                                    <span class="fw-medium text-{{ $credit->credit_type === 'receivable' ? 'success' : 'danger' }}">
                                        {{ $credit->credit_type === 'receivable' ? 'Receivable' : 'Payable' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    @if($credit->credit_type === 'receivable')
                                        <div class="fw-medium">{{ $credit->customer->name ?? 'Unknown Customer' }}</div>
                                        @if($credit->customer && $credit->customer->phone)
                                            <small class="text-muted">{{ $credit->customer->phone }}</small>
                                        @endif
                                    @else
                                        <div class="fw-medium">{{ $credit->supplier->name ?? 'Unknown Supplier' }}</div>
                                        @if($credit->supplier && $credit->supplier->phone)
                                            <small class="text-muted">{{ $credit->supplier->phone }}</small>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-end py-3 px-3">
                                    <span class="fw-semibold">{{ number_format($credit->amount, 2) }}</span>
                                </td>
                                <td class="text-end py-3 px-3">
                                    <span class="text-success fw-semibold">{{ number_format($credit->paid_amount, 2) }}</span>
                                    @if($credit->reference_type === 'sale' && $credit->sale && $credit->sale->advance_amount > 0)
                                        <div class="text-info small">
                                            <i class="bi bi-cash-coin me-1"></i>{{ number_format($credit->sale->advance_amount, 2) }} advance
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end py-3 px-3">
                                    @php
                                        $effectiveInfo = $this->getEffectiveCreditInfo($credit);
                                        $effectiveBalance = $effectiveInfo['effective_balance'];
                                        $effectiveStatus = $effectiveInfo['effective_status'];
                                    @endphp
                                    <span class="fw-semibold {{ $effectiveBalance > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($effectiveBalance, 2) }}
                                    </span>
                                    @if($effectiveInfo['has_savings'])
                                        <div class="text-info small">
                                            <i class="bi bi-handshake me-1"></i>+{{ number_format($effectiveInfo['total_savings'], 2) }} saved
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center py-3 px-3">
                                    @php
                                        $status = \App\Enums\PaymentStatus::tryFrom($effectiveStatus);
                                    @endphp
                                    @if($status)
                                        <span class="badge bg-{{ $status->color() }}-subtle text-{{ $status->color() }}-emphasis rounded-1">
                                            {{ $status->label() }}
                                        </span>
                                    @else
                                        <span class="fw-medium text-{{ $effectiveStatus === 'paid' ? 'success' : ($effectiveStatus === 'partially_paid' ? 'warning' : ($effectiveStatus === 'overdue' ? 'danger' : 'secondary')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $effectiveStatus)) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end py-3 px-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.credits.show', $credit) }}" class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($effectiveBalance > 0)
                                        <a href="{{ route('admin.credits.payments.create', $credit->id) }}" class="btn btn-outline-success" title="Add Payment">
                                            <i class="bi bi-plus-circle"></i>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-credit-card display-6 text-secondary mb-3"></i>
                                        <h6 class="fw-medium">No credits found</h6>
                                        <p class="text-secondary small">Try adjusting your search criteria</p>
                                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" wire:click="resetFilters">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </table>
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="d-lg-none">
                @forelse ($credits as $credit)
                    @php
                        $effectiveInfo = $this->getEffectiveCreditInfo($credit);
                        $effectiveBalance = $effectiveInfo['effective_balance'];
                        $effectiveStatus = $effectiveInfo['effective_status'];
                    @endphp
                    <div class="p-3 border-bottom">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div class="flex-grow-1">
                                <div class="fw-medium">
                                    @if($credit->credit_type === 'receivable')
                                        {{ $credit->customer->name ?? 'Unknown Customer' }}
                                    @else
                                        {{ $credit->supplier->name ?? 'Unknown Supplier' }}
                                    @endif
                                </div>
                                <div class="small text-muted">
                                    {{ $credit->credit_date->format('M d, Y') }} - Due: {{ $credit->due_date->format('M d, Y') }}
                                </div>
                            </div>
                            <div>
                                <span class="badge bg-{{ $credit->credit_type === 'receivable' ? 'success' : 'danger' }}-subtle text-{{ $credit->credit_type === 'receivable' ? 'success' : 'danger' }}-emphasis">
                                    {{ $credit->credit_type === 'receivable' ? 'Receivable' : 'Payable' }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-4">
                                <div class="small text-muted">Amount</div>
                                <div class="fw-medium">{{ number_format($credit->amount, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">Paid</div>
                                <div class="fw-medium">{{ number_format($credit->paid_amount, 2) }}</div>
                                @if($credit->reference_type === 'sale' && $credit->sale && $credit->sale->advance_amount > 0)
                                    <div class="text-info small">
                                        💰 {{ number_format($credit->sale->advance_amount, 2) }} advance
                                    </div>
                                @endif
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">Balance</div>
                                <div class="fw-medium {{ $effectiveBalance > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($effectiveBalance, 2) }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.credits.show', $credit->id) }}" class="btn btn-sm btn-outline-primary flex-fill">
                                👁 View
                            </a>
                            @if($effectiveBalance > 0)
                            <a href="{{ route('admin.credits.payments.create', $credit->id) }}" class="btn btn-sm btn-outline-success flex-fill">
                                💰 Pay
                            </a>
                            @endif
                            <a href="{{ route('admin.credits.payments.index', $credit->id) }}" class="btn btn-sm btn-outline-secondary flex-fill">
                                📜 History
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="text-muted mb-3 fs-1">💳</div>
                            <h5 class="text-muted">No credits found</h5>
                            @if($showPaidCredits)
                                <p class="text-muted">No credits match your current filter criteria.</p>
                                <button wire:click="resetFilters" class="btn btn-outline-primary">
                                    🔄 Reset Filters
                                </button>
                            @else
                                <p class="text-muted">No active credits found.</p>
                                <div class="d-flex gap-2">
                                    <button wire:click="togglePaidCredits" class="btn btn-outline-primary">
                                        👁 Show Paid Credits
                                    </button>
                                    <button wire:click="resetFilters" class="btn btn-outline-secondary">
                                        🔄 Reset Filters
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($credits->hasPages())
                <div class="border-top px-4 py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <!-- Results Info -->
                        <div class="small text-secondary">
                            Showing {{ $credits->firstItem() ?? 0 }} to {{ $credits->lastItem() ?? 0 }} of {{ $credits->total() }} results
                        </div>
                        
                    </div>
                </div>
            @endif
        </div>
    </div>
    

</div>
