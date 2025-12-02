<div>
    <div class="card border-0 shadow-sm">
        <div class="card-header border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-0">Create New Supplier</h4>
                    <span class="text-secondary small">Add a new supplier to your network with contact information</span>
                </div>
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Suppliers
                </a>
            </div>
        </div>

        <div class="card-body">
            <form wire:submit.prevent="create">
                <div class="row g-3">
                    <!-- Supplier Name -->
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

                    <!-- Company Name -->
                    <div class="col-md-6">
                        <label for="company" class="form-label fw-medium">Company Name</label>
                        <input type="text" 
                               class="form-control @error('form.company') is-invalid @enderror" 
                               id="company" 
                               wire:model="form.company"
                               placeholder="Enter company name">
                        @error('form.company')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-medium">Email</label>
                        <input type="email" 
                               class="form-control @error('form.email') is-invalid @enderror" 
                               id="email" 
                               wire:model="form.email"
                               placeholder="supplier@email.com">
                        @error('form.email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Phone Number -->
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-medium">Phone Number</label>
                        <input type="tel" 
                               class="form-control @error('form.phone') is-invalid @enderror" 
                               id="phone" 
                               wire:model="form.phone"
                               placeholder="+251912345678">
                        @error('form.phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Branch -->
                    <div class="col-md-6">
                        <label for="branch_id" class="form-label fw-medium">Branch</label>
                        <select class="form-select @error('form.branch_id') is-invalid @enderror" 
                                id="branch_id" 
                                wire:model="form.branch_id">
                            <option value="">Select branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('form.branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Address -->
                    <div class="col-md-6">
                        <label for="address" class="form-label fw-medium">Address</label>
                        <textarea class="form-control @error('form.address') is-invalid @enderror" 
                                  id="address" 
                                  wire:model="form.address"
                                  rows="2"
                                  placeholder="Enter address"></textarea>
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
                                  placeholder="Additional notes"></textarea>
                        @error('form.notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text small text-muted">Fill in the required fields to create a new supplier</div>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-footer">
            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </a>
                <button type="submit" 
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="create"
                        wire:click="create">
                    <span wire:loading.remove wire:target="create">
                        <i class="bi bi-check-lg me-1"></i>Create Supplier
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