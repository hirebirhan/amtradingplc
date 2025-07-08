<div>
    {{-- Clean Expenses Management Page --}}
    
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Expenses</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Track business expenses, categorize spending, and monitor financial outflows
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @can('expenses.create')
            <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">New Expense</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm">
        <!-- Clean Filters Section -->
        <div class="card-header bg-white border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="Search expenses..." wire:model.live.debounce.300ms="search">
                        @if($search)
                            <button class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 text-muted" type="button" wire:click="clearSearch">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>
                </div>
                
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="categoryFilter">
                        <option value="">All Categories</option>
                        @foreach($this->categories as $category => $label)
                            <option value="{{ $category }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <input type="date" class="form-control" wire:model.live="dateFilter" placeholder="Filter by date">
                </div>
                
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="perPage">
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <button class="btn btn-outline-secondary btn-sm w-100" wire:click="clearFilters">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
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
                                <th class="px-3">Expense</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $expense)
                            <tr>
                                                <td class="px-3">
                    <span class="fw-medium small">{{ $expense->description }}</span>
                </td>
                <td><span class="small">{{ $expense->type }}</span></td>
                <td><span class="small">{{ $expense->amount }}</span></td>
                <td><span class="small">{{ $expense->date }}</span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.expenses.show', $expense) }}" class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.expenses.edit', $expense) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-cash-stack display-6 text-secondary mb-3"></i>
                                        <h6 class="fw-medium">No expenses found</h6>
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
                @forelse($expenses as $expense)
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 32px; height: 32px;">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">{{ $expense->description }}</div>
                                    <div class="text-secondary small">
                                        <i class="bi bi-calendar me-1"></i>{{ $expense->date->format('M d, Y') }}
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                @if($expense->status === 'approved')
                                    <span class="badge bg-success-subtle text-success-emphasis">Approved</span>
                                @elseif($expense->status === 'pending')
                                    <span class="badge bg-warning-subtle text-warning-emphasis">Pending</span>
                                @elseif($expense->status === 'rejected')
                                    <span class="badge bg-danger-subtle text-danger-emphasis">Rejected</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <div class="text-secondary small">
                                <i class="bi bi-tags me-1"></i>{{ $expense->category }}
                            </div>
                            <div class="fw-medium">ETB {{ number_format($expense->amount, 2) }}</div>
                            @if($expense->payment_method)
                                <div class="text-secondary small">
                                    <i class="bi bi-credit-card me-1"></i>{{ $expense->payment_method }}
                                </div>
                            @endif
                        </div>
                        
                        <div class="d-flex gap-1">
                            @can('expenses.view')
                            <a href="{{ route('admin.expenses.show', $expense->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                            @endcan
                            @can('expenses.edit')
                            <a href="{{ route('admin.expenses.edit', $expense->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            @endcan
                            @can('expenses.delete')
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                wire:click="delete({{ $expense->id }})"
                                wire:confirm="Are you sure you want to delete this expense?">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-cash-stack text-secondary" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-secondary">No expenses found</h5>
                        @if($search || $categoryFilter || $statusFilter || $dateFilter)
                            <p class="text-secondary">Try adjusting your search criteria</p>
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="clearFilters">
                                Clear Filters
                            </button>
                        @else
                            <p class="text-secondary">Start by adding your first expense</p>
                            @can('expenses.create')
                            <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i> Add First Expense
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
        @if($expenses->hasPages())
            <div class="d-flex justify-content-center py-3 border-top">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>
</div>