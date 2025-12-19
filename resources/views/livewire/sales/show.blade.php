{{-- Clean Sales Show Page --}}
<div>
    <!-- Simple Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Sales Order</h4>
            <p class="text-muted mb-0">#{{ $sale->reference_no }} • {{ $sale->sale_date->format('M d, Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.print', $sale) }}" class="btn btn-outline-secondary" target="_blank">
                <i class="bi bi-printer me-1"></i>Print
            </a>
            <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary">
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
                                <div class="h5 mb-1">{{ count($sale->items) }}</div>
                                <small class="text-muted">Items</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 mb-1">{{ number_format($sale->total_amount, 2) }}</div>
                                <small class="text-muted">Total Amount</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                @if($sale->is_walking_customer)
                                    <div class="h5 mb-1">
                                        <span class="badge bg-info-subtle text-info-emphasis">
                                            <i class="bi bi-person-walking me-1"></i>Walking Customer
                                        </span>
                                    </div>
                                @else
                                    <div class="h5 mb-1">{{ $sale->customer->name ?? 'N/A' }}</div>
                                @endif
                                <small class="text-muted">Customer</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 mb-1">{{ $sale->warehouse->name ?? 'N/A' }}</div>
                                <small class="text-muted">Source</small>
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
                    <h6 class="fw-semibold mb-0">Sale Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 fw-semibold text-dark">Item Details</th>
                                    <th class="text-center py-3 px-4 fw-semibold text-dark">Quantity</th>
                                    <th class="text-end py-3 px-4 fw-semibold text-dark">Unit Price</th>
                                    <th class="text-end py-3 px-4 fw-semibold text-dark">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sale->items as $item)
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
                                        {{ number_format($item->unit_price, 2) }}
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
                        <span class="badge bg-{{ $sale->payment_status === 'paid' ? 'success' : ($sale->payment_status === 'partial' ? 'warning' : 'danger') }}-subtle text-{{ $sale->payment_status === 'paid' ? 'success' : ($sale->payment_status === 'partial' ? 'warning' : 'danger') }}-emphasis rounded-1">
                            {{ ucfirst(str_replace('_', ' ', $sale->payment_status)) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Payment Method:</span>
                        <span class="fw-medium">{{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}</span>
                    </div>
                    
                    @if(in_array($sale->payment_method, ['telebirr', 'bank_transfer']) && $sale->transaction_number)
                    <div class="border-top pt-3 mt-3">
                        <h6 class="fw-semibold mb-2">Payment Details</h6>
                        <div class="mb-2">
                            <div class="text-muted small mb-1">Transaction Number:</div>
                            <div class="fw-medium">{{ $sale->transaction_number }}</div>
                        </div>
                        @if($sale->payment_method === 'telebirr' && $sale->receiver_account_holder)
                        <div class="mb-2">
                            <div class="text-muted small mb-1">Account Holder Name:</div>
                            <div class="fw-medium">{{ $sale->receiver_account_holder }}</div>
                        </div>
                        @endif
                        @if($sale->payment_method === 'bank_transfer')
                            @if($sale->receiver_bank_name)
                            <div class="mb-2">
                                <div class="text-muted small mb-1">Bank Name:</div>
                                <div class="fw-medium">{{ $sale->receiver_bank_name }}</div>
                            </div>
                            @endif
                            @if($sale->receiver_account_holder)
                            <div class="mb-2">
                                <div class="text-muted small mb-1">Account Holder Name:</div>
                                <div class="fw-medium">{{ $sale->receiver_account_holder }}</div>
                            </div>
                            @endif
                            @if($sale->receiver_account_number)
                            <div class="mb-2">
                                <div class="text-muted small mb-1">Account Number:</div>
                                <div class="fw-medium">{{ $sale->receiver_account_number }}</div>
                            </div>
                            @endif
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-semibold mb-3">Financial Summary</h6>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-semibold">Total Amount:</span>
                            <span class="fw-semibold h6 mb-0">{{ number_format($sale->total_amount, 2) }}</span>
                        </div>
                        @if($sale->payment_status === 'partial')
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-success">Amount Paid:</span>
                            <span class="text-success fw-semibold">{{ number_format($sale->paid_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-danger">Amount Due:</span>
                            <span class="text-danger fw-semibold">{{ number_format($sale->due_amount, 2) }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Credit Information (if applicable) -->
            @if($sale->payment_method === 'credit_advance' || $sale->payment_method === 'full_credit')
                @php
                    $credit = \App\Models\Credit::where('reference_type', 'sale')
                        ->where('reference_id', $sale->id)
                        ->first();
                @endphp
                @if($credit)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-semibold mb-0">Credit Information</h6>
                            <a href="{{ route('admin.credits.show', $credit->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>View Details
                            </a>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Credit Reference:</span>
                                <span class="fw-medium">{{ $credit->reference_no }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Credit Amount:</span>
                                <span class="fw-semibold">{{ number_format($credit->amount, 2) }} ETB</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-success">Paid Amount:</span>
                                <span class="text-success fw-semibold">{{ number_format($credit->paid_amount, 2) }} ETB</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-danger">Balance Due:</span>
                                <span class="text-danger fw-semibold">{{ number_format($credit->balance, 2) }} ETB</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Status:</span>
                                <span class="badge bg-{{ 
                                    $credit->status === 'paid' ? 'success' : 
                                    ($credit->status === 'partially_paid' ? 'warning' : 
                                    ($credit->status === 'overdue' ? 'danger' : 'secondary')) 
                                }}-subtle text-{{ 
                                    $credit->status === 'paid' ? 'success' : 
                                    ($credit->status === 'partially_paid' ? 'warning' : 
                                    ($credit->status === 'overdue' ? 'danger' : 'secondary')) 
                                }}-emphasis rounded-1">
                                    {{ ucfirst(str_replace('_', ' ', $credit->status)) }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Recent Payments -->
                        @if($credit->payments->count() > 0)
                        <div class="mt-3">
                            <h6 class="fw-semibold mb-2">Recent Payments</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th class="py-2 px-2 fw-semibold text-dark">Date</th>
                                            <th class="py-2 px-2 fw-semibold text-dark">Amount</th>
                                            <th class="py-2 px-2 fw-semibold text-dark">Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($credit->payments->take(3) as $payment)
                                        <tr>
                                            <td class="py-2 px-2">{{ $payment->payment_date->format('M d') }}</td>
                                            <td class="py-2 px-2 text-success fw-medium">{{ number_format($payment->amount, 2) }}</td>
                                            <td class="py-2 px-2">
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-1">{{ ucfirst($payment->payment_method) }}</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($credit->payments->count() > 3)
                            <div class="text-center mt-2">
                                <small class="text-muted">+{{ $credit->payments->count() - 3 }} more payments</small>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            @endif

            <!-- Additional Info -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-semibold mb-3">Additional Information</h6>
                    
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Customer</div>
                        @if($sale->is_walking_customer)
                            <div class="fw-medium">
                                <span class="badge bg-info-subtle text-info-emphasis">
                                    <i class="bi bi-person-walking me-1"></i>Walking Customer
                                </span>
                            </div>
                            <div class="small text-muted">No customer information recorded</div>
                        @else
                            <div class="fw-medium">{{ $sale->customer->name ?? 'N/A' }}</div>
                            @if($sale->customer && $sale->customer->phone)
                                <div class="small text-muted">{{ $sale->customer->phone }}</div>
                            @endif
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Source Warehouse</div>
                        <div class="fw-medium">{{ $sale->warehouse->name ?? 'N/A' }}</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Created By</div>
                        <div class="fw-medium">{{ $sale->user->name ?? 'System' }}</div>
                    </div>
                    
                    @if($sale->notes)
                    <div>
                        <div class="text-muted small mb-1">Notes</div>
                        <div class="text-muted">{{ $sale->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>