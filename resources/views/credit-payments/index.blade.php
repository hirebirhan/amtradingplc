@extends('layouts.app')

@section('title', 'Payment History')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Payment History for Credit #{{ $credit->reference_no }}</h5>
                    <div>
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
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Method</th>
                                    <th scope="col">Reference No</th>
                                    <th scope="col">Bank Details</th>
                                    <th scope="col">Reference</th>
                                    <th scope="col">Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                    <td>
                                        <span class="fw-bold text-success">
                                            {{ number_format($payment->amount, 2) }} ETB
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ ucfirst($payment->payment_method) }}
                                        </span>
                                    </td>
                                    <td>{{ $payment->reference_no ?? 'N/A' }}</td>
                                    <td>
                                        @if($payment->receiver_bank_name || $payment->receiver_account_holder || $payment->receiver_account_number)
                                            <div class="small">
                                                @if($payment->receiver_bank_name)
                                                    <div><strong>Bank:</strong> {{ $payment->receiver_bank_name }}</div>
                                                @endif
                                                @if($payment->receiver_account_holder)
                                                    <div><strong>Holder:</strong> {{ $payment->receiver_account_holder }}</div>
                                                @endif
                                                @if($payment->receiver_account_number)
                                                    <div><strong>Account:</strong> {{ $payment->receiver_account_number }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $payment->reference ?? '-' }}</small>
                                    </td>
                                    <td>{{ $payment->user->name ?? 'Unknown' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $payments->links() }}
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
@endsection 