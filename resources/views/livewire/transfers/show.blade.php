<div>
    <!-- Simple Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
        <div class="mb-2 mb-md-0">
            <h2 class="fw-bold mb-1">Transfer #{{ $transfer->reference_code }}</h2>
            <p class="text-muted mb-0">{{ $transfer->date_initiated->format('M d, Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.transfers.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
            @if($transfer->status === 'pending' && $this->canApproveTransfer())
                <button wire:click="showConfirmModal('approve')" class="btn btn-success btn-sm">
                    <i class="bi bi-check me-1"></i>Approve
                </button>
                <button wire:click="showConfirmModal('reject')" class="btn btn-danger btn-sm">
                    <i class="bi bi-x me-1"></i>Reject
                </button>
            @endif
            <button wire:click="printTransfer" class="btn btn-primary btn-sm">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>

    <!-- Transfer Details -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Transfer Details</h5>
        </div>
        <div class="card-body">
            <!-- Desktop View -->
            <div class="d-none d-md-block">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">From</span>
                            <span class="fw-medium">{{ $this->sourceLocation }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">To</span>
                            <span class="fw-medium">{{ $this->destinationLocation }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Type</span>
                            <span class="fw-medium">{{ $this->transferType }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Status</span>
                            <span class="badge {{ $this->statusBadgeClass }}" @if($this->statusBadgeStyle) style="{{ $this->statusBadgeStyle }}" @endif>
                                {{ ucfirst($transfer->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Total Items</span>
                            <span class="fw-medium">{{ $this->transferSummary['total_items'] }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Total Quantity</span>
                            <span class="fw-medium">{{ $this->transferSummary['total_quantity'] }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Created By</span>
                            <span class="fw-medium">{{ $transfer->user->name ?? 'System' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Created At</span>
                            <span class="fw-medium">{{ $transfer->created_at->format('M d, Y g:i A') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile View -->
            <div class="d-md-none">
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between align-items-center p-2 rounded">
                        <span class="text-muted">From</span>
                        <span class="fw-medium">{{ $this->sourceLocation }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded">
                        <span class="text-muted">To</span>
                        <span class="fw-medium">{{ $this->destinationLocation }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded">
                        <span class="text-muted">Type</span>
                        <span class="fw-medium">{{ $this->transferType }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded">
                        <span class="text-muted">Status</span>
                        <span class="badge {{ $this->statusBadgeClass }}" @if($this->statusBadgeStyle) style="{{ $this->statusBadgeStyle }}" @endif>
                            {{ ucfirst($transfer->status) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded">
                        <span class="text-muted">Total Items</span>
                        <span class="fw-medium">{{ $this->transferSummary['total_items'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded">
                        <span class="text-muted">Total Quantity</span>
                        <span class="fw-medium">{{ $this->transferSummary['total_quantity'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded">
                        <span class="text-muted">Created By</span>
                        <span class="fw-medium">{{ $transfer->user->name ?? 'System' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-2 rounded">
                        <span class="text-muted">Created At</span>
                        <span class="fw-medium">{{ $transfer->created_at->format('M d, Y g:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Items -->
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">Items ({{ count($transferItems) }})</h5>
        </div>
        <div class="card-body p-0">
            @if($transferItems->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 fw-semibold text-dark">#</th>
                                <th class="px-3 py-2 fw-semibold text-dark">Item</th>
                                <th class="px-3 py-2 text-center fw-semibold text-dark">Quantity</th>
                                <th class="px-3 py-2 text-end fw-semibold text-dark">Unit Cost</th>
                                <th class="px-3 py-2 text-end fw-semibold text-dark">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transferItems as $index => $transferItem)
                            <tr>
                                <td class="px-3 py-2">{{ $index + 1 }}</td>
                                <td class="px-3 py-2">{{ $transferItem->item->name }}</td>
                                <td class="px-3 py-2 text-center">{{ $transferItem->quantity }} {{ $transferItem->item->unit }}</td>
                                <td class="px-3 py-2 text-end">ETB {{ number_format($transferItem->unit_cost, 2) }}</td>
                                <td class="px-3 py-2 text-end fw-medium">ETB {{ number_format($transferItem->quantity * $transferItem->unit_cost, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="px-3 py-2 text-end fw-semibold text-dark">Total:</th>
                                <th class="px-3 py-2 text-center fw-semibold text-dark">{{ $this->transferSummary['total_quantity'] }}</th>
                                <th></th>
                                <th class="px-3 py-2 text-end fw-bold text-dark">ETB {{ number_format($transferItems->sum(function($item) { return $item->quantity * $item->unit_cost; }), 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <p class="text-muted mb-0">No items in this transfer</p>
                </div>
            @endif
        </div>
    </div>

    @if($transfer->note)
    <!-- Notes -->
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">Notes</h5>
        </div>
        <div class="card-body">
            <p class="mb-0">{{ $transfer->note }}</p>
        </div>
    </div>
    @endif

    <!-- Stock Movements -->
    @if($transfer->status === 'completed' && $this->stockMovements->count() > 0)
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">Stock Movements ({{ $this->stockMovements->count() }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 fw-semibold text-dark">Item</th>
                            <th class="px-3 py-2 fw-semibold text-dark">Location</th>
                            <th class="px-3 py-2 text-center fw-semibold text-dark">Before</th>
                            <th class="px-3 py-2 text-center fw-semibold text-dark">Change</th>
                            <th class="px-3 py-2 text-center fw-semibold text-dark">After</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->stockMovements as $movement)
                        <tr>
                            <td class="px-3 py-2">
                                <div class="fw-medium">{{ $movement->item->name }}</div>
                                <small class="text-muted">{{ $movement->item->sku }}</small>
                            </td>
                            <td class="px-3 py-2">{{ $movement->warehouse->name }}</td>
                            <td class="px-3 py-2 text-center">{{ number_format($movement->quantity_before, 2) }}</td>
                            <td class="px-3 py-2 text-center">
                                @if($movement->quantity_change > 0)
                                    <span class="text-success">+{{ number_format($movement->quantity_change, 2) }}</span>
                                @else
                                    <span class="text-danger">{{ number_format($movement->quantity_change, 2) }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center fw-medium">{{ number_format($movement->quantity_after, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

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
                        <p>Are you sure you want to {{ $modalAction }} transfer <strong>{{ $transfer->reference_code }}</strong>?</p>
                        <div class="alert alert-info">
                            <strong>From:</strong> {{ $transfer->source_location_name }}<br>
                            <strong>To:</strong> {{ $transfer->destination_location_name }}<br>
                            <strong>Items:</strong> {{ $transferItems->count() }} items
                        </div>
                        @if($modalAction === 'approve')
                            <div class="alert alert-warning">
                                <strong>Note:</strong> Approving this transfer will immediately move the stock from source to destination and cannot be undone.
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

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('print-page', () => {
                window.print();
            });
        });
    </script>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Transfer show view initialization
    });
</script>
@endpush