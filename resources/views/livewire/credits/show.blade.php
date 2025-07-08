<div>
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <h4 class="fw-bold mb-0">Credit #{{ $credit->reference_no }}</h4>
                    @php
                        $effectiveStatus = $credit->status;
                        if ($savingsInfo && $savingsInfo['total_savings'] > 0 && $savingsInfo['effective_balance'] <= 0) {
                            $effectiveStatus = 'paid';
                        }
                    @endphp
                    <span class="badge bg-{{ 
                        $effectiveStatus === 'paid' ? 'success' : 
                        ($effectiveStatus === 'partially_paid' ? 'warning' : 
                        ($effectiveStatus === 'overdue' ? 'danger' : 'secondary')) 
                    }}">
                        {{ ucfirst(str_replace('_', ' ', $effectiveStatus)) }}
                    </span>
                </div>
                <p class="text-secondary mb-0">
                    @if($credit->credit_type === 'receivable')
                        Customer: {{ $credit->customer->name ?? 'Unknown Customer' }}
                    @else
                        Supplier: {{ $credit->supplier->name ?? 'Unknown Supplier' }}
                    @endif
                    • {{ $credit->credit_date->format('M d, Y') }}
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <a href="{{ route('admin.credits.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
                
                @php
                    $canAddPayment = $effectiveStatus !== 'paid' && $credit->balance > 0 && (!$savingsInfo || $savingsInfo['effective_balance'] > 0);
                @endphp
                
                @if($canAddPayment)
                    @can('credit-payments.create')
                    <a href="{{ route('admin.credits.payments.create', $credit->id) }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add Payment
                    </a>
                    @endcan
                @endif
            </div>
        </div>

        <!-- Financial Summary Cards -->
        <div class="row g-3 mb-4">
            <!-- Total Amount -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 text-center">
                        <div class="text-muted small mb-1">Total Amount</div>
                        <div class="h5 fw-bold mb-0">{{ number_format($credit->amount, 2) }}</div>
                        <div class="text-muted small">ETB</div>
                    </div>
                </div>
            </div>

            <!-- Paid Amount -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 text-center">
                        <div class="text-muted small mb-1">Paid Amount</div>
                        <div class="h5 fw-bold text-success mb-0">{{ number_format($credit->paid_amount, 2) }}</div>
                        <div class="text-muted small">ETB</div>
                    </div>
                </div>
            </div>

            <!-- Balance Due -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 text-center">
                        <div class="text-muted small mb-1">
                            @if($savingsInfo && $savingsInfo['total_savings'] > 0)
                                Effective Balance
                            @else
                                Balance Due
                            @endif
                        </div>
                        @if($savingsInfo && $savingsInfo['total_savings'] > 0)
                            @if($savingsInfo['effective_balance'] <= 0)
                                <div class="h5 fw-bold text-success mb-0">0.00</div>
                                <div class="text-success small">Fully Paid</div>
                            @else
                                <div class="h5 fw-bold text-warning mb-0">{{ number_format($savingsInfo['effective_balance'], 2) }}</div>
                                <div class="text-muted small">ETB</div>
                            @endif
                        @else
                            <div class="h5 fw-bold text-danger mb-0">{{ number_format($credit->balance, 2) }}</div>
                            <div class="text-muted small">ETB</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Savings (if applicable) -->
            @if($savingsInfo && $savingsInfo['total_savings'] > 0)
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3 text-center">
                        <div class="text-muted small mb-1">Savings</div>
                        <div class="h5 fw-bold text-info mb-0">{{ number_format($savingsInfo['total_savings'], 2) }}</div>
                        <div class="text-muted small">ETB</div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Main Content -->
        <div class="row g-4">
            <!-- Payment History -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0">
                        <h6 class="fw-semibold mb-0">Payment History</h6>
                    </div>
                    <div class="card-body p-0">
                        @if(count($recentPayments) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-3 fw-semibold text-dark">Date & Time</th>
                                            <th class="px-3 py-3 text-end fw-semibold text-dark">Amount</th>
                                            <th class="px-3 py-3 fw-semibold text-dark">Method</th>
                                            <th class="px-4 py-3 fw-semibold text-dark">Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentPayments as $payment)
                                            <tr>
                                                <td class="px-4 py-3">
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-medium">{{ $payment->payment_date->format('M d, Y') }}</span>
                                                        <span class="text-secondary small">{{ $payment->payment_date->format('H:i') }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-3 text-end">
                                                    <div class="fw-bold">{{ number_format($payment->amount, 2) }} ETB</div>
                                                </td>
                                                <td class="px-3 py-3">
                                                    <div class="fw-medium">{{ ucfirst($payment->payment_method) }}</div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-medium">{{ $payment->reference_no ?? '-' }}</span>
                                                        @if($payment->reference)
                                                            <span class="text-secondary small">{{ Str::limit($payment->reference, 30) }}</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            @can('credit-payments.view')
                            <div class="p-3 border-top">
                                <a href="{{ route('admin.credits.payments.index', $credit->id) }}" class="btn btn-outline-primary btn-sm">
                                    View All Payments
                                </a>
                            </div>
                            @endcan
                        @else
                            <div class="p-4 text-center">
                                <div class="text-muted mb-3">
                                    <i class="fas fa-receipt fa-2x"></i>
                                </div>
                                <h6 class="text-muted">No payments recorded</h6>
                                @if($canAddPayment)
                                    @can('credit-payments.create')
                                    <a href="{{ route('admin.credits.payments.create', $credit->id) }}" class="btn btn-primary btn-sm mt-2">
                                        Add First Payment
                                    </a>
                                    @endcan
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Credit Details Section -->
        <div class="row g-4 mt-2">
            <!-- Credit Information -->
            <div class="col-lg-6 col-md-12">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0">
                        <h6 class="fw-semibold mb-0">Credit Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <div class="text-muted small mb-1">Type</div>
                                <div class="fw-medium">
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                        {{ ucfirst($credit->credit_type) }}
                                    </span>
                                </div>
                            </div>
                            
                            <div>
                                <div class="text-muted small mb-1">Reference</div>
                                <div class="fw-medium">
                                    @if($credit->reference_type === 'purchase' && $credit->purchase)
                                        <a href="{{ route('admin.purchases.show', $credit->purchase->id) }}" class="text-decoration-none">
                                            Purchase #{{ $credit->purchase->reference_no }}
                                        </a>
                                    @elseif($credit->reference_type === 'sale' && $credit->sale)
                                        <a href="{{ route('admin.sales.show', $credit->sale->id) }}" class="text-decoration-none">
                                            Sale #{{ $credit->sale->reference_no }}
                                        </a>
                                    @else
                                        {{ ucfirst($credit->reference_type) }} #{{ $credit->reference_no }}
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <div class="text-muted small mb-1">Due Date</div>
                                <div class="fw-medium">
                                    @if($credit->due_date->isPast() && $effectiveStatus !== 'paid')
                                        <span class="text-danger">{{ $credit->due_date->format('M d, Y') }}</span>
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    @else
                                        {{ $credit->due_date->format('M d, Y') }}
                                    @endif
                                </div>
                            </div>
                            
                            @if($credit->description)
                            <div>
                                <div class="text-muted small mb-1">Description</div>
                                <div class="fw-medium">{{ $credit->description }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Early Closure Info -->
            @if($savingsInfo && $savingsInfo['has_closing_prices'])
            <div class="col-lg-6 col-md-12">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0">
                        <h6 class="fw-semibold mb-0">
                            <i class="fas fa-handshake me-1"></i> Early Closure
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="text-muted small mb-2">
                            Closed with negotiated prices
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="h5 fw-bold text-info mb-0">{{ number_format($savingsInfo['total_savings'], 2) }}</div>
                            <div class="text-muted">ETB saved</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
