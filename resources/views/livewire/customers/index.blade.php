{{-- Clean Customers Management Page --}}
<div>
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Customer Management</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Manage your customer directory, track credit limits, and monitor customer relationships
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @can('customers.export')
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="exportCustomers">
                <i class="bi bi-download me-1"></i>
                <span class="d-none d-sm-inline">Export</span>
            </button>
            @endcan
            @can('customers.create')
            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">New Customer</span>
            </a>
            @endcan
        </div>
    </div>

    @can('customers.view')
    <!-- Main Content Card -->
    <div class="card border-0 shadow-sm">
        <!-- Filters -->
        <div class="card-header border-bottom py-3">
            <div class="row g-3">
                <!-- Search and Reset Row -->
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" 
                               class="form-control border-start-0 ps-0" 
                               placeholder="Search customers by name, email, or phone..." 
                               wire:model.live.debounce.300ms="search">
                        @if($search)
                            <button class="btn btn-outline-secondary border-start-0" 
                                    type="button" 
                                    wire:click="$set('search', '')" 
                                    title="Clear search">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Per Page -->
                <div class="col-md-4">
                    <select class="form-select" wire:model.live="perPage">
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>

                <!-- Filters Row -->
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <!-- Type Filter -->
                        <div class="flex-grow-1">
                            <select class="form-select form-select-sm" wire:model.live="typeFilter">
                                <option value="">All Types</option>
                                <option value="retail">Retail</option>
                                <option value="wholesale">Wholesale</option>
                            </select>
                        </div>
                        
                        <!-- Branch Filter -->
                        <div class="flex-grow-1">
                            <select class="form-select form-select-sm" wire:model.live="branchFilter">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>



                        <!-- Active Filters & Reset -->
                        @if($search || $typeFilter || $branchFilter)
                            <div class="d-flex gap-2 align-items-center ms-auto">
                                @if($search)
                                    <span class="badge bg-primary-subtle text-primary d-flex align-items-center gap-1">
                                        Search: {{ $search }}
                                        <button type="button" class="btn btn-link text-primary p-0 text-decoration-none" wire:click="$set('search', '')">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </span>
                                @endif
                                @if($typeFilter)
                                    <span class="badge bg-primary-subtle text-primary d-flex align-items-center gap-1">
                                        Type: {{ ucfirst($typeFilter) }}
                                        <button type="button" class="btn btn-link text-primary p-0 text-decoration-none" wire:click="$set('typeFilter', '')">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </span>
                                @endif
                                @if($branchFilter)
                                    <span class="badge bg-primary-subtle text-primary d-flex align-items-center gap-1">
                                        Branch: {{ $branches->where('id', $branchFilter)->first()->name ?? '' }}
                                        <button type="button" class="btn btn-link text-primary p-0 text-decoration-none" wire:click="$set('branchFilter', '')">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </span>
                                @endif

                                <button type="button" 
                                        class="btn btn-link text-danger text-decoration-none p-0 d-flex align-items-center gap-1" 
                                        wire:click="clearFilters">
                                    <i class="bi bi-arrow-clockwise"></i>
                                    Reset All
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Table Section -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 cursor-pointer fw-semibold text-dark" wire:click="sortBy('name')">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Customer Info</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-3 py-3 cursor-pointer fw-semibold text-dark" wire:click="sortBy('type')">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Type</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-3 py-3 fw-semibold text-dark">Contact</th>
                            <th class="px-4 py-3 text-end fw-semibold text-dark">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium">{{ $customer->name }}</span>
                                        <span class="text-secondary small">{{ $customer->reference_no }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                        {{ ucfirst($customer->type) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    @if($customer->email || $customer->phone)
                                        <div class="small">
                                            @if($customer->email)
                                                <div class="text-secondary"><i class="bi bi-envelope me-1"></i>{{ $customer->email }}</div>
                                            @endif
                                            @if($customer->phone)
                                                <div class="text-secondary"><i class="bi bi-telephone me-1"></i>{{ $customer->phone }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-secondary small">No contact info</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        @can('customers.view')
                                        <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @endcan
                                        @can('customers.edit')
                                        <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @endcan
                                        @can('customers.delete')
                                        <button type="button" class="btn btn-outline-danger" title="Delete"
                                            wire:click="confirmDelete({{ $customer->id }})"
                                            wire:confirm="Are you sure you want to delete {{ $customer->name }}? This action cannot be undone.">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-person-x display-6 text-secondary mb-3"></i>
                                        <h6 class="fw-medium">No customers found</h6>
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
            @if($customers->hasPages())
                <div class="border-top px-4 py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <!-- Results Info -->
                        <div class="small text-secondary">
                            Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} results
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
                                {{ $customers->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @else
    <!-- Access Denied Message -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-lock text-secondary display-4"></i>
                    </div>
                    <h4 class="fw-semibold mb-3">Access Restricted</h4>
                    <p class="text-secondary mb-4">You don't have permission to view customers. Contact your administrator if you need access to this section.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                        </a>
                        @can('customers.create')
                        <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Create Customer
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
</div>
