<div>
    <!-- Main Card Container -->
    <div class="card border-0 shadow-sm">
        <!-- Header -->
        <div class="card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Create New Bank Account</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary mb-0 small">Add a new bank account for transactions</span>
                    </div>
                </div>
                <a href="{{ route('admin.bank-accounts.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Back to Bank Accounts</span>
                </a>
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
            <form id="bankAccountForm" wire:submit="save" class="p-4 pt-0">
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
                        <label for="location_id" class="form-label fw-medium">Location (Branch/Warehouse)</label>
                        <select class="form-select @error('form.location_id') is-invalid @enderror"
                                id="location_id" 
                                wire:model="form.location_id">
                            <option value="">Select Location</option>
                            @php
                                $locations = $this->locations;
                            @endphp
                            
                            @if(empty($locations))
                                <option value="" disabled>No locations available</option>
                            @else
                                @foreach($locations as $location)
                                    <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                                @endforeach
                            @endif
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
                </div>
            </form>
        </div>

        <!-- Card Footer -->
        <div class="card-footer border-top-0 px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.bank-accounts.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </a>
                <button type="submit" 
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        form="bankAccountForm">
                    <span wire:loading.remove wire:target="save">
                        <i class="bi bi-check-lg me-1"></i>Create Bank Account
                    </span>
                    <span wire:loading wire:target="save">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                        Creating...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>