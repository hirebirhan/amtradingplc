{{-- Sleek Settings Dashboard --}}
<x-app-layout>
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Settings</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-body-secondary mb-0 small">
                Configure system preferences, manage users, and monitor system health
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <button class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-download me-1"></i>Export
            </button>
            <button class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Settings Grid -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 bg-primary bg-opacity-10 rounded">
                                <i class="bi bi-people text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-semibold">User Management</h6>
                                <p class="text-body-secondary small mb-0">{{ App\Models\User::count() }} users</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 bg-success bg-opacity-10 rounded">
                                <i class="bi bi-tags text-success"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-semibold">Expense Types</h6>
                                <p class="text-body-secondary small mb-0">Financial categories</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.settings.expense-types') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 bg-warning bg-opacity-10 rounded">
                                <i class="bi bi-diagram-3 text-warning"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-semibold">Organization</h6>
                                <p class="text-body-secondary small mb-0">Departments & positions</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.settings.departments-positions') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 bg-info bg-opacity-10 rounded">
                                <i class="bi bi-database text-info"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-semibold">System Backup</h6>
                                <p class="text-body-secondary small mb-0">Data protection</p>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download me-1"></i>Backup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Overview -->
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-semibold">System Health</h6>
                        <span class="badge bg-success-subtle text-success-emphasis">
                            <i class="bi bi-check-circle me-1"></i>All Systems Operational
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <div class="mb-2">
                                    <i class="bi bi-database text-success fs-4"></i>
                                </div>
                                <div class="small fw-medium">Database</div>
                                <div class="small text-success">Connected</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <div class="mb-2">
                                    <i class="bi bi-hdd text-success fs-4"></i>
                                </div>
                                <div class="small fw-medium">Storage</div>
                                <div class="small text-success">{{ round(disk_free_space('/') / 1024 / 1024 / 1024, 1) }}GB free</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <div class="mb-2">
                                    <i class="bi bi-speedometer2 text-success fs-4"></i>
                                </div>
                                <div class="small fw-medium">Cache</div>
                                <div class="small text-success">Optimized</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <div class="mb-2">
                                    <i class="bi bi-shield-check text-success fs-4"></i>
                                </div>
                                <div class="small fw-medium">Security</div>
                                <div class="small text-success">Protected</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-semibold">Quick Stats</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small text-body-secondary">Total Users</span>
                        <span class="fw-semibold">{{ App\Models\User::count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small text-body-secondary">Inventory Items</span>
                        <span class="fw-semibold">{{ App\Models\Item::count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small text-body-secondary">Warehouses</span>
                        <span class="fw-semibold">{{ App\Models\Warehouse::count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small text-body-secondary">Active Sessions</span>
                        <span class="fw-semibold">{{ App\Models\Sale::count() }}</span>
                    </div>
                    <div class="text-center">
                        <small class="text-body-secondary">
                            <i class="bi bi-clock me-1"></i>Updated {{ now()->format('M d, H:i') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>


</x-app-layout> 