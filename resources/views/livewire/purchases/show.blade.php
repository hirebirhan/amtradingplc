<div>
    <!-- Simple Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Purchase Order</h4>
            <p class="text-muted mb-0">#{{ $purchase->reference_no }} • {{ $purchase->purchase_date->format('M d, Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if($purchase->status !== 'received')
                <button wire:click="confirmStockUpdate" class="btn btn-primary" wire:loading.attr="disabled">
                    <i class="bi bi-box-arrow-in-down me-1"></i>Receive Items
                </button>
            @endif
            <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <!-- Status Overview -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 mb-1">{{ count($purchase->items) }}</div>
                                <small class="text-muted">Items</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 mb-1">{{ number_format($purchase->total_amount, 2) }}</div>
                                <small class="text-muted">Total Amount</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 mb-1">{{ $purchase->supplier->name }}</div>
                                <small class="text-muted">Supplier</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 mb-1">{{ $purchase->warehouse->name }}</div>
                                <small class="text-muted">Destination</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Items List -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="fw-semibold mb-0">Purchase Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 fw-semibold text-dark">Item Details</th>
                                    <th class="text-center py-3 px-4 fw-semibold text-dark">Quantity</th>
                                    <th class="text-end py-3 px-4 fw-semibold text-dark">Unit Cost</th>
                                    <th class="text-end py-3 px-4 fw-semibold text-dark">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase->items as $item)
                                <tr>
                                    <td class="py-3 px-4">
                                        <div class="fw-medium">{{ $item->item->name }}</div>
                                        @if($item->item->sku)
                                            <small class="text-muted">SKU: {{ $item->item->sku }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center py-3 px-4">
                                        {{ $item->quantity }} {{ $item->item->unit ?? 'pcs' }}
                                    </td>
                                    <td class="text-end py-3 px-4">
                                        {{ number_format($item->unit_cost, 2) }}
                                    </td>
                                    <td class="text-end py-3 px-4">
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

        <!-- Summary Sidebar -->
        <div class="col-12 col-lg-4">
            <!-- Payment Status -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-semibold mb-3">Payment Status</h6>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Status:</span>
                        <span class="badge bg-{{ $purchase->payment_status === 'paid' ? 'success' : ($purchase->payment_status === 'partial' ? 'warning' : 'danger') }}-subtle text-{{ $purchase->payment_status === 'paid' ? 'success' : ($purchase->payment_status === 'partial' ? 'warning' : 'danger') }}-emphasis rounded-1">
                            {{ ucfirst(str_replace('_', ' ', $purchase->payment_status)) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Order Status:</span>
                        <span class="badge bg-secondary-subtle text-secondary-emphasis border rounded-1">{{ ucfirst($purchase->status) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Payment Method:</span>
                        <span class="fw-medium">{{ ucfirst(str_replace('_', ' ', $purchase->payment_method)) }}</span>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-semibold mb-3">Financial Summary</h6>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal:</span>
                            <span>{{ number_format(collect($purchase->items)->sum('subtotal'), 2) }}</span>
                        </div>
                        @if($purchase->discount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Discount:</span>
                            <span class="text-danger">-{{ number_format($purchase->discount, 2) }}</span>
                        </div>
                        @endif
                        @if($purchase->tax > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tax ({{ $purchase->tax }}%):</span>
                            <span>+{{ number_format((collect($purchase->items)->sum('subtotal') - $purchase->discount) * ($purchase->tax / 100), 2) }}</span>
                        </div>
                        @endif
                        @if($purchase->shipping > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Shipping:</span>
                            <span>+{{ number_format($purchase->shipping, 2) }}</span>
                        </div>
                        @endif
                    </div>
                    
                    <hr class="my-3">
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-semibold">Total Amount:</span>
                            <span class="fw-semibold h6 mb-0">{{ number_format($purchase->total_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-success">Amount Paid:</span>
                            <span class="text-success fw-semibold">{{ number_format($purchase->paid_amount, 2) }}</span>
                        </div>
                        @if($purchase->due_amount > 0)
                        <div class="d-flex justify-content-between">
                            <span class="text-danger">Amount Due:</span>
                            <span class="text-danger fw-semibold">{{ number_format($purchase->due_amount, 2) }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-semibold mb-3">Additional Information</h6>
                    
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Created By</div>
                        <div class="fw-medium">{{ $purchase->user->name ?? 'System' }}</div>
                    </div>
                    
                    @if($purchase->notes)
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Notes</div>
                        <div class="text-muted">{{ $purchase->notes }}</div>
                    </div>
                    @endif
                    
                    @if($paymentHistory && count($paymentHistory) > 0)
                    <div>
                        <div class="text-muted small mb-2">Payment History ({{ count($paymentHistory) }})</div>
                        @foreach($paymentHistory->take(3) as $payment)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <div class="small fw-medium">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</div>
                                <div class="small text-muted">{{ $payment->payment_date->format('M d, Y') }}</div>
                            </div>
                            <div class="text-success fw-semibold">{{ number_format($payment->amount, 2) }}</div>
                        </div>
                        @endforeach
                        @if(count($paymentHistory) > 3)
                        <div class="text-center">
                            <small class="text-muted">+{{ count($paymentHistory) - 3 }} more payments</small>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    @if($confirmingStockUpdate)
    <div class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm">
                <div class="modal-header bg-transparent border-0 py-3">
                    <h5 class="modal-title fw-semibold">Receive Purchase Items</h5>
                    <button type="button" class="btn-close" wire:click="cancelStockUpdate"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="mb-3">Are you sure you want to receive all items from this purchase order?</p>
                    <div class="alert alert-info border-0">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>This will:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Add items to warehouse inventory</li>
                            <li>Update stock levels</li>
                            <li>Mark purchase as received</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer bg-transparent border-0 py-3">
                    <button type="button" class="btn btn-outline-secondary" wire:click="cancelStockUpdate">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="updateStock" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="updateStock">
                            <i class="bi bi-check-lg me-1"></i>Receive Items
                        </span>
                        <span wire:loading wire:target="updateStock">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>