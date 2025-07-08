<div>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Expense Type Details</h5>
                <div class="btn-group">
                    <a href="{{ route('admin.settings.expense-types') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                    <a href="{{ route('admin.settings.expense-types.edit', $expenseType) }}" class="btn btn-sm btn-info">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                    <button wire:click="$dispatch('openDeleteModal')" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
            
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="text-uppercase text-muted mb-2">Basic Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block">Name</small>
                                <span class="fw-medium">{{ $expenseType->name }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block">Status</small>
                                <span class="badge bg-{{ $expenseType->is_active ? 'success' : 'danger' }}">
                                    {{ $expenseType->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block">Branch</small>
                                <span class="fw-medium">{{ $expenseType->branch ? $expenseType->branch->name : 'All Branches' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block">Created By</small>
                                <span class="fw-medium">{{ $expenseType->creator ? $expenseType->creator->name : 'Unknown' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block">Created At</small>
                                <span class="fw-medium">{{ $expenseType->created_at->format('M d, Y H:i A') }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block">Last Updated</small>
                                <span class="fw-medium">{{ $expenseType->updated_at->format('M d, Y H:i A') }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 border rounded bg-light">
                                <small class="text-muted d-block">Description</small>
                                <span class="fw-medium">{{ $expenseType->description ?: 'No description provided' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                @if($expenseType->expenses->count() > 0)
                <div class="mb-4">
                    <h6 class="text-uppercase text-muted mb-2">Associated Expenses</h6>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This expense type is being used by {{ $expenseType->expenses->count() }} expense(s) and cannot be deleted.
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Expense Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the expense type <strong>"{{ $expenseType->name }}"</strong>?</p>
                    <p class="text-danger">This action cannot be undone if the expense type is in use.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="delete">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    @script
    let deleteModal;
    
    document.addEventListener('livewire:initialized', function() {
        // Initialize modal
        const deleteModalEl = document.getElementById('deleteModal');
        if(deleteModalEl) deleteModal = new bootstrap.Modal(deleteModalEl);
        
        // Show delete modal when dispatched
        $wire.on('openDeleteModal', () => {
            if(deleteModal) deleteModal.show();
        });
    });
    @endscript
</div> 