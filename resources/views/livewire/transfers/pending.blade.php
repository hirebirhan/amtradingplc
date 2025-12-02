<div>
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">Pending Transfers</h1>
                        <p class="mb-0 text-muted">Manage and approve pending stock transfers</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.transfers.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Transfer
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" wire:model.live="search" placeholder="Search transfers...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" wire:model.live="dateFrom">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" wire:model.live="dateTo">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters">
                                    <i class="fas fa-undo me-1"></i>Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfers Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        @if($transfers->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th wire:click="sortBy('reference_code')" style="cursor: pointer;">
                                                Reference
                                                @if($sortField === 'reference_code')
                                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                                @endif
                                            </th>
                                            <th>Source</th>
                                            <th>Destination</th>
                                            <th>Items</th>
                                            <th wire:click="sortBy('date_initiated')" style="cursor: pointer;">
                                                Date
                                                @if($sortField === 'date_initiated')
                                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                                @endif
                                            </th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transfers as $transfer)
                                            <tr>
                                                <td>
                                                    <strong>{{ $transfer->reference_code }}</strong>
                                                </td>
                                                <td>
                                                    @if($transfer->source_type === 'branch')
                                                        <span class="badge bg-info">{{ $transfer->sourceBranch->name ?? 'N/A' }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ $transfer->sourceWarehouse->name ?? 'N/A' }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($transfer->destination_type === 'branch')
                                                        <span class="badge bg-info">{{ $transfer->destinationBranch->name ?? 'N/A' }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ $transfer->destinationWarehouse->name ?? 'N/A' }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">{{ $transfer->items->count() }} items</span>
                                                </td>
                                                <td>{{ $transfer->date_initiated->format('M d, Y') }}</td>
                                                <td>
                                                    <span class="badge" style="background-color: #ffc107; color: #000;">Pending</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <button type="button" class="btn btn-sm btn-success" 
                                                                wire:click="showConfirmModal({{ $transfer->id }}, 'approve')">
                                                            <i class="fas fa-check me-1"></i>Approve
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                wire:click="showConfirmModal({{ $transfer->id }}, 'reject')">
                                                            <i class="fas fa-times me-1"></i>Reject
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <select class="form-select form-select-sm" wire:model.live="perPage" style="width: auto;">
                                        <option value="10">10 per page</option>
                                        <option value="25">25 per page</option>
                                        <option value="50">50 per page</option>
                                    </select>
                                </div>
                                <div>
                                    {{ $transfers->links() }}
                                </div>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No pending transfers found</h5>
                                <p class="text-muted">All transfers have been processed or no transfers match your search criteria.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    @if($showModal)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $modalAction === 'approve' ? 'Approve Transfer' : 'Reject Transfer' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        @if($selectedTransfer)
                            <p>Are you sure you want to {{ $modalAction }} transfer <strong>{{ $selectedTransfer->reference_code }}</strong>?</p>
                            <div class="alert alert-info">
                                <strong>From:</strong> {{ $selectedTransfer->sourceBranch->name ?? $selectedTransfer->sourceWarehouse->name }}<br>
                                <strong>To:</strong> {{ $selectedTransfer->destinationBranch->name ?? $selectedTransfer->destinationWarehouse->name }}<br>
                                <strong>Items:</strong> {{ $selectedTransfer->items->count() }} items
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                        <button type="button" class="btn btn-{{ $modalAction === 'approve' ? 'success' : 'danger' }}" wire:click="confirmAction">
                            {{ $modalAction === 'approve' ? 'Approve' : 'Reject' }} Transfer
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>