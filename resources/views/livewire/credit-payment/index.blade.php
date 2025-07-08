<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Payment History for Credit #{{ $credit->reference_no }}</h5>
                        <div>
                            <button wire:click="refreshData" class="btn btn-sm btn-secondary me-2">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <a href="{{ route('admin.credits.show', $credit->id) }}" class="btn btn-sm btn-secondary me-2">
                                <i class="fas fa-arrow-left"></i> Back to Credit
                            </a>
                            @if($credit->balance > 0)
                            <a href="{{ route('admin.credits.payments.create', $credit->id) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Add Payment
                            </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Credit Information</h6>
                                <p class="mb-1"><strong>Reference:</strong> {{ $credit->reference_no }}</p>
                                <p class="mb-1">
                                    <strong>For:</strong> 
                                    @if ($credit->credit_type === 'receivable')
                                        {{ $credit->customer->name ?? 'Unknown Customer' }} (Receivable)
                                    @else
                                        {{ $credit->supplier->name ?? 'Unknown Supplier' }} (Payable)
                                    @endif
                                </p>
                                <p class="mb-1"><strong>Total Amount:</strong> {{ number_format($credit->amount, 2) }} ETB</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Payment Summary</h6>
                                <p class="mb-1"><strong>Paid Amount:</strong> <span class="text-success">{{ number_format($credit->paid_amount, 2) }} ETB</span></p>
                                <p class="mb-1"><strong>Balance Due:</strong> <span class="text-danger">{{ number_format($credit->balance, 2) }} ETB</span></p>
                                <p class="mb-1"><strong>Status:</strong> 
                                    <span class="badge bg-{{ $credit->status === 'paid' ? 'success' : ($credit->status === 'partially_paid' ? 'warning' : 'danger') }}">
                                        {{ ucfirst(str_replace('_', ' ', $credit->status)) }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        @if(count($payments) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="px-3">Payment #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                                                                <th>Date</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payments as $payment)
                                                <tr>
                <td class="px-3"><span class="small">{{ $payment->number }}</span></td>
                <td><span class="small">{{ $payment->customer }}</span></td>
                <td><span class="small">{{ $payment->amount }}</span></td>
                                <td><span class="small">{{ $payment->date }}</span></td>
                <td><span class="small">{{ $payment->status }}</span></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.credit-payments.show', $payment) }}" class="btn btn-outline-info" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.credit-payments.edit', $payment) }}" class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="bi bi-cash-coin display-6 text-secondary mb-3"></i>
                                                <h6 class="fw-medium">No credit payments found</h6>
                                                <p class="text-secondary small">Try adjusting your search criteria</p>
                                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" wire:click="clearFilters">
                                                    <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $payments->links() }}
                        </div>
                        @else
                        <div class="alert alert-info">
                            No payments have been recorded yet.
                            @if($credit->balance > 0)
                            <a href="{{ route('admin.credits.payments.create', $credit->id) }}" class="alert-link">Add a payment</a>.
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
