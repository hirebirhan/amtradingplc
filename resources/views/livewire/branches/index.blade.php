{{-- Clean Branches Management Page --}}
<div>
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Branches</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Manage branch locations, assign warehouses, and control user access by location
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @can('branches.create')
            <a href="{{ route('admin.branches.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">New Branch</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm">
        <!-- Mobile-Optimized Filters -->
        <div class="card-header border-bottom p-3">
            <div class="row g-3">
                <!-- Search -->
                <div class="col-12 col-md-6">
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="Search branches..." wire:model.live.debounce.300ms="search">
                        @if($search)
                            <button class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 text-muted" type="button" wire:click="$set('search', '')">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Desktop Table View -->
            <div class="d-none d-lg-block">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-3">Branch</th>
                                <th>Location</th>

                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branches as $branch)
                            <tr>
                                                <td class="px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle avatar-sm">
                            <i class="bi bi-building"></i>
                        </div>
                        <div>
                            <div class="fw-medium small">{{ $branch->name }}</div>
                            <div class="text-secondary small">{{ $branch->code ?? '' }}</div>
                        </div>
                    </div>
                </td>
                <td><span class="small">{{ $branch->address ?? '-' }}</span></td>

                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.branches.show', $branch) }}" class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-building-x display-6 text-secondary mb-3"></i>
                                        <h6 class="fw-medium">No branches found</h6>
                                        <p class="text-secondary small">Try adjusting your search criteria</p>
                                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" wire:click="clearFilters">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="d-lg-none">
                @forelse($branches as $branch)
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle avatar-sm">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">{{ $branch->name }}</div>
                                    <div class="text-secondary small">{{ $branch->code }}</div>
                                </div>
                            </div>

                        </div>
                        
                        @if($branch->address || $branch->phone || $branch->email)
                            <div class="mb-2">
                                @if($branch->address)
                                    <div class="text-secondary small">
                                        <i class="bi bi-geo-alt me-1"></i>{{ Str::limit($branch->address, 60) }}
                                    </div>
                                @endif
                                @if($branch->phone)
                                    <div class="text-secondary small">
                                        <i class="bi bi-telephone me-1"></i>{{ $branch->phone }}
                                    </div>
                                @endif
                                @if($branch->email)
                                    <div class="text-secondary small">
                                        <i class="bi bi-envelope me-1"></i>{{ $branch->email }}
                                    </div>
                                @endif
                            </div>
                        @endif
                        
                        <div class="d-flex gap-1">
                            @can('branches.view')
                            <a href="{{ route('admin.branches.show', $branch->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                            @endcan
                            @can('branches.edit')
                            <a href="{{ route('admin.branches.edit', $branch->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            @endcan
                            @can('branches.delete')
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                wire:click="delete({{ $branch->id }})">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-building text-secondary fs-1"></i>
                        </div>
                        <h5 class="text-secondary">No branches found</h5>
                        @if($search)
                            <p class="text-secondary">Try adjusting your search criteria</p>
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="$set('search', '');">
                                Clear Filters
                            </button>
                        @else
                            <p class="text-secondary">Start by adding your first branch</p>
                            @can('branches.create')
                            <a href="{{ route('admin.branches.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i> Add First Branch
                            </a>
                            @else
                            <p class="small text-secondary">Contact your administrator for access</p>
                            @endcan
                        @endif
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Pagination -->
        @if($branches->hasPages())
            <div class="d-flex justify-content-center py-3 border-top">
                {{ $branches->links() }}
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-semibold">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <span id="deleteModalTitle">Delete Branch</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="deleteModalMessage" class="mb-3">
                        Are you sure you want to delete this branch? This action cannot be undone.
                    </div>
                    
                    <!-- Transfer Options (shown when branch has stock) -->
                    <div id="transferOptions" class="d-none">
                        <div class="alert alert-warning">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-exclamation-triangle me-2 mt-1"></i>
                                <div>
                                    <strong>Stock Transfer Required</strong>
                                    <p class="mb-2 mt-1">This branch has inventory that needs to be transferred before deletion.</p>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-warning btn-sm" id="showTransferBtn">
                                            <i class="bi bi-arrow-left-right me-1"></i>Transfer Stock
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="bi bi-trash me-1"></i>
                        <span>Delete Branch</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Modal -->
    <div class="modal fade" id="transferModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark border-0">
                    <h5 class="modal-title fw-semibold">
                        <i class="bi bi-arrow-left-right me-2"></i>Transfer Stock Before Deletion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-info-circle me-2 mt-1"></i>
                            <div>
                                <strong>Stock Transfer Required</strong>
                                <p class="mb-0 mt-1">Select a target branch to transfer all inventory before deleting this branch.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="targetBranchSelect" class="form-label fw-medium">Target Branch</label>
                        <select class="form-select" id="targetBranchSelect">
                            <option value="">Select target branch...</option>
                            @foreach($targetBranches ?? [] as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle me-2 mt-1"></i>
                            <div>
                                <strong>Warning</strong>
                                <p class="mb-0 mt-1">This action will transfer all stock to the selected branch and then delete the current branch. This cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-warning" id="confirmTransferBtn">
                        <i class="bi bi-arrow-left-right me-1"></i>
                        <span>Transfer & Delete</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        // Delete confirmation modal
        const deleteModal = document.getElementById('deleteConfirmationModal');
        const transferModal = document.getElementById('transferModal');
        
        if (deleteModal) {
            const modal = new bootstrap.Modal(deleteModal);
            
            // Listen for Livewire events to show modal
            Livewire.on('showDeleteConfirmation', (data) => {
                const title = document.getElementById('deleteModalTitle');
                const message = document.getElementById('deleteModalMessage');
                const transferOptions = document.getElementById('transferOptions');
                const confirmBtn = document.getElementById('confirmDeleteBtn');
                
                title.textContent = data.title;
                message.textContent = data.message;
                
                if (data.hasStock) {
                    transferOptions.classList.remove('d-none');
                    confirmBtn.style.display = 'none';
                } else {
                    transferOptions.classList.add('d-none');
                    confirmBtn.style.display = 'block';
                }
                
                modal.show();
            });
            
            // Handle confirm delete button
            document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
                Livewire.dispatch('deleteBranch');
                modal.hide();
            });
            
            // Handle show transfer button
            document.getElementById('showTransferBtn').addEventListener('click', () => {
                modal.hide();
                Livewire.dispatch('showTransferOptions');
            });
            
            // Listen for successful deletion
            Livewire.on('branchDeleted', () => {
                modal.hide();
            });
        }
        
        // Transfer modal
        if (transferModal) {
            const modal = new bootstrap.Modal(transferModal);
            
            // Listen for Livewire events to show transfer modal
            Livewire.on('showTransferModal', () => {
                modal.show();
            });
            
            // Handle confirm transfer button
            document.getElementById('confirmTransferBtn').addEventListener('click', () => {
                const targetBranchId = document.getElementById('targetBranchSelect').value;
                if (!targetBranchId) {
                    Livewire.dispatch('notify', [{ type: 'error', message: 'Please select a target branch' }]);
                    return;
                }
                Livewire.dispatch('transferAndDelete', { targetBranchId: targetBranchId });
            });
            
            // Listen for successful transfer and deletion
            Livewire.on('hideTransferModal', () => {
                modal.hide();
            });
        }
        
        // Listen for Livewire notifications
        Livewire.on('notify', (data) => {
            const notification = document.createElement('div');
            notification.className = `alert alert-${data[0].type === 'error' ? 'danger' : data[0].type} alert-dismissible fade show shadow-sm rounded-3 position-fixed top-0 end-0 m-3 z-3`;
            notification.innerHTML = `
                ${data[0].message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                notification.remove();
            }, 5000);
        });
    });
</script>
@endpush