<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Bank Account Details</h5>
            <div>
                <a href="{{ route('admin.bank-accounts.edit', $bankAccount) }}" class="btn btn-primary me-2">
                    <i class="fa-solid fa-edit me-2"></i>Edit
                </a>
                @if(Auth::user()->is_admin || Auth::user()->branch_id === $bankAccount->branch_id)
                    <button type="button" class="btn btn-danger" wire:click="delete"
                            onclick="return confirm('Are you sure you want to delete this bank account?')">
                        <i class="fa-solid fa-trash me-2"></i>Delete
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">Account Name</h6>
                    <p class="mb-0">{{ $bankAccount->account_name }}</p>
                </div>

                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">Account Number</h6>
                    <p class="mb-0">{{ $bankAccount->account_number }}</p>
                </div>

                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">Bank Name</h6>
                    <p class="mb-0">{{ $bankAccount->bank_name }}</p>
                </div>

                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">Branch Name</h6>
                    <p class="mb-0">{{ $bankAccount->branch_name }}</p>
                </div>

                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">Account Type</h6>
                    <p class="mb-0">{{ ucfirst($bankAccount->account_type) }}</p>
                </div>

                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">Opening Balance</h6>
                    <p class="mb-0">{{ number_format($bankAccount->opening_balance, 2) }} {{ $bankAccount->currency }}</p>
                </div>

                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">Current Balance</h6>
                    <p class="mb-0">{{ number_format($bankAccount->current_balance, 2) }} {{ $bankAccount->currency }}</p>
                </div>

                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">Status</h6>
                    <p class="mb-0">
                        <span class="badge bg-{{ $bankAccount->is_active ? 'success' : 'danger' }}">
                            {{ $bankAccount->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </p>
                </div>

                @if(Auth::user()->is_admin)
                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">Branch</h6>
                    <p class="mb-0">{{ $bankAccount->branch->name }}</p>
                </div>
                @endif

                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">Created At</h6>
                    <p class="mb-0">{{ $bankAccount->created_at->format('M d, Y H:i') }}</p>
                </div>

                <div class="col-md-6 mb-3">
                    <h6 class="text-muted">Last Updated</h6>
                    <p class="mb-0">{{ $bankAccount->updated_at->format('M d, Y H:i') }}</p>
                </div>

                @if($bankAccount->description)
                <div class="col-12 mb-3">
                    <h6 class="text-muted">Description</h6>
                    <p class="mb-0">{{ $bankAccount->description }}</p>
                </div>
                @endif
            </div>

            <div class="mt-4">
                <a href="{{ route('admin.bank-accounts.index') }}" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>
    </div>
</div>