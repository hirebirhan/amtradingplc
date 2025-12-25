{{-- Confirmation Modal --}}
<div class="modal fade" id="confirmationModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">
                    <i class="bi bi-check-circle me-2 text-success"></i>Confirm Sale
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Modal Error Alert --}}
                @if($errors->any())
                    <div class="alert alert-danger mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-1 small">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Key Information --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <span class="text-muted small">Reference:</span>
                            <span class="fw-semibold ms-1">{{ $form['reference_no'] }}</span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small">Customer:</span>
                            <span class="fw-semibold ms-1">
                                @if($form['is_walking_customer'])
                                    <span class="badge bg-info-subtle text-info-emphasis">
                                        <i class="bi bi-person-walking me-1"></i>Walking Customer
                                    </span>
                                @elseif($selectedCustomer)
                                    {{ $selectedCustomer['name'] }}
                                @else
                                    <span class="text-muted">Not selected</span>
                                @endif
                            </span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small">Location:</span>
                            <span class="fw-semibold ms-1">
                                @php
                                    $selectedWarehouse = null;
                                    if (isset($warehouses) && is_object($warehouses) && method_exists($warehouses, 'firstWhere')) {
                                        $selectedWarehouse = $warehouses->firstWhere('id', $form['warehouse_id']);
                                    }
                                @endphp
                                @if($selectedWarehouse)
                                    @if(isset($selectedWarehouse->branch) && $selectedWarehouse->branch)
                                        {{ $selectedWarehouse->branch->name }} - {{ $selectedWarehouse->name }}
                                    @else
                                        {{ $selectedWarehouse->name }}
                                    @endif
                                @else
                                    <span class="text-muted">Not selected</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <span class="text-muted small">Date:</span>
                            <span class="fw-semibold ms-1">{{ \Carbon\Carbon::parse($form['sale_date'])->format('M d, Y') }}</span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small">Payment Method:</span>
                            <span class="fw-semibold ms-1">
                                @switch($form['payment_method'])
                                    @case('cash')
                                        Cash Payment
                                        @break
                                    @case('bank_transfer')
                                        Bank Transfer
                                        @break
                                    @case('telebirr')
                                        Telebirr
                                        @break
                                    @case('credit_advance')
                                        Credit with Advance
                                        @break
                                    @case('full_credit')
                                        Full Credit
                                        @break
                                    @default
                                        {{ ucfirst($form['payment_method']) }}
                                @endswitch
                            </span>
                        </div>
                        @if(in_array($form['payment_method'], ['telebirr', 'bank_transfer']) && !empty($form['transaction_number'] ?? ''))
                            <div class="mb-2">
                                <span class="text-muted small">Transaction Number:</span>
                                <span class="fw-semibold ms-1">{{ $form['transaction_number'] }}</span>
                            </div>
                        @endif
                        @if($form['payment_method'] === 'credit_advance' && $form['advance_amount'] > 0)
                            <div class="mb-2">
                                <span class="text-muted small">Advance:</span>
                                <span class="fw-semibold ms-1">ETB {{ number_format($form['advance_amount'], 2) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Items Summary --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        @php
                            $itemCount = is_countable($items) ? count($items) : 0;
                        @endphp
                        <h6 class="fw-semibold mb-0">Items ({{ $itemCount }})</h6>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th class="border-0 small fw-medium text-dark">Item</th>
                                    <th class="border-0 text-center small fw-medium text-dark" style="width: 80px;">Qty</th>
                                    <th class="border-0 text-end small fw-medium text-dark" style="width: 100px;">Price (ETB)</th>
                                    <th class="border-0 text-end small fw-medium text-dark" style="width: 120px;">Total (ETB)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td class="small">
                                            <div class="fw-medium">{{ $item['name'] }}</div>
                                        </td>
                                        <td class="text-center small">{{ $item['quantity'] }} pcs</td>
                                        <td class="text-end small">{{ number_format($item['price'], 2) }}</td>
                                        <td class="text-end fw-semibold small">{{ number_format($item['subtotal'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="border-0"></td>
                                    <td class="border-0 text-end small text-muted">Subtotal:</td>
                                    <td class="border-0 text-end small">{{ number_format($subtotal, 2) }}</td>
                                </tr>
                                @if($taxAmount > 0)
                                    <tr>
                                        <td colspan="2" class="border-0"></td>
                                        <td class="border-0 text-end small text-muted">Tax ({{ $form['tax'] }}%):</td>
                                        <td class="border-0 text-end small">{{ number_format($taxAmount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="2" class="border-0"></td>
                                    <td class="border-0 text-end fw-bold small">Total:</td>
                                    <td class="border-0 text-end fw-bold small">{{ number_format($totalAmount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-muted small">
                    <i class="bi bi-check-circle me-1"></i>
                    Ready to create sale! Please review the details above.
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-success" wire:click="confirmSale" wire:loading.attr="disabled">
                        <i class="bi bi-check-lg me-1"></i>
                        <span wire:loading.remove>Confirm Sale</span>
                        <span wire:loading>Creating...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
