<div>
    <x-partials.main title="Create Role">
        <div class="card-header p-2 p-md-4 border-bottom">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-primary bg-opacity-10 p-2 me-2 d-flex align-items-center justify-content-center d-none d-md-flex" style="width: 42px; height: 42px">
                        <i class="bi bi-shield-plus text-primary"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1 h6">Add New Role</h5>
                        <p class="text-muted small mb-0 d-none d-md-block">Define a new user role and assign specific permissions</p>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="cancel">
                    <i class="bi bi-arrow-left me-1"></i> 
                    <span class="d-none d-sm-inline">Back to List</span>
                    <span class="d-sm-none">Back</span>
                </button>
            </div>
        </div>

        <div class="card-body p-2 p-md-4">
            <!-- Success/Error Messages -->
            @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form wire:submit.prevent="store">
                <!-- Role Details Section -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-semibold">
                            Role Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="name" class="form-control @error('name') is-invalid @enderror" 
                               wire:model.live="name" placeholder="Enter role name (e.g., Sales Manager)" 
                               required autofocus {{ $isSubmitting ? 'disabled' : '' }}>
                        @error('name') 
                            <div class="invalid-feedback d-block">{{ $message }}</div> 
                        @enderror
                        <div class="form-text">Choose a descriptive name for the role (letters, numbers, spaces, hyphens, underscores only)</div>
                    </div>
                    <div class="col-md-6">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea id="description" class="form-control @error('description') is-invalid @enderror" 
                                  wire:model.live="description" rows="2" 
                                  placeholder="Brief description of the role's purpose" {{ $isSubmitting ? 'disabled' : '' }}></textarea>
                        @error('description') 
                            <div class="invalid-feedback d-block">{{ $message }}</div> 
                        @enderror
                        <div class="form-text">Optional description to help identify the role</div>
                    </div>
                </div>

                <!-- Permissions Section -->
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <label class="form-label fw-semibold mb-0">
                            <i class="bi bi-key me-2 text-primary"></i>
                            Permissions <span class="text-danger">*</span>
                        </label>
                        <span class="badge bg-primary-subtle text-primary-emphasis">
                            {{ count($selectedPermissions) }} Selected
                        </span>
                    </div>
                    
                    @error('selectedPermissions') 
                        <div class="alert alert-danger py-2 mb-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ $message }}
                        </div> 
                    @enderror

                    <div class="row g-3">
                        @forelse($allPermissions as $group)
                            <div class="col-lg-6">
                                <div class="card h-100 border">
                                    <div class="card-header bg-muted border-bottom py-2">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-{{ $this->getPermissionIcon($group['group']) }} me-2 text-primary"></i>
                                                <strong class="text-capitalize">{{ $group['group'] }}</strong>
                                            </div>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                                {{ count($group['permissions']) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2">
                                            @foreach($group['permissions'] as $permission)
                                                <div class="col-12">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="perm_{{ $permission['id'] }}" 
                                                               value="{{ $permission['name'] }}" 
                                                               wire:model.live="selectedPermissions"
                                                               {{ $isSubmitting ? 'disabled' : '' }}>
                                                        <label class="form-check-label small" for="perm_{{ $permission['id'] }}">
                                                            {{ $permission['display_name'] }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    No permissions available. Please contact your administrator.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-3 pt-3 border-top">
                    <button type="button" class="btn btn-outline-secondary" wire:click="cancel" {{ $isSubmitting ? 'disabled' : '' }}>
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary px-4" {{ $isSubmitting ? 'disabled' : '' }}>
                        @if($isSubmitting)
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Creating...
                        @else
                            <i class="bi bi-save me-1"></i> Create Role
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </x-partials.main>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="confirmationModalBody">
                    Are you sure you want to proceed?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="confirmationModalCancel">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmationModalConfirm">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        // Handle confirmation dialogs
        document.addEventListener('livewire:init', () => {
            Livewire.on('show-confirmation', (data) => {
                const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
                document.getElementById('confirmationModalTitle').textContent = data.title;
                document.getElementById('confirmationModalBody').textContent = data.message;
                document.getElementById('confirmationModalCancel').textContent = data.cancelText;
                document.getElementById('confirmationModalConfirm').textContent = data.confirmText;
                
                document.getElementById('confirmationModalConfirm').onclick = () => {
                    modal.hide();
                    if (data.action === 'cancel-role-creation') {
                        @this.confirmCancel();
                    }
                };
                
                modal.show();
            });
        });
    </script>
    @endscript
</div> 