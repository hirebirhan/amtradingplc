<div>
    <!-- Success/Error Messages -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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
                                <input type="date" class="form-control" wire:model.live="dateFrom" placeholder="Select start date">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" wire:model.live="dateTo" placeholder="Select end date">
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
                                                    <span class="badge bg-light text-dark">{{ $transfer->items_count }} items</span>
                                                </td>
                                                <td>{{ $transfer->date_initiated->format('M d, Y') }}</td>
                                                <td>
                                                    <span class="badge" style="background-color: #ffc107; color: #000;">Pending</span>
                                                </td>
                                                <td>
                                                    @php
                                                        $user = auth()->user();
                                                        $isSender = $user->isBranchManager() && $user->branch_id && 
                                                                   $transfer->source_type === 'branch' && 
                                                                   $transfer->source_id === $user->branch_id;
                                                        $isReceiver = $user->isSuperAdmin() || $user->isGeneralManager() || 
                                                                     ($user->isBranchManager() && $user->branch_id && 
                                                                      $transfer->destination_type === 'branch' && 
                                                                      $transfer->destination_id === $user->branch_id);
                                                    @endphp
                                                    <div class="d-flex gap-1">
                                                        <a href="{{ route('admin.transfers.show', $transfer) }}" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           title="View Details">
                                                            <i class="fas fa-eye me-1"></i>View
                                                        </a>
                                                        @if($isReceiver)
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-success" 
                                                                    wire:click="showConfirmModal({{ $transfer->id }}, 'approve')"
                                                                    title="Approve">
                                                                <i class="fas fa-check me-1"></i>Approve
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-danger" 
                                                                    wire:click="showConfirmModal({{ $transfer->id }}, 'reject')"
                                                                    title="Reject">
                                                                <i class="fas fa-times me-1"></i>Reject
                                                            </button>
                                                        @elseif($isSender)
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-warning" 
                                                                    wire:click="showConfirmModal({{ $transfer->id }}, 'cancel')"
                                                                    title="Cancel">
                                                                <i class="fas fa-ban me-1"></i>Cancel
                                                            </button>
                                                        @else
                                                            <span class="badge bg-secondary">Awaiting Approval</span>
                                                        @endif
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
    @if($showModal && $selectedTransfer)
    <div class="modal fade show" 
         id="confirmModal" 
         tabindex="-1" 
         style="display: block;" 
         aria-modal="true"
         role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-{{ $modalAction === 'approve' ? 'primary' : 'danger' }} text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-{{ $modalAction === 'approve' ? 'check-circle' : ($modalAction === 'cancel' ? 'ban' : 'times-circle') }} me-2"></i>
                        {{ $modalAction === 'approve' ? 'Approve Transfer' : ($modalAction === 'cancel' ? 'Cancel Transfer' : 'Reject Transfer') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Are you sure you want to <strong>{{ $modalAction }}</strong> this transfer?</p>
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-12">
                                    <strong>Reference:</strong> {{ $selectedTransfer->reference_code }}
                                </div>
                                <div class="col-12">
                                    <strong>From:</strong> 
                                    @if($selectedTransfer->source_type === 'branch')
                                        {{ $selectedTransfer->sourceBranch->name ?? 'N/A' }}
                                    @else
                                        {{ $selectedTransfer->sourceWarehouse->name ?? 'N/A' }}
                                    @endif
                                </div>
                                <div class="col-12">
                                    <strong>To:</strong> 
                                    @if($selectedTransfer->destination_type === 'branch')
                                        {{ $selectedTransfer->destinationBranch->name ?? 'N/A' }}
                                    @else
                                        {{ $selectedTransfer->destinationWarehouse->name ?? 'N/A' }}
                                    @endif
                                </div>
                                <div class="col-12">
                                    <strong>Items:</strong> {{ $selectedTransfer->items_count }} items
                                </div>
                                <div class="col-12">
                                    <strong>Date:</strong> {{ $selectedTransfer->date_initiated->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($modalAction === 'approve')
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>After approval, the transfer will be ready for processing and completion.</small>
                        </div>
                    @elseif($modalAction === 'cancel')
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <small>Cancelling this transfer will release all reserved stock. You can create a new transfer if needed.</small>
                        </div>
                    @else
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <small>Rejecting this transfer will release all reserved stock and cannot be undone.</small>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal" wire:loading.attr="disabled">
                        Cancel
                    </button>
                    <button type="button" 
                            class="btn btn-{{ $modalAction === 'approve' ? 'primary' : 'danger' }}" 
                            wire:click="confirmAction"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="confirmAction">
                            <i class="fas fa-{{ $modalAction === 'approve' ? 'check' : ($modalAction === 'cancel' ? 'ban' : 'times') }} me-1"></i>
                            {{ $modalAction === 'approve' ? 'Approve' : ($modalAction === 'cancel' ? 'Cancel' : 'Reject') }} Transfer
                        </span>
                        <span wire:loading wire:target="confirmAction">
                            <i class="fas fa-spinner fa-spin me-1"></i>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>