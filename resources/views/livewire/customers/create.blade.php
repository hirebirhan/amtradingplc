<div>
    <div class="card border-0 shadow-sm">
        <div class="card-header border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="fw-bold mb-0">Create New Customer</h4>
                <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="card-body">
            <form wire:submit.prevent="create">
                <div class="row g-3">
                    <!-- Name -->
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-medium">
                            Customer Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('form.name') is-invalid @enderror" 
                               id="name" 
                               wire:model="form.name"
                               placeholder="Enter customer name">
                        @error('form.name')
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
                               placeholder="customer@email.com">
                        @error('form.email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-medium">
                            Phone Number <span class="text-danger">*</span>
                        </label>
                        <input type="tel" 
                               class="form-control @error('form.phone') is-invalid @enderror" 
                               id="phone" 
                               wire:model="form.phone"
                               placeholder="+251912345678">
                        @error('form.phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div class="col-md-6">
                        <label for="notes" class="form-label fw-medium">Notes</label>
                        <textarea class="form-control @error('form.notes') is-invalid @enderror" 
                                  id="notes" 
                                  wire:model="form.notes"
                                  rows="3"
                                  placeholder="Additional notes"></textarea>
                        @error('form.notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </form>
        </div>

        <div class="card-footer">
            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </a>
                <button type="submit" 
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="create"
                        wire:click="create">
                    <span wire:loading.remove wire:target="create">
                        <i class="bi bi-check-lg me-1"></i>Create Customer
                    </span>
                    <span wire:loading wire:target="create">
                        <span class="spinner-border spinner-border-sm me-1"></span>
                        Creating...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>