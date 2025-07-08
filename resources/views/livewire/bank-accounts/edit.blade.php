<div>
    <!-- Main Card Container -->
    <div class="card border-0 shadow-sm">
        <!-- Header -->
        <div class="card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Edit Bank Account</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary mb-0 small">{{ $bankAccount->account_name }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.bank-accounts.show', $bankAccount) }}" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-eye me-1"></i>View Details
                    </a>
                    <a href="{{ route('admin.bank-accounts.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body p-0">
            <!-- Error Alert -->
            @if ($errors->any())
                <div class="p-4 pb-0">
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-1 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            <!-- Form Content -->
            <form id="bankAccountEditForm" wire:submit="update" class="p-4 pt-0">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="account_name" class="form-label fw-medium">
                            Account Name <span class="text-primary">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('form.account_name') is-invalid @enderror"
                               id="account_name" 
                               wire:model="form.account_name">
                        @error('form.account_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="account_number" class="form-label fw-medium">
                            Account Number <span class="text-primary">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('form.account_number') is-invalid @enderror"
                               id="account_number" 
                               wire:model="form.account_number">
                        @error('form.account_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="bank_name" class="form-label fw-medium">
                            Bank Name <span class="text-primary">*</span>
                        </label>
                        <select class="form-select @error('form.bank_name') is-invalid @enderror"
                               id="bank_name" 
                               wire:model="form.bank_name">
                            <option value="">Select Bank</option>
                            @foreach($this->banks as $bank)
                                <option value="{{ $bank }}">{{ $bank }}</option>
                            @endforeach
                        </select>
                        @error('form.bank_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="branch_name" class="form-label fw-medium">Branch Name</label>
                        <input type="text" 
                               class="form-control @error('form.branch_name') is-invalid @enderror"
                               id="branch_name" 
                               wire:model="form.branch_name">
                        @error('form.branch_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="account_type" class="form-label fw-medium">Account Type</label>
                        <select class="form-select @error('form.account_type') is-invalid @enderror"
                                id="account_type" 
                                wire:model="form.account_type">
                            <option value="">Select Account Type</option>
                            <option value="savings">Savings</option>
                            <option value="checking">Checking</option>
                            <option value="current">Current</option>
                        </select>
                        @error('form.account_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="opening_balance" class="form-label fw-medium">Opening Balance</label>
                        <input type="number" 
                               step="0.01" 
                               class="form-control @error('form.opening_balance') is-invalid @enderror"
                               id="opening_balance" 
                               wire:model="form.opening_balance">
                        @error('form.opening_balance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="currency" class="form-label fw-medium">Currency</label>
                        <select class="form-select @error('form.currency') is-invalid @enderror"
                                id="currency" 
                                wire:model="form.currency">
                            <option value="ETB">ETB</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                        @error('form.currency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="location_id" class="form-label fw-medium">Location (Branch/Warehouse)</label>
                        <select class="form-select @error('form.location_id') is-invalid @enderror"
                                id="location_id" 
                                wire:model="form.location_id">
                            <option value="">Select Location</option>
                            @foreach($this->locations as $location)
                                <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                            @endforeach
                        </select>
                        @error('form.location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label fw-medium">Description</label>
                        <textarea class="form-control @error('form.description') is-invalid @enderror"
                                  id="description" 
                                  wire:model="form.description" 
                                  rows="3"></textarea>
                        @error('form.description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_active"
                                   wire:model="form.is_active">
                            <label class="form-check-label fw-medium" for="is_active">
                                Active Account
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Card Footer -->
        <div class="card-footer border-top-0 px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.bank-accounts.show', $bankAccount) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </a>
                <button type="submit" 
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="update"
                        form="bankAccountEditForm">
                    <span wire:loading.remove wire:target="update">
                        <i class="bi bi-check-lg me-1"></i>Update Bank Account
                    </span>
                    <span wire:loading wire:target="update">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                        Updating...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>