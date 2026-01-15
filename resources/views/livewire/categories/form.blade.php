<div>
    <form id="categoryForm" wire:submit.prevent="save" class="needs-validation">
        <div class="card-body p-0">
            <!-- Success/Error Messages -->


            <!-- Form Content -->
            <div class="p-4 pt-0">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-medium">
                            Category Name <span class="text-primary">*</span>
                        </label>
                        <input
                            type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            id="name"
                            wire:model.live="name"
                            placeholder="e.g. Electronics"
                            required autofocus
                            {{ $isSubmitting ? 'disabled' : '' }}
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="parent_id" class="form-label fw-medium">Parent Category</label>
                        <select class="form-select @error('parent_id') is-invalid @enderror" 
                                id="parent_id" 
                                wire:model.live="parent_id"
                                {{ $isSubmitting ? 'disabled' : '' }}>
                            <option value="">-- None (Top Level) --</option>
                            @foreach($parentCategories as $parentCategory)
                                <option value="{{ $parentCategory['id'] }}">{{ $parentCategory['name'] }}</option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label fw-medium">Description</label>
                        <textarea
                            class="form-control @error('description') is-invalid @enderror"
                            id="description"
                            wire:model.live="description"
                            rows="3"
                            placeholder="Describe this category..."
                            {{ $isSubmitting ? 'disabled' : '' }}
                        ></textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer border-top-0 px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary" wire:click="cancel" {{ $isSubmitting ? 'disabled' : '' }}>
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                        <button type="submit" 
                                class="btn btn-primary"
                                {{ $isSubmitting ? 'disabled' : '' }}
                                wire:loading.attr="disabled"
                                wire:target="save">
                            <span wire:loading.remove wire:target="save">
                                @if($isSubmitting)
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    {{ $isEdit ? 'Updating...' : 'Creating...' }}
                                @else
                                    <i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Update' : 'Create' }} Category
                                @endif
                            </span>
                            <span wire:loading wire:target="save">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                {{ $isEdit ? 'Updating...' : 'Creating...' }}
                            </span>
                        </button>
            </div>
        </div>
    </form>

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
                    if (data.action === 'cancel-category-creation') {
                        @this.confirmCancel();
                    }
                };
                
                modal.show();
            });
            
            // Handle form submission feedback
            Livewire.on('category-saved', () => {
                // Optional: Show a toast notification or other feedback
                console.log('Category saved successfully');
            });
        });
    </script>
    @endscript
</div>
