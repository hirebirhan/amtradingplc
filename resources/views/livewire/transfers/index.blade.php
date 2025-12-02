<div>
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Stock Transfers</h4>
            </div>
            <p class="text-secondary mb-0 small">
                Manage and track all inventory transfers between warehouses and branches
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <a href="{{ route('admin.transfers.pending') }}" class="btn btn-warning btn-sm">
                <i class="bi bi-clock me-1"></i>
                <span class="d-none d-sm-inline">Pending Transfers</span>
            </a>
            <a href="{{ route('admin.transfers.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">New Transfer</span>
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-0">
        <div class="p-4 border-bottom">
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Search transfers...">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select wire:model.live="transferDirection" class="form-select">
                        <option value="">All Directions</option>
                        <option value="outgoing">Outgoing</option>
                        <option value="incoming">Incoming</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" class="form-control" wire:model.live="dateFrom">
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" class="form-control" wire:model.live="dateTo">
                </div>
            </div>
            <div class="mt-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="resetFilters">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reset
                </button>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">Transfer #</th>
                                <th class="px-4 py-3">From</th>
                                <th class="px-4 py-3">To</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-end pe-5">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $transfer)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="fw-medium small">{{ $transfer->reference_code }}</span>
                                </td>
                                <td class="px-4 py-3"><span class="small">{{ $transfer->source_location_name }}</span></td>
                                <td class="px-4 py-3"><span class="small">{{ $transfer->destination_location_name }}</span></td>
                                <td class="px-4 py-3"><span class="small">{{ $transfer->date_initiated->format('M d, Y') }}</span></td>
                                <td class="px-4 py-3">
                                    @if($transfer->status === 'pending')
                                        <span class="badge" style="background-color: #ffc107; color: #000;">{{ ucfirst($transfer->status) }}</span>
                                    @elseif($transfer->status === 'completed')
                                        <span class="badge bg-success">{{ ucfirst($transfer->status) }}</span>
                                    @elseif($transfer->status === 'approved')
                                        <span class="badge bg-info">{{ ucfirst($transfer->status) }}</span>
                                    @elseif($transfer->status === 'in_transit')
                                        <span class="badge bg-primary">{{ ucfirst($transfer->status) }}</span>
                                    @elseif($transfer->status === 'rejected')
                                        <span class="badge bg-danger">{{ ucfirst($transfer->status) }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($transfer->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-end pe-5">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.transfers.show', $transfer) }}" class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-arrow-left-right display-6 text-secondary mb-3"></i>
                                        <h6 class="fw-medium">No transfers found</h6>
                                        <p class="text-secondary small">Try adjusting your search criteria</p>
                                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" wire:click="resetFilters">
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
        </div>
        @if($transfers->hasPages())
            <div class="border-top px-4 py-3">
                <div class="d-flex justify-content-end gap-2">
                    {{ $transfers->links() }}
                </div>
            </div>
        @endif
    </div>
</div>