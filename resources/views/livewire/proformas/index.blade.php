<div class="container-fluid px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 p-3 border rounded gap-3">
        <div class="flex-grow-1">
            <h1 class="h3 mb-1">Proformas</h1>
            <p class="text-muted mb-0">Manage quotations and proforma invoices for your customers</p>
        </div>
        @can('proformas.create')
            <a href="{{ route('admin.proformas.create') }}" class="btn btn-primary flex-shrink-0">
                <i class="bi bi-plus"></i> <span class="d-none d-sm-inline">New Proforma</span>
            </a>
        @endcan
    </div>

    <!-- Search and Filter -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-2 g-md-3">
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label small text-muted d-none d-md-block">Search</label>
                    <input type="text" wire:model.live="search" class="form-control" placeholder="Search reference or customer...">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label small text-muted d-none d-md-block">Status</label>
                    <select wire:model.live="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="approved">Approved</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label small text-muted d-none d-md-block">Date</label>
                    <input type="date" wire:model.live="dateFilter" class="form-control">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label small text-muted d-none d-md-block">&nbsp;</label>
                    <button wire:click="resetFilters" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-sm-inline">Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop Table -->
    <div class="card shadow-sm d-none d-lg-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="border-0 px-3 py-3">Reference</th>
                            <th class="border-0 px-3 py-3">Customer</th>
                            <th class="border-0 px-3 py-3">Date</th>
                            <th class="border-0 px-3 py-3">Valid Until</th>
                            <th class="border-0 px-3 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proformas as $proforma)
                            <tr class="align-middle">
                                <td class="px-3 py-3">
                                    <a href="{{ route('admin.proformas.show', $proforma) }}" class="text-decoration-none fw-semibold text-primary">
                                        {{ $proforma->reference_no }}
                                    </a>
                                </td>
                                <td class="px-3 py-3">{{ $proforma->customer->name }}</td>
                                <td class="px-3 py-3">{{ $proforma->created_at->format('M d, Y') }}</td>
                                <td class="px-3 py-3">{{ $proforma->valid_until ? $proforma->valid_until->format('M d, Y') : '-' }}</td>
                                <td class="px-3 py-3">
                                    <div class="d-flex justify-content-center gap-1">
                                        @can('proformas.view')
                                            <a href="{{ route('admin.proformas.show', $proforma) }}" class="btn btn-outline-primary btn-sm" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endcan
                                        @can('proformas.edit')
                                            <a href="{{ route('admin.proformas.edit', $proforma) }}" class="btn btn-outline-secondary btn-sm" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endcan
                                        <a href="{{ route('admin.proformas.print', $proforma) }}" class="btn btn-outline-info btn-sm" target="_blank" title="Print">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        @can('proformas.delete')
                                            <button wire:click="confirmDelete({{ $proforma->id }})" class="btn btn-outline-danger btn-sm" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-file-earmark-check display-4 mb-3 d-block"></i>
                                        <p class="mb-3">No proformas found</p>
                                        @can('proformas.create')
                                            <a href="{{ route('admin.proformas.create') }}" class="btn btn-primary">
                                                <i class="bi bi-plus"></i> Create your first proforma
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Mobile/Tablet Cards -->
    <div class="d-lg-none">
        @forelse($proformas as $proforma)
            <div class="card mb-3 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <a href="{{ route('admin.proformas.show', $proforma) }}" class="text-decoration-none fw-bold text-primary">
                                    {{ $proforma->reference_no }}
                                </a>
                            </h6>
                            <p class="text-muted mb-1 small">{{ $proforma->customer->name }}</p>
                        </div>
                        <span class="badge bg-{{ $proforma->status === 'approved' ? 'success' : 'warning' }} ms-2">
                            {{ ucfirst($proforma->status) }}
                        </span>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Date</small>
                            <span class="small fw-medium">{{ $proforma->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Valid Until</small>
                            <span class="small fw-medium">{{ $proforma->valid_until ? $proforma->valid_until->format('M d, Y') : '-' }}</span>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-1 flex-wrap">
                        @can('proformas.view')
                            <a href="{{ route('admin.proformas.show', $proforma) }}" class="btn btn-outline-primary btn-sm flex-fill" title="View">
                                <i class="bi bi-eye"></i> <span class="d-none d-sm-inline">View</span>
                            </a>
                        @endcan
                        @can('proformas.edit')
                            <a href="{{ route('admin.proformas.edit', $proforma) }}" class="btn btn-outline-secondary btn-sm flex-fill" title="Edit">
                                <i class="bi bi-pencil"></i> <span class="d-none d-sm-inline">Edit</span>
                            </a>
                        @endcan
                        <a href="{{ route('admin.proformas.print', $proforma) }}" class="btn btn-outline-info btn-sm flex-fill" target="_blank" title="Print">
                            <i class="bi bi-printer"></i> <span class="d-none d-sm-inline">Print</span>
                        </a>
                        @can('proformas.delete')
                            <button wire:click="confirmDelete({{ $proforma->id }})" class="btn btn-outline-danger btn-sm flex-fill" title="Delete">
                                <i class="bi bi-trash"></i> <span class="d-none d-sm-inline">Delete</span>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-file-earmark-check display-4 text-muted mb-3"></i>
                    <h5 class="text-muted mb-3">No proformas found</h5>
                    @can('proformas.create')
                        <a href="{{ route('admin.proformas.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Create your first proforma
                        </a>
                    @endcan
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($proformas->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $proformas->links() }}
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal && $proformaToDelete)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Confirm Deletion
                    </h5>
                    <button type="button" wire:click="$set('showDeleteModal', false)" class="btn-close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this proforma?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" wire:click="$set('showDeleteModal', false)" class="btn btn-secondary">Cancel</button>
                    <button type="button" wire:click="deleteProforma" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>