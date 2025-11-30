<div>
    <div class="card">
        <div class="card-header">
            <h4>Record Payment - {{ $credit->customer->name }}</h4>
        </div>
        
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Credit Information</h6>
                    <p><strong>Reference:</strong> {{ $credit->reference_no }}</p>
                    <p><strong>Original Amount:</strong> {{ number_format($credit->amount, 2) }} ETB</p>
                    <p><strong>Paid Amount:</strong> {{ number_format($credit->paid_amount, 2) }} ETB</p>
                    <p><strong>Balance:</strong> {{ number_format($credit->balance, 2) }} ETB</p>
                </div>
            </div>

            <form wire:submit.prevent="validateAndShowModal">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Payment Amount *</label>
                            <input type="number" wire:model="form.amount" class="form-control" step="0.01" max="{{ $credit->balance }}">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Payment Method *</label>
                            <select wire:model.live="form.payment_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="telebirr">Telebirr</option>
                            </select>
                        </div>
                    </div>
                </div>

                @if($form['payment_method'] === 'telebirr')
                    <div class="mb-3">
                        <label class="form-label">Transaction Number *</label>
                        <input type="text" wire:model="form.transaction_number" class="form-control">
                    </div>
                @endif

                @if($form['payment_method'] === 'bank_transfer')
                    <div class="mb-3">
                        <label class="form-label">Bank Account *</label>
                        <select wire:model="form.bank_account_id" class="form-select">
                            <option value="">Select Bank Account</option>
                            @foreach($bankAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_name }} - {{ $account->bank_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Payment Date *</label>
                            <input type="date" wire:model="form.payment_date" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea wire:model="form.notes" class="form-control" rows="3"></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                    <a href="{{ route('admin.credits.show', $credit) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    @if($showConfirmModal)
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Payment</h5>
                    </div>
                    <div class="modal-body">
                        <p>Please review the payment details before confirming. This action cannot be undone.</p>
                        
                        <h6>Payment Summary</h6>
                        <p><strong>Amount:</strong> {{ number_format($form['amount'], 2) }} ETB</p>
                        <p><strong>Method:</strong> {{ ucfirst(str_replace('_', ' ', $form['payment_method'])) }}</p>
                        <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($form['payment_date'])->format('M d, Y') }}</p>
                        
                        <h6>Credit Information</h6>
                        <p><strong>Credit #:</strong> {{ $credit->reference_no }}</p>
                        <p><strong>Original Amount:</strong> {{ number_format($credit->amount, 2) }} ETB</p>
                        <p><strong>Already Paid:</strong> {{ number_format($credit->paid_amount, 2) }} ETB</p>
                        <p><strong>Current Balance:</strong> {{ number_format($credit->balance, 2) }} ETB</p>
                        <p><strong>Payment Amount:</strong> {{ number_format($form['amount'], 2) }} ETB</p>
                        <p><strong>Remaining After Payment:</strong> {{ number_format($credit->balance - $form['amount'], 2) }} ETB</p>
                        
                        @if($credit->balance - $form['amount'] <= 0)
                            <div class="alert alert-success">Credit will be fully paid</div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showConfirmModal', false)">Cancel</button>
                        <button type="button" class="btn btn-success" wire:click="confirmPayment">Confirm Payment</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>