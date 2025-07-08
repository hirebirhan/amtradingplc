<div>
    @script
    <script>
        $wire.on('notify', (event) => {
            const notification = event[0] || event;
            
            // Create notification element
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${notification.type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 1060; min-width: 300px;';
            alertDiv.innerHTML = `
                <strong>${notification.title || 'Notification'}</strong> ${notification.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Add to body
            document.body.appendChild(alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        });
    </script>
    @endscript

    <!-- Main Card Container -->
    <div class="card border-0 shadow-sm">
        <!-- Header -->
        <div class="card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Create New Warehouse</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary mb-0 small">Add a new warehouse location</span>
                    </div>
                </div>
                <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Back to Warehouses</span>
                </a>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body p-0">
            <!-- Form Content -->
            <form id="warehouseForm" wire:submit="save" class="p-4">
                <!-- Form Fields -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label fw-medium">
                            Warehouse Name <span class="text-primary">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               class="form-control @error('name') is-invalid @enderror"
                               wire:model="name" 
                               placeholder="Enter warehouse name" 
                               autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">A unique code will be automatically generated</div>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="manager_name" class="form-label fw-medium">Manager Name</label>
                        <input type="text" 
                               id="manager_name" 
                               class="form-control @error('manager_name') is-invalid @enderror"
                               wire:model="manager_name" 
                               placeholder="Manager name">
                        @error('manager_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="phone" class="form-label fw-medium">Phone</label>
                        <input type="tel" 
                               id="phone" 
                               class="form-control @error('phone') is-invalid @enderror"
                               wire:model="phone" 
                               placeholder="+251912345678">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="address" class="form-label fw-medium">Address</label>
                        <textarea id="address" 
                                  class="form-control @error('address') is-invalid @enderror"
                                  wire:model="address" 
                                  rows="2" 
                                  placeholder="Enter warehouse address"></textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Branch Selection -->
                <div class="mb-4">
                    <label for="branch_ids" class="form-label fw-medium">Branches</label>
                    <select id="branch_ids" 
                            class="form-select @error('branch_ids') is-invalid @enderror"
                            wire:model="branch_ids" 
                            multiple 
                            size="4">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Select one or more branches (hold Ctrl/Cmd for multiple)</div>
                    @error('branch_ids')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
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
                        wire:target="save"
                        form="warehouseForm">
                    <span wire:loading.remove wire:target="save">
                        <i class="bi bi-check-lg me-1"></i>Create Warehouse
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