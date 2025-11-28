<div>
    @section('title', 'Pending Approvals')
    @section('breadcrumbs')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.transfers.index') }}">Transfers</a></li>
        <li class="breadcrumb-item active">Pending Approvals</li>
    @endsection

    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Pending Approvals</h1>
                <p class="text-muted mb-0">Transfers awaiting approval</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" 
                               class="form-control" 
                               id="search"
                               wire:model.live="search" 
                               placeholder="Search by reference, warehouse, or branch...">
                    </div>
                    <div class="col-md-3">
                        <label for="perPage" class="form-label">Per Page</label>
                        <select class="form-select" id="perPage" wire:model.live="perPage">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfers Table -->
        <div class="card">
            <div class="card-body">
                @if($transfers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>
                                        <button type="button" 
                                                class="btn btn-link p-0 text-decoration-none text-dark fw-bold"
                                                wire:click="sortBy('reference_code')">
                                            Reference
                                            @if($sortField === 'reference_code')
                                                <i class="bi bi-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </button>
                                    </th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Items</th>
                                    <th>
                                        <button type="button" 
                                                class="btn btn-link p-0 text-decoration-none text-dark fw-bold"
                                                wire:click="sortBy('created_at')">
                                            Created
                                            @if($sortField === 'created_at')
                                                <i class="bi bi-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </button>
                                    </th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transfers as $transfer)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.transfers.show', $transfer) }}" 
                                               class="text-decoration-none fw-medium">
                                                {{ $transfer->reference_code }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-{{ $transfer->source_type === 'warehouse' ? 'building' : 'diagram-3' }} me-2 text-muted"></i>
                                                <div>
                                                    <div class="fw-medium">
                                                        {{ $transfer->source_type === 'warehouse' ? $transfer->sourceWarehouse?->name : $transfer->sourceBranch?->name }}
                                                    </div>
                                                    <small class="text-muted">{{ ucfirst($transfer->source_type) }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-{{ $transfer->destination_type === 'warehouse' ? 'building' : 'diagram-3' }} me-2 text-muted"></i>
                                                <div>
                                                    <div class="fw-medium">
                                                        {{ $transfer->destination_type === 'warehouse' ? $transfer->destinationWarehouse?->name : $transfer->destinationBranch?->name }}
                                                    </div>
                                                    <small class="text-muted">{{ ucfirst($transfer->destination_type) }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                {{ $transfer->items->count() }} items
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                {{ $transfer->created_at->format('M d, Y') }}
                                            </div>
                                            <small class="text-muted">{{ $transfer->created_at->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-circle me-2 text-muted"></i>
                                                {{ $transfer->user?->name }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('admin.transfers.show', $transfer) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye me-1"></i>
                                                    Review
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Showing {{ $transfers->firstItem() }} to {{ $transfers->lastItem() }} of {{ $transfers->total() }} results
                        </div>
                        {{ $transfers->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <h4 class="mt-3">No Pending Approvals</h4>
                        <p class="text-muted">All transfers have been processed or no transfers are awaiting approval.</p>
                        <a href="{{ route('admin.transfers.index') }}" class="btn btn-primary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back to All Transfers
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>