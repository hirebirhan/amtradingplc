@extends('layouts.app')

@section('title', 'Make Payment')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8 col-md-10 mx-auto">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Make Payment for Credit #{{ $credit->reference_no }}</h5>
                    <a href="{{ route('admin.credits.show', $credit->id) }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body p-4">
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
                            <p class="mb-1"><strong>Amount:</strong> {{ number_format($credit->amount, 2) }} ETB</p>
                            <p class="mb-1"><strong>Balance Due:</strong> <span class="text-danger">{{ number_format($credit->balance, 2) }} ETB</span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Payment Details</h6>
                            <p class="mb-1"><strong>Date Created:</strong> {{ $credit->credit_date->format('M d, Y') }}</p>
                            <p class="mb-1"><strong>Due Date:</strong> {{ $credit->due_date->format('M d, Y') }}</p>
                            <p class="mb-1"><strong>Status:</strong> 
                                <span class="badge bg-{{ $credit->status === 'paid' ? 'success' : ($credit->status === 'partially_paid' ? 'warning' : 'danger') }}">
                                    {{ ucfirst(str_replace('_', ' ', $credit->status)) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('admin.credits.payments.store', $credit->id) }}" method="POST" x-data="{ paymentMethod: '{{ old('payment_method', 'cash') }}' }">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Payment Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">ETB</span>
                                    <input type="number" 
                                        name="amount" 
                                        id="amount" 
                                        class="form-control @error('amount') is-invalid @enderror" 
                                        value="{{ old('amount', $credit->balance) }}" 
                                        step="0.01" 
                                        min="0.01" 
                                        max="{{ $credit->balance }}" 
                                        required>
                                </div>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Maximum payment amount: {{ number_format($credit->balance, 2) }} ETB</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="payment_date" class="form-label">Payment Date</label>
                                <input type="date" 
                                    name="payment_date" 
                                    id="payment_date" 
                                    class="form-control @error('payment_date') is-invalid @enderror" 
                                    value="{{ old('payment_date', date('Y-m-d')) }}" 
                                    required>
                                @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" x-model="paymentMethod" required>
                                    <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="telebirr" {{ old('payment_method') === 'telebirr' ? 'selected' : '' }}>Telebirr</option>
                                    <option value="check" {{ old('payment_method') === 'check' ? 'selected' : '' }}>Check/Cheque</option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Bank Account Selection - Shown only for bank transfer -->
                            <div class="col-md-6 mb-3 bank-transfer-fields" x-show="paymentMethod === 'bank_transfer'">
                                <label for="bank_account_id" class="form-label">Bank Account</label>
                                <select name="bank_account_id" id="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror">
                                    <option value="">Select Bank Account</option>
                                    @foreach(\App\Models\BankAccount::where('is_active', true)->orderBy('account_name')->get() as $account)
                                        <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->account_name }} - {{ $account->bank_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('bank_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Telebirr Transaction Number - Shown only for telebirr -->
                            <div class="col-md-6 mb-3 telebirr-fields" x-show="paymentMethod === 'telebirr'">
                                <label for="transaction_number" class="form-label">Transaction Number</label>
                                <input type="text" 
                                    name="transaction_number" 
                                    id="transaction_number" 
                                    class="form-control @error('transaction_number') is-invalid @enderror" 
                                    value="{{ old('transaction_number') }}" 
                                    placeholder="Telebirr Transaction ID">
                                @error('transaction_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Reference Number - Shown for cash and check -->
                            <div class="col-md-6 mb-3 reference-fields" x-show="['cash', 'check'].includes(paymentMethod)">
                                <label for="reference_no" class="form-label">
                                    <span x-show="paymentMethod === 'cash'">Receipt Number (Optional)</span>
                                    <span x-show="paymentMethod === 'check'">Check Number</span>
                                </label>
                                <input type="text" 
                                    name="reference_no" 
                                    id="reference_no" 
                                    class="form-control @error('reference_no') is-invalid @enderror" 
                                    value="{{ old('reference_no') }}" 
                                    x-bind:placeholder="paymentMethod === 'check' ? 'Check Number' : 'Receipt Number (if available)'"
                                    x-bind:required="paymentMethod === 'check'">
                                @error('reference_no')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Bank Transfer Warning -->
                        <div class="row bank-transfer-fields" x-show="paymentMethod === 'bank_transfer'">
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Please ensure that the bank transfer has been completed before recording this payment.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.credits.show', $credit->id) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection 