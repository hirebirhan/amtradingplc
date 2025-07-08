<div>
    <!-- Main Card Container -->
    <div class="card border-0 shadow-sm">
        <!-- Header -->
        <div class="card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Edit Branch</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary mb-0 small">{{ $branch->name }}</span>
                    </div>
                </div>
                <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Back to Branches</span>
                </a>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body p-0">
            <!-- Form Content -->
            <form id="branchEditForm" wire:submit.prevent="save" class="p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-medium">
                            Branch Name <span class="text-primary">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror"
                               id="name" 
                               wire:model="name" 
                               placeholder="Enter branch name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label fw-medium">Email Address</label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror"
                               id="email" 
                               wire:model="email" 
                               placeholder="Enter email address">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-medium">Phone Number</label>
                        <input type="text" 
                               class="form-control @error('phone') is-invalid @enderror"
                               id="phone" 
                               wire:model="phone" 
                               placeholder="Enter phone number">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="address" class="form-label fw-medium">
                            Address <span class="text-primary">*</span>
                        </label>
                        <textarea class="form-control @error('address') is-invalid @enderror"
                                  id="address" 
                                  wire:model="address" 
                                  rows="3" 
                                  placeholder="Enter branch address"></textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="isActive" 
                               wire:model="isActive">
                        <label class="form-check-label fw-medium" for="isActive">
                            Active
                        </label>
                    </div>
                </div>
            </form>
        </div>

        <!-- Card Footer -->
        <div class="card-footer border-top-0 px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary" wire:click="$refresh">
                    <i class="bi bi-arrow-clockwise me-1"></i>Reset
                </button>
                <button type="submit" 
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        form="branchEditForm">
                    <span wire:loading.remove wire:target="save">
                        <i class="bi bi-check-lg me-1"></i>Update Branch
                    </span>
                    <span wire:loading wire:target="save">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                        Updating...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>