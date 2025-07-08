<div>
    <!-- Main Card Container -->
    <div class="card border-0 shadow-sm">
        <!-- Header -->
        <div class="card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Edit Warehouse</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary mb-0 small">{{ $warehouse->name }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.warehouses.show', $warehouse->id) }}" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-eye me-1"></i>View Details
                    </a>
                    <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body p-0">
            <!-- Form Content -->
            <form id="warehouseEditForm" wire:submit="update" class="p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-medium">
                            Name <span class="text-primary">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               class="form-control @error('name') is-invalid @enderror"
                               wire:model="name" 
                               autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="code" class="form-label fw-medium">Code</label>
                        <input type="text" 
                               id="code" 
                               class="form-control" 
                               value="{{ $code }}" 
                               readonly>
                        <div class="form-text small text-muted">Code cannot be changed after creation</div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label for="address" class="form-label fw-medium">Address</label>
                        <textarea id="address" 
                                  class="form-control @error('address') is-invalid @enderror"
                                  wire:model="address" 
                                  rows="2"></textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="manager_name" class="form-label fw-medium">Manager Name</label>
                        <input type="text" 
                               id="manager_name" 
                               class="form-control @error('manager_name') is-invalid @enderror"
                               wire:model="manager_name">
                        @error('manager_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-medium">Phone</label>
                        <input type="tel" 
                               id="phone" 
                               class="form-control @error('phone') is-invalid @enderror"
                               wire:model="phone" 
                               placeholder="e.g. +1234567890" 
                               pattern="^\+?[0-9\s\-\(\)]+$">
                        <div class="form-text small">Enter a valid phone number (e.g., +1234567890)</div>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="branch_ids" class="form-label fw-medium">Branches</label>
                        <select id="branch_ids" 
                                class="form-select @error('branch_ids') is-invalid @enderror"
                                wire:model="branch_ids" 
                                multiple 
                                size="5">
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text small">Hold Ctrl/Cmd to select multiple branches</div>
                        @error('branch_ids')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </form>
        </div>

        <!-- Card Footer -->
        <div class="card-footer border-top-0 px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </a>
                <button type="submit" 
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="update"
                        form="warehouseEditForm">
                    <span wire:loading.remove wire:target="update">
                        <i class="bi bi-check-lg me-1"></i>Update Warehouse
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