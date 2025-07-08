<div>
    <!-- Main Card Container -->
    <div class="card border-0 shadow-sm">
        <!-- Header -->
        <div class="card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Edit Customer</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary mb-0 small">{{ $customer->name }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary btn-sm">
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
            <form id="customerEditForm" wire:submit.prevent="update" class="p-4 pt-0">
                <div class="row g-3">
                    <!-- Name -->
                    <div class="col-md-4">
                        <label for="name" class="form-label fw-medium">
                            Customer Name <span class="text-primary">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('form.name') is-invalid @enderror" 
                               id="name"
                               wire:model="form.name"
                               placeholder="Customer name">
                        @error('form.name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Customer Type -->
                    <div class="col-md-3">
                        <label for="customer_type" class="form-label fw-medium">
                            Customer Type <span class="text-primary">*</span>
                        </label>
                        <select class="form-select @error('form.customer_type') is-invalid @enderror" 
                                id="customer_type"
                                wire:model="form.customer_type">
                            <option value="retail">Retail</option>
                            <option value="wholesale">Wholesale</option>
                            <option value="distributor">Distributor</option>
                        </select>
                        @error('form.customer_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="col-md-5">
                        <label for="email" class="form-label fw-medium">Email Address</label>
                        <input type="email" 
                               class="form-control @error('form.email') is-invalid @enderror" 
                               id="email"
                               wire:model="form.email"
                               placeholder="customer@email.com">
                        @error('form.email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div class="col-md-4">
                        <label for="phone" class="form-label fw-medium">Phone Number</label>
                        <input type="text" 
                               class="form-control @error('form.phone') is-invalid @enderror" 
                               id="phone"
                               wire:model="form.phone"
                               placeholder="+251 912 345 678">
                        @error('form.phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- City -->
                    <div class="col-md-4">
                        <label for="city" class="form-label fw-medium">City</label>
                        <input type="text" 
                               class="form-control @error('form.city') is-invalid @enderror" 
                               id="city"
                               wire:model="form.city"
                               placeholder="Addis Ababa">
                        @error('form.city')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Credit Limit -->
                    <div class="col-md-4">
                        <label for="credit_limit" class="form-label fw-medium">Credit Limit (ETB)</label>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               max="999999.99"
                               class="form-control @error('form.credit_limit') is-invalid @enderror" 
                               id="credit_limit"
                               wire:model="form.credit_limit"
                               placeholder="0.00">
                        @error('form.credit_limit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Address -->
                    <div class="col-12">
                        <label for="address" class="form-label fw-medium">Address</label>
                        <input type="text" 
                               class="form-control @error('form.address') is-invalid @enderror" 
                               id="address"
                               wire:model="form.address"
                               placeholder="Enter full address">
                        @error('form.address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div class="col-12">
                        <label for="notes" class="form-label fw-medium">Notes</label>
                        <textarea class="form-control @error('form.notes') is-invalid @enderror" 
                                  id="notes"
                                  wire:model="form.notes"
                                  rows="2"
                                  placeholder="Additional notes about the customer..."></textarea>
                        @error('form.notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text small">{{ strlen($form['notes'] ?? '') }}/1000 characters</div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Card Footer -->
        <div class="card-footer border-top-0 px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </a>
                <button type="submit" 
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="update"
                        form="customerEditForm">
                    <span wire:loading.remove wire:target="update">
                        <i class="bi bi-check-lg me-1"></i>Update Customer
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