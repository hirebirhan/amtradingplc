<div>
    {{-- Clean Activities Management Page --}}
    
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title and Stats Badges -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">
                    @if(auth()->user()->hasRole('Sales') && !auth()->user()->hasAnyRole(['SuperAdmin', 'BranchManager']))
                        My Activity Log
                    @else
                        Recent Activity
                    @endif
                </h4>
               
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                @if(auth()->user()->hasRole('Sales') && !auth()->user()->hasAnyRole(['SuperAdmin', 'BranchManager']))
                    Track your inventory movements, sales, and stock changes
                @else
                    Monitor all inventory movements, stock changes, and system activities
                @endif
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <button wire:click="clearFilters" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
            </button>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card border-0 shadow-sm">
        <!-- Enhanced Filters Section -->
        <div class="card-header border-bottom">
            <div class="row g-3">
                <!-- Search Items -->
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Search Items</label>
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="Search by item name or SKU..." wire:model.live.debounce.300ms="search">
                        @if($search)
                            <button class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 text-muted" type="button" wire:click="$set('search', '')">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>
                </div>
                
                <!-- Activity Type -->
                @if(auth()->user()->hasAnyRole(['SuperAdmin', 'BranchManager']))
                <div class="col-md-2">
                @else
                <div class="col-md-3">
                @endif
                    <label class="form-label small text-muted mb-1">Activity Type</label>
                    <select class="form-select" wire:model.live="activityType">
                        <option value="">All Types</option>
                        @if(auth()->user()->hasRole('Sales') && !auth()->user()->hasAnyRole(['SuperAdmin', 'BranchManager']))
                            {{-- Sales users only see sale and return activities --}}
                            <option value="sale">Sale</option>
                            <option value="return">Return</option>
                        @else
                            {{-- Admin and managers see all activity types --}}
                            <option value="sale">Sale</option>
                            <option value="purchase">Purchase</option>
                            <option value="transfer">Transfer</option>
                            <option value="return">Return</option>
                            <option value="initial">Initial Stock</option>
                        @endif
                    </select>
                </div>
                
                <!-- Warehouse Filter (Only for SuperAdmin and BranchManager) -->
                @if(auth()->user()->hasAnyRole(['SuperAdmin', 'BranchManager']))
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Warehouse</label>
                    <select class="form-select" wire:model.live="warehouseFilter">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                <!-- Date Range -->
                @if(auth()->user()->hasAnyRole(['SuperAdmin', 'BranchManager']))
                <div class="col-md-3">
                @else
                <div class="col-md-4">
                @endif
                    <label class="form-label small text-muted mb-1">Date Range</label>
                    <div class="d-flex gap-2">
                        <input type="date" class="form-control" wire:model.live="dateFrom">
                        <input type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                </div>
                
                <!-- Records Per Page -->
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Per Page</label>
                    <select wire:model.live="perPage" class="form-select">
                        <option value="10">10 records</option>
                        <option value="20">20 records</option>
                        <option value="50">50 records</option>
                        <option value="100">100 records</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <!-- Desktop Table View -->
            <div class="d-none d-lg-block">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="px-3 py-3 fw-semibold text-dark">
                                    <i class="bi bi-box me-1"></i>Item
                                </th>
                                <th class="py-3 fw-semibold text-dark">
                                    <i class="bi bi-tag me-1"></i>Type
                                </th>
                                <th class="py-3 text-center fw-semibold text-dark">
                                    <i class="bi bi-arrow-left-right me-1"></i>Quantity
                                </th>
                                <th class="py-3 text-center fw-semibold text-dark">
                                    <i class="bi bi-calculator me-1"></i>Balance After
                                </th>
                                <th class="py-3 fw-semibold text-dark">
                                    <i class="bi bi-warehouse me-1"></i>Warehouse
                                </th>
                                <th class="py-3 fw-semibold text-dark">
                                    <i class="bi bi-person me-1"></i>User
                                </th>
                                <th class="py-3 fw-semibold text-dark">
                                    <i class="bi bi-calendar me-1"></i>Date
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activities as $activity)
                            <tr>
                                <td class="px-3 py-3">
                                    <div class="fw-medium">
                                        {{ $activity->item?->name ?? 'Deleted Item' }}
                                    </div>
                                </td>
                                <td class="py-3">
                                    <span class="badge {{ $this->getActivityTypeBadgeClass($activity->reference_type) }}">
                                        {{ $this->getActivityTypeDisplayName($activity->reference_type) }}
                                    </span>
                                </td>
                                <td class="py-3 text-center">
                                    @if($activity->quantity_change > 0)
                                        <span class="text-success fw-medium">+{{ number_format($activity->quantity_change) }}</span>
                                    @elseif($activity->quantity_change < 0)
                                        <span class="text-danger fw-medium">{{ number_format($activity->quantity_change) }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="py-3 text-center">
                                    <span class="fw-medium">{{ number_format($activity->quantity_after) }}</span>
                                </td>
                                <td class="py-3">
                                    <span class="text-secondary">{{ $activity->warehouse?->name ?? 'N/A' }}</span>
                                </td>
                                <td class="py-3">
                                    <span class="text-secondary">{{ $activity->user?->name ?? 'System' }}</span>
                                </td>
                                <td class="py-3">
                                    <div class="text-secondary small">
                                        {{ $activity->created_at->format('M d, Y') }} at {{ $activity->created_at->format('h:i A') }}
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="mb-3">
                                            <i class="bi bi-clock-history text-secondary" style="font-size: 3rem;"></i>
                                        </div>
                                        @if(auth()->user()->hasRole('Sales') && !auth()->user()->hasAnyRole(['SuperAdmin', 'BranchManager']))
                                            <h5 class="text-secondary">No activities found for your account</h5>
                                            <p class="text-secondary">Activities will appear here when you create sales, process returns, or make stock adjustments.</p>
                                        @else
                                            <h5 class="text-secondary">No activities found</h5>
                                            <p class="text-secondary">System activities will appear here as users interact with inventory.</p>
                                        @endif
                                        @if($search || $activityType || $warehouseFilter || $dateFrom || $dateTo)
                                            <button wire:click="clearFilters" class="btn btn-outline-primary btn-sm mt-2">
                                                <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                                            </button>
                                        @endif
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
                @forelse($activities as $activity)
                    <div class="border-bottom p-3">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="flex-grow-1">
                                <div class="fw-medium">
                                    {{ $activity->item?->name ?? 'Deleted Item' }}
                                </div>
                                <div class="small text-secondary mt-1">
                                    {{ $activity->created_at->format('M d, Y') }} at {{ $activity->created_at->format('h:i A') }}
                                </div>
                            </div>
                            <div>
                                <span class="badge {{ $this->getActivityTypeBadgeClass($activity->reference_type) }}">
                                    {{ $this->getActivityTypeDisplayName($activity->reference_type) }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-4">
                                <div class="small text-secondary">Quantity Change</div>
                                <div class="fw-medium">
                                    @if($activity->quantity_change > 0)
                                        <span class="text-success">+{{ number_format($activity->quantity_change) }}</span>
                                    @elseif($activity->quantity_change < 0)
                                        <span class="text-danger">{{ number_format($activity->quantity_change) }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="small text-secondary">Balance After</div>
                                <div class="fw-medium">{{ number_format($activity->quantity_after) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="small text-secondary">User</div>
                                <div class="fw-medium">{{ $activity->user?->name ?? 'System' }}</div>
                            </div>
                            <div class="col-12">
                                <div class="small text-secondary">Warehouse</div>
                                <div class="fw-medium">{{ $activity->warehouse?->name ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="mb-3">
                                <i class="bi bi-clock-history text-secondary" style="font-size: 3rem;"></i>
                            </div>
                            @if(auth()->user()->hasRole('Sales') && !auth()->user()->hasAnyRole(['SuperAdmin', 'BranchManager']))
                                <h5 class="text-secondary">No activities found for your account</h5>
                                <p class="text-secondary">Activities will appear here when you create sales, process returns, or make stock adjustments.</p>
                            @else
                                <h5 class="text-secondary">No activities found</h5>
                                <p class="text-secondary">System activities will appear here as users interact with inventory.</p>
                            @endif
                            @if($search || $activityType || $warehouseFilter || $dateFrom || $dateTo)
                                <button wire:click="clearFilters" class="btn btn-outline-primary btn-sm mt-2">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                                </button>
                            @endif
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Clean Pagination -->
            @if($activities->hasPages())
                <div class="d-flex justify-content-center py-3 border-top">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>
    </div>
</div> 