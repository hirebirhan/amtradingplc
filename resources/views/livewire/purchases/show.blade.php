<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Purchase Order</h4>
            <p class="text-muted mb-0">#{{ $purchase->reference_no }} • {{ $purchase->purchase_date->format('M d, Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if($purchase->status === 'confirmed')
                <button wire:click="confirmStockUpdate" class="btn btn-success" wire:loading.attr="disabled">
                    <i class="bi bi-box-arrow-in-down me-1"></i>Receive Items
                </button>
            @elseif($purchase->status === 'received')
                <span class="badge bg-success px-3 py-2">
                    <i class="bi bi-check-circle-fill me-1"></i>Items Received
                </span>
            @elseif($purchase->payment_status === 'paid' && $purchase->status !== 'received')
                <button wire:click="confirmStockUpdate" class="btn btn-success" wire:loading.attr="disabled">
                    <i class="bi bi-box-arrow-in-down me-1"></i>Receive Items
                </button>
            @endif
            <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="h5">{{ count($purchase->items) }}</div>
                            <small class="text-muted">Items</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="h5">{{ number_format($purchase->total_amount, 2) }}</div>
                            <small class="text-muted">Total Amount</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="h5">{{ $purchase->supplier->name }}</div>
                            <small class="text-muted">Supplier</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="h5">{{ $purchase->warehouse->name }}</div>
                            <small class="text-muted">Destination</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6>Purchase Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Item Details</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Unit Cost</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $item->item->name }}</div>
                                        @if($item->item->sku)
                                            <small class="text-muted">SKU: {{ $item->item->sku }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ $item->quantity }} {{ $item->item->unit ?? 'pcs' }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($item->unit_cost, 2) }}
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold">{{ number_format($item->subtotal, 2) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h6>Payment Status</h6>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Status:</span>
                        <span class="badge bg-{{ $purchase->payment_status === 'paid' ? 'success' : ($purchase->payment_status === 'partial' ? 'warning' : 'danger') }}">
                            {{ ucfirst($purchase->payment_status) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Order Status:</span>
                        <span class="badge bg-{{ $purchase->status === 'received' ? 'success' : 'primary' }}">
                            {{ ucfirst($purchase->status) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Payment Method:</span>
                        <span>{{ ucfirst(str_replace('_', ' ', $purchase->payment_method)) }}</span>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h6>Financial Summary</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal:</span>
                        <span>{{ number_format(collect($purchase->items)->sum('subtotal'), 2) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-semibold">Total Amount:</span>
                        <span class="fw-semibold">{{ number_format($purchase->total_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-success">Amount Paid:</span>
                        <span class="text-success">{{ number_format($purchase->paid_amount, 2) }}</span>
                    </div>
                    @if($purchase->due_amount > 0)
                    <div class="d-flex justify-content-between">
                        <span class="text-danger">Amount Due:</span>
                        <span class="text-danger">{{ number_format($purchase->due_amount, 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6>Additional Information</h6>
                    <div class="mb-3">
                        <div class="text-muted small">Created By</div>
                        <div>{{ $purchase->user->name ?? 'System Administrator' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($confirmingStockUpdate)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Receive Purchase Items</h5>
                    <button type="button" class="btn-close" wire:click="cancelStockUpdate"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to receive all items from this purchase order?</p>
                    <div class="alert alert-info">
                        <strong>This will:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Add items to warehouse inventory</li>
                            <li>Update stock levels</li>
                            <li>Mark purchase as received</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="cancelStockUpdate">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="updateStock" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="updateStock">Receive Items</span>
                        <span wire:loading wire:target="updateStock">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>