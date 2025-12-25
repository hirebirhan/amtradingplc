{{-- Stock Warning Modal --}}
@if($stockWarningType)
<div class="modal fade show" id="stockWarningModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            @if($stockWarningType === 'out_of_stock')
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-x-circle me-2"></i>Out of Stock
                    </h5>
                </div>
                <div class="modal-body">
                    <p><strong>{{ $stockWarningItem['name'] ?? '' }}</strong> is completely out of stock.</p>
                    
                    <div class="alert alert-danger mb-3">
                        <div class="row">
                            <div class="col-6">
                                <strong>Current Stock:</strong> {{ $stockWarningItem['stock'] ?? 0 }}
                            </div>
                            <div class="col-6">
                                <strong>Unit Price:</strong> ETB {{ number_format($stockWarningItem['price'] ?? 0, 2) }}
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-danger mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Warning:</strong> Selling this item will create negative inventory. Consider restocking first.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="cancelStockWarning">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-danger" wire:click="proceedWithWarning">
                        <i class="bi bi-exclamation-triangle me-1"></i>Sell Anyway
                    </button>
                </div>
            @else
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2"></i>Stock Warning
                    </h5>
                </div>
                <div class="modal-body">
                    <p><strong>{{ $stockWarningItem['name'] ?? '' }}</strong> has insufficient stock.</p>
                    
                    <div class="row mb-3">
                        <div class="col-4">
                            <div class="text-center">
                                <div class="fw-bold">Available</div>
                                <div class="fs-4 text-success">{{ $stockWarningItem['available'] ?? 0 }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center">
                                <div class="fw-bold">Requested</div>
                                <div class="fs-4 text-primary">{{ $stockWarningItem['requested'] ?? 0 }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center">
                                <div class="fw-bold">Deficit</div>
                                <div class="fs-4 text-danger">{{ $stockWarningItem['deficit'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Proceeding will result in negative inventory.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="cancelStockWarning">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-warning" wire:click="proceedWithWarning">
                        Proceed
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
@endif
