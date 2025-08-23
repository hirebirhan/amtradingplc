{{-- Clean Suppliers Management Page --}}
<div>
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Suppliers</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Manage your supplier network, track vendor relationships, and monitor purchase history
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @can('suppliers.create')
            <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">New Supplier</span>
            </a>
            @endcan
        </div>
    </div>

    @can('suppliers.view')
    <!-- Main Content Card -->
    <div class="card border-0 shadow-sm">
        <!-- Filters -->
        <div class="p-4 border-bottom">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" placeholder="Search suppliers..." wire:model.live.debounce.300ms="search">
                        @if($search)
                            <button class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2" type="button" wire:click="$set('search', '')" style="background: none; border: none;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select" wire:model.live="branchFilter">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
              
            </div>

            @if($search || $branchFilter)
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearFilters">
                        <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                    </button>
                </div>
            @endif
        </div>

        <div class="card-body p-0">
            <!-- Table Section -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3 cursor-pointer fw-semibold text-dark" wire:click="sortBy('name')">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Supplier</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-3 py-3 fw-semibold text-dark">Contact</th>
                            <th class="px-4 py-3 text-end fw-semibold text-dark">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 32px; height: 32px;">
                                            <i class="bi bi-truck"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium">{{ $supplier->name }}</div>
                                            <div class="text-secondary small">{{ $supplier->reference_no ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    @if($supplier->email || $supplier->phone)
                                        <div class="small">
                                            @if($supplier->email)
                                                <div class="text-secondary"><i class="bi bi-envelope me-1"></i>{{ $supplier->email }}</div>
                                            @endif
                                            @if($supplier->phone)
                                                <div class="text-secondary"><i class="bi bi-telephone me-1"></i>{{ $supplier->phone }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-secondary small">No contact info</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.suppliers.show', $supplier) }}" class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-truck-x display-6 text-secondary mb-3"></i>
                                        <h6 class="fw-medium">No suppliers found</h6>
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
            @if($suppliers->hasPages())
                <div class="border-top px-4 py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <!-- Results Info -->
                        <div class="small text-secondary">
                            Showing {{ $suppliers->firstItem() ?? 0 }} to {{ $suppliers->lastItem() ?? 0 }} of {{ $suppliers->total() }} results
                        </div>
                        
                        <div class="d-flex align-items-center gap-3">
                            {{ $suppliers->links() }}
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
                    <p class="text-secondary mb-4">You don't have permission to view suppliers. Contact your administrator if you need access to this section.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                        </a>
                        @can('suppliers.create')
                        <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Create Supplier
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
</div>