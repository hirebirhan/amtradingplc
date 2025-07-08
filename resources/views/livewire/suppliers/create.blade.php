<div>
    <!-- Main Card Container -->
    <div class="card border-0 shadow-sm">
        <!-- Header -->
        <div class="card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Create New Supplier</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary mb-0 small">Add a new supplier to manage your inventory purchases and vendor relationships</span>
                    </div>
                </div>
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Back to Suppliers</span>
                </a>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body p-0">
            <!-- Form Content -->
            <form id="supplierForm" wire:submit.prevent="create" class="p-4">
                <div class="row g-3">
                    <!-- Name -->
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-medium">
                            Supplier Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('form.name') is-invalid @enderror" 
                               id="name" 
                               wire:model="form.name"
                               placeholder="Enter supplier name">
                        @error('form.name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-medium">
                            Phone Number <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('form.phone') is-invalid @enderror" 
                               id="phone" 
                               wire:model="form.phone"
                               placeholder="+251 912 345 678">
                        @error('form.phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-medium">Email Address</label>
                        <input type="email" 
                               class="form-control @error('form.email') is-invalid @enderror" 
                               id="email" 
                               wire:model="form.email"
                               placeholder="supplier@email.com">
                        @error('form.email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Reference Number -->
                    <div class="col-md-6">
                        <label for="reference_no" class="form-label fw-medium">Reference Number</label>
                        <input type="text" 
                               class="form-control @error('form.reference_no') is-invalid @enderror" 
                               id="reference_no" 
                               wire:model="form.reference_no"
                               placeholder="SUP-001">
                        @error('form.reference_no')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Address -->
                    <div class="col-12">
                        <label for="address" class="form-label fw-medium">
                            Address <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control @error('form.address') is-invalid @enderror" 
                                  id="address" 
                                  wire:model="form.address"
                                  rows="2"
                                  placeholder="Enter complete address"></textarea>
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
                                  placeholder="Additional notes about the supplier"></textarea>
                        @error('form.notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

            </form>
        </div>

        <!-- Card Footer -->
        <div class="card-footer border-top-0 px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </a>
                <button type="submit" 
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="create"
                        form="supplierForm">
                    <span wire:loading.remove wire:target="create">
                        <i class="bi bi-check-lg me-1"></i>Create Supplier
                    </span>
                    <span wire:loading wire:target="create">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                        Creating...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>