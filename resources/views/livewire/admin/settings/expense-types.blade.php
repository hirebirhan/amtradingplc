<div>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Expense Types</h5>
                <a href="{{ route('admin.settings.expense-types.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i> Add New Type
                </a>
            </div>
            
            <div class="card-body">
                <!-- Search and filters -->
                <div class="p-4 border-bottom">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.300ms="searchQuery" 
                                    class="form-control" 
                                    placeholder="Search expense types..."
                                >
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select wire:model="branch_id" class="form-select">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select wire:model="is_active" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button wire:click="clearFilters" class="btn btn-outline-secondary">Clear Filters</button>
                        </div>
                    </div>
                </div>
                
                <!-- Expense Types Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3 fw-semibold">Name</th>
                                <th class="px-4 py-3 fw-semibold">Description</th>
                                <th class="px-4 py-3 fw-semibold">Branch</th>
                                <th class="px-4 py-3 fw-semibold">Status</th>
                                <th class="px-4 py-3 fw-semibold">Created By</th>
                                <th class="px-4 py-3 fw-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenseTypes as $expenseType)
                                <tr>
                                    <td class="px-4 py-3 fw-medium">
                                        <a href="{{ route('admin.settings.expense-types.show', $expenseType) }}" class="text-decoration-none fw-medium">
                                            {{ $expenseType->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 fw-medium">
                                        <div class="text-wrap" style="max-width: 250px;">
                                            {{ $expenseType->description ?? 'No description' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 fw-medium">{{ $expenseType->branch ? $expenseType->branch->name : 'All Branches' }}</td>
                                    <td class="px-4 py-3 fw-medium">
                                        <span class="fw-medium text-{{ $expenseType->is_active ? 'success' : 'danger' }}">
                                            {{ $expenseType->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 fw-medium">{{ $expenseType->creator ? $expenseType->creator->name : 'Unknown' }}</td>
                                    <td class="px-4 py-3 fw-medium">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.settings.expense-types.show', $expenseType) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.settings.expense-types.edit', $expenseType) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button wire:click="confirmDelete({{ $expenseType->id }})" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-folder-open fa-2x mb-2"></i>
                                            <p>No expense types found</p>
                                            <a href="{{ route('admin.settings.expense-types.create') }}" class="btn btn-sm btn-outline-primary mt-2">
                                                <i class="fas fa-plus me-1"></i> Create New Type
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted small">
                        Showing {{ $expenseTypes->firstItem() ?? 0 }} to {{ $expenseTypes->lastItem() ?? 0 }} of {{ $expenseTypes->total() }} expense types
                    </div>
                    <div>
                        {{ $expenseTypes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Modal -->
    <div class="modal fade" id="createExpenseTypeModal" tabindex="-1" wire:ignore.self data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Expense Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="cancel"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="store">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" wire:model="name" id="name" class="form-control @error('name') is-invalid @enderror" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea wire:model="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3"></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="branch_id" class="form-label">Branch</label>
                            <select wire:model="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" wire:model="is_active" id="is_active" class="form-check-input">
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="cancel">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="store">Create</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal fade" id="editExpenseTypeModal" tabindex="-1" wire:ignore.self data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Expense Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="cancel"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="update">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" wire:model="name" id="edit_name" class="form-control @error('name') is-invalid @enderror" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea wire:model="description" id="edit_description" class="form-control @error('description') is-invalid @enderror" rows="3"></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_branch_id" class="form-label">Branch</label>
                            <select wire:model="branch_id" id="edit_branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" wire:model="is_active" id="edit_is_active" class="form-check-input">
                            <label class="form-check-label" for="edit_is_active">Active</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="cancel">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="update">Update</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div class="modal fade" id="deleteExpenseTypeModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Expense Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="cancel"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the expense type <strong>"{{ $name }}"</strong>?</p>
                    <p class="text-danger">This action cannot be undone if the expense type is in use.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="delete">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    @script
    // Initialize modal instances when the component is initialized
    let createModal, editModal, deleteModal;
    
    // Set up modals when Livewire initializes
    document.addEventListener('livewire:initialized', function() {
        setupModals();
    });
    
    // Set up once DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        setupModals();
    });
    
    function setupModals() {
        // Initialize Bootstrap modal instances
        const createModalEl = document.getElementById('createExpenseTypeModal');
        const editModalEl = document.getElementById('editExpenseTypeModal');
        const deleteModalEl = document.getElementById('deleteExpenseTypeModal');
        
        if(createModalEl) createModal = new bootstrap.Modal(createModalEl);
        if(editModalEl) editModal = new bootstrap.Modal(editModalEl);
        if(deleteModalEl) deleteModal = new bootstrap.Modal(deleteModalEl);
        
        // Handle toastr alerts from Livewire
        $wire.on('alert', ({ type, message }) => {
            if (typeof toastr !== 'undefined') {
                toastr[type](message);
            } else {
                alert(message);
            }
        });
    }
    
    // Modal event listeners
    $wire.on('showCreateModal', () => {
        if(createModal) createModal.show();
    });
    
    $wire.on('hideCreateModal', () => {
        if(createModal) createModal.hide();
    });
    
    $wire.on('showEditModal', () => {
        if(editModal) editModal.show();
    });
    
    $wire.on('hideEditModal', () => {
        if(editModal) editModal.hide();
    });
    
    $wire.on('showDeleteModal', () => {
        if(deleteModal) deleteModal.show();
    });
    
    $wire.on('hideDeleteModal', () => {
        if(deleteModal) deleteModal.hide();
    });
    @endscript
</div>
