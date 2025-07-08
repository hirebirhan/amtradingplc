<div>
    <!-- Main Card Container -->
    <div class="card border-0 shadow-sm">
        <!-- Header -->
        <div class="card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Edit Supplier</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary mb-0 small">{{ $supplier->name }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.suppliers.show', $supplier) }}" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body p-0">
            <!-- Form Content -->
            <form id="supplierEditForm" wire:submit.prevent="update" class="p-4">
                <div class="row g-3">
                    <!-- Essential Information Row 1 -->
                    <div class="col-md-4">
                        <label for="name" class="form-label fw-medium">
                            Name <span class="text-primary">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('form.name') is-invalid @enderror" 
                               id="name" 
                               wire:model="form.name">
                        @error('form.name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="company" class="form-label fw-medium">Company</label>
                        <input type="text" 
                               class="form-control @error('form.company') is-invalid @enderror" 
                               id="company" 
                               wire:model="form.company">
                        @error('form.company')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="phone" class="form-label fw-medium">Phone</label>
                        <input type="text" 
                               class="form-control @error('form.phone') is-invalid @enderror" 
                               id="phone" 
                               wire:model="form.phone">
                        @error('form.phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Contact & Tax Information Row 2 -->
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-medium">Email</label>
                        <input type="email" 
                               class="form-control @error('form.email') is-invalid @enderror" 
                               id="email" 
                               wire:model="form.email">
                        @error('form.email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="tax_number" class="form-label fw-medium">Tax Number</label>
                        <input type="text" 
                               class="form-control @error('form.tax_number') is-invalid @enderror" 
                               id="tax_number" 
                               wire:model="form.tax_number">
                        @error('form.tax_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Address & Branch Row 3 -->
                    <div class="col-md-9">
                        <label for="address" class="form-label fw-medium">Address</label>
                        <input type="text" 
                               class="form-control @error('form.address') is-invalid @enderror" 
                               id="address" 
                               wire:model="form.address">
                        @error('form.address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="branch_id" class="form-label fw-medium">Branch</label>
                        <select class="form-select @error('form.branch_id') is-invalid @enderror" 
                                id="branch_id" 
                                wire:model="form.branch_id">
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('form.branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Notes Row 5 -->
                    <div class="col-12">
                        <label for="notes" class="form-label fw-medium">Notes</label>
                        <textarea class="form-control @error('form.notes') is-invalid @enderror" 
                                  id="notes" 
                                  rows="2" 
                                  wire:model="form.notes" 
                                  placeholder="Additional notes or comments..."></textarea>
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
                        wire:target="update"
                        form="supplierEditForm">
                    <span wire:loading.remove wire:target="update">
                        <i class="bi bi-check-lg me-1"></i>Update Supplier
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