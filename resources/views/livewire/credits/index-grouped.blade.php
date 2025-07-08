<div>
    <!-- Export Buttons -->
    <div class="d-flex justify-content-end mb-3">
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="exportDropdownGrouped" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download me-1"></i>Export
            </button>
            <ul class="dropdown-menu" aria-labelledby="exportDropdownGrouped">
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
    </div>

    <div class="row mb-3">
        <!-- Stats Cards -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-clock text-primary"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Active Credits</span>
                        <h4 class="mb-0 mt-1">{{ number_format($activeCount) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-check-circle text-success"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Paid Credits</span>
                        <h4 class="mb-0 mt-1">{{ number_format($paidCount) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle bg-info bg-opacity-10 p-2 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-pie-chart text-info"></i>
                    </div>
                    <div>
                        <span class="text-muted small">Total Credits</span>
                        <h4 class="mb-0 mt-1">{{ number_format($activeCount + $paidCount) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm hover-shadow-sm transition-all">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">Credits Management (Grouped View)</h5>
                <div class="d-flex gap-2">
                    <button wire:click="toggleGrouping" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-list me-1"></i> Standard View
                    </button>
                    <button wire:click="togglePaidCredits" class="btn {{ $showPaidCredits ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                        <i class="bi bi-eye me-1"></i>
                        {{ $showPaidCredits ? 'Hide Paid' : 'Show Paid' }}
                        @if($paidCount > 0)
                            <span class="badge bg-white text-primary ms-1">{{ $paidCount }}</span>
                        @endif
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Compact Search and Filters -->
            <div class="p-3 border-bottom">
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
                    <div class="col-6 col-md-2">
                        <input type="date" wire:model.live="filters.date_to" class="form-control" placeholder="To">
                    </div>
                    <div class="col-6 col-md-1">
                        <select wire:model.live="perPage" class="form-select">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                
                <!-- Row 2: Party Selection -->
                <div class="row g-3">
                    <div class="col-12">
                        @if($filters['type'] === 'receivable')
                            <div class="dropdown w-100" wire:key="customer-dropdown">
                                <div class="input-group">
                                    <span class="input-group-text bg-success bg-opacity-10 text-success">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    @if($selectedCustomer)
                                        <input type="text" readonly class="form-control" value="{{ $selectedCustomer->name }}">
                                        <button class="btn btn-outline-secondary" type="button" wire:click="clearCustomer">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    @else
                                        <input type="text" class="form-control dropdown-toggle" 
                                            wire:model.live.debounce.300ms="customerSearch" 
                                            placeholder="Search customer..." 
                                            data-bs-toggle="dropdown" 
                                            autocomplete="off">
                                        <ul class="dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;">
                                            @forelse($customers as $customer)
                                                <li><a class="dropdown-item text-wrap" href="#" wire:click.prevent="selectCustomer({{ $customer['id'] }})">{{ $customer['name'] }} @if($customer['phone'])({{ $customer['phone'] }})@endif</a></li>
                                            @empty
                                                <li><span class="dropdown-item">No customers found</span></li>
                                            @endforelse
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @elseif($filters['type'] === 'payable')
                            <div class="dropdown w-100" wire:key="supplier-dropdown">
                                <div class="input-group">
                                    <span class="input-group-text bg-danger bg-opacity-10 text-danger">
                                        <i class="bi bi-building"></i>
                                    </span>
                                    @if($selectedSupplier)
                                        <input type="text" readonly class="form-control" value="{{ $selectedSupplier->name }}">
                                        <button class="btn btn-outline-secondary" type="button" wire:click="clearSupplier">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    @else
                                        <input type="text" class="form-control dropdown-toggle" 
                                            wire:model.live.debounce.300ms="supplierSearch" 
                                            placeholder="Search supplier..." 
                                            data-bs-toggle="dropdown" 
                                            autocomplete="off">
                                        <ul class="dropdown-menu w-100" style="max-height: 200px; overflow-y: auto;">
                                            @forelse($suppliers as $supplier)
                                                <li><a class="dropdown-item text-wrap" href="#" wire:click.prevent="selectSupplier({{ $supplier['id'] }})">{{ $supplier['name'] }} @if($supplier['phone'])({{ $supplier['phone'] }})@endif</a></li>
                                            @empty
                                                <li><span class="dropdown-item">No suppliers found</span></li>
                                            @endforelse
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="dropdown w-100" wire:key="customer-dropdown-combo">
                                        <div class="input-group">
                                            <span class="input-group-text bg-success bg-opacity-10 text-success">
                                                <i class="bi bi-person"></i>
                                            </span>
                                            @if($selectedCustomer)
                                                <input type="text" readonly class="form-control" value="{{ $selectedCustomer->name }}">
                                                <button class="btn btn-outline-secondary" type="button" wire:click="clearCustomer">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            @else
                                                <input type="text" class="form-control dropdown-toggle" 
                                                    wire:model.live.debounce.300ms="customerSearch" 
                                                    placeholder="Search customer..." 
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
                                                <input type="text" readonly class="form-control" value="{{ $selectedSupplier->name }}">
                                                <button class="btn btn-outline-secondary" type="button" wire:click="clearSupplier">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            @else
                                                <input type="text" class="form-control dropdown-toggle" 
                                                    wire:model.live.debounce.300ms="supplierSearch" 
                                                    placeholder="Search supplier..." 
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
                </div>
                
                @if($search || $filters['type'] || $filters['status'] || $filters['date_from'] || $filters['date_to'])
                <div class="d-flex justify-content-end mt-3">
                    <button wire:click="resetFilters" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                    </button>
                </div>
                @endif
            </div>

            <!-- Grouped Credits Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 px-4">Party</th>
                            <th class="py-3 px-3">Type</th>
                            <th class="text-center py-3 px-3">Credits</th>
                            <th class="text-end py-3 px-3">Total Amount</th>
                            <th class="text-end py-3 px-3">Total Paid</th>
                            <th class="text-end py-3 px-3">Balance</th>
                            <th class="py-3 px-3">Latest Credit</th>
                            <th class="py-3 px-3">Earliest Due</th>
                            <th class="text-center py-3 px-4" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groupedCredits as $credit)
                            <tr>
                                <td class="py-3 px-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-{{ $credit['type'] === 'receivable' ? 'success' : 'danger' }} bg-opacity-10 me-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-{{ $credit['type'] === 'receivable' ? 'person' : 'building' }} text-{{ $credit['type'] === 'receivable' ? 'success' : 'danger' }}"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium">{{ $credit['name'] }}</div>
                                            <small class="text-muted">{{ $credit['type'] === 'receivable' ? 'Customer' : 'Supplier' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="fw-medium text-{{ $credit['type'] === 'receivable' ? 'success' : 'danger' }}">
                                        {{ $credit['type'] === 'receivable' ? 'Receivable' : 'Payable' }}
                                    </span>
                                </td>
                                <td class="text-center py-3 px-3">
                                    <span class="fw-semibold text-info">{{ $credit['count'] }}</span>
                                </td>
                                <td class="text-end py-3 px-3">
                                    <span class="fw-semibold">{{ number_format($credit['total_amount'], 2) }}</span>
                                </td>
                                <td class="text-end py-3 px-3">
                                    <span class="text-success fw-semibold">{{ number_format($credit['total_paid'], 2) }}</span>
                                </td>
                                <td class="text-end py-3 px-3">
                                    <span class="fw-semibold {{ $credit['total_balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($credit['total_balance'], 2) }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($credit['latest_credit_date'])->format('M d, Y') }}</small>
                                </td>
                                <td class="py-3 px-3">
                                    <small class="{{ \Carbon\Carbon::parse($credit['earliest_due_date']) < now() && $credit['total_balance'] > 0 ? 'text-danger fw-medium' : 'text-muted' }}">
                                        {{ \Carbon\Carbon::parse($credit['earliest_due_date'])->format('M d, Y') }}
                                    </small>
                                </td>
                                <td class="text-center py-3 px-4">
                                    <div class="btn-group btn-group-sm">
                                        @if($credit['entity_type'] === 'customer')
                                            <a href="{{ route('admin.customers.show', $credit['entity_id']) }}" class="btn btn-outline-info" title="View Customer">
                                                <i class="bi bi-person"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('admin.suppliers.show', $credit['entity_id']) }}" class="btn btn-outline-info" title="View Supplier">
                                                <i class="bi bi-building"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.credits.index') }}?{{ $credit['entity_type'] }}_id={{ $credit['entity_id'] }}" class="btn btn-outline-primary" title="View All Credits">
                                            <i class="bi bi-list"></i>
                                        </a>
                                        @if($credit['total_balance'] > 0)
                                        <a href="{{ route('admin.credits.payments.create', $credit['entity_id']) }}" class="btn btn-outline-success" title="Make Payment">
                                            <i class="bi bi-plus-circle"></i>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-credit-card display-6 text-secondary mb-3"></i>
                                        <h6 class="fw-medium">No credits found</h6>
                                        <p class="text-secondary small">
                                            @if($showPaidCredits)
                                                No credits match your current filter criteria
                                            @else
                                                No active credits found
                                            @endif
                                        </p>
                                        <div class="d-flex gap-2 mt-2">
                                            @if(!$showPaidCredits)
                                            <button wire:click="togglePaidCredits" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye me-1"></i>Show Paid Credits
                                            </button>
                                            @endif
                                            <button wire:click="resetFilters" class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-arrow-clockwise me-1"></i>Reset Filters
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($groupedCredits->hasPages())
                <div class="border-top px-4 py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <!-- Results Info -->
                        <div class="small text-secondary">
                            Showing {{ $groupedCredits->firstItem() ?? 0 }} to {{ $groupedCredits->lastItem() ?? 0 }} of {{ $groupedCredits->total() }} groups
                        </div>
                        
                        <!-- Pagination Links -->
                        <div>
                            {{ $groupedCredits->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div> 