{{-- Clean Categories Management Page --}}
<div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Categories</h4>
            </div>
            <p class="text-secondary mb-0 small">
                Manage product categories and subcategories
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @can('categories.create')
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">New Category</span>
            </a>
            @endcan
        </div>
    </div>

    @can('categories.view')
    <!-- Main Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Filters -->
            <div class="p-4 border-bottom">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Search categories...">
                            @if($search)
                                <button class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2" type="button" wire:click="$set('search', '')" style="background: none; border: none;">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <select wire:model.live="parentFilter" class="form-select">
                            <option value="">All Types</option>
                            <option value="parent">Parent Only</option>
                            <option value="child">Child Only</option>
                        </select>
                    </div>
                    
                </div>
                @if($search || $parentFilter || $perPage != 10)
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearFilters">
                        <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                    </button>
                </div>
                @endif
            </div>

            <!-- Table Section -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3 cursor-pointer fw-semibold text-dark" wire:click="sortBy('name')">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Category</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-3 py-3 fw-semibold text-dark">Description</th>
                            <th class="px-3 py-3 text-center cursor-pointer fw-semibold text-dark" wire:click="sortBy('items_count')">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span>Items</span>
                                    <i class="bi bi-arrow-down-up text-secondary"></i>
                                </div>
                            </th>
                            <th class="px-4 py-3 text-end fw-semibold text-dark pe-5">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="fw-medium">{{ $category->name }}{{ $category->parent ? ' (' . $category->parent->name . ')' : '' }}</span>
                            </td>
                            <td class="px-3 py-3">{{ Str::limit($category->description, 50) }}</td>
                            <td class="px-3 py-3 text-center">
                                @if($category->items_count == 0)
                                    <span class="text-danger fw-medium">{{ $category->items_count }}</span>
                                @elseif($category->items_count <= 5)
                                    <span class="text-warning fw-medium">{{ $category->items_count }}</span>
                                @else
                                    <span class="text-success fw-medium">{{ $category->items_count }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end pe-5">
                                <div class="btn-group btn-group-sm">
                                    @can('categories.edit')
                                    <a href="{{ route('admin.categories.edit', $category->id) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    @can('categories.delete')
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal" wire:click="$set('modalCategoryId', {{ $category->id }})" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="bi bi-folder-x display-6 text-secondary mb-3"></i>
                                    <h6 class="fw-medium">No categories found</h6>
                                    <p class="text-secondary small">Try adjusting your search or filters</p>
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

            <!-- Pagination -->
            @if($categories->hasPages())
            <div class="border-top px-4 py-3">
                <div class="d-flex justify-content-end gap-2">
                    {{ $categories->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>
    @else
    <!-- Access Denied Message -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-shield-lock fs-1 text-secondary mb-3"></i>
                    <h5 class="fw-bold">Access Denied</h5>
                    <p class="text-secondary">You don't have permission to view categories.</p>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    <!-- Delete Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteCategoryModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this category? This cannot be undone if there are no associated items or subcategories.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="delete({{ $modalCategoryId }})" data-bs-dismiss="modal">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.addEventListener('categoryDeleted', () => {
        const modalEl = document.getElementById('deleteCategoryModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
        }
    });
    window.addEventListener('notify', event => {
        const { type, message } = event.detail || {};
        if (message) {
            // Simple fallback: show Bootstrap alert
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            alertDiv.role = 'alert';
            alertDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            document.body.appendChild(alertDiv);
            setTimeout(() => { alertDiv.remove(); }, 4000);
        }
    });
</script>
@endpush
