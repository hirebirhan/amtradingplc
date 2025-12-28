{{-- Clean Modern Sidebar --}}
<div class="d-flex flex-column h-100 w-100">
    <nav class="flex-grow-1 overflow-y-auto px-3 py-3 d-flex flex-column gap-1">
        <!-- Dashboard -->
        <div>
            <a href="{{ route('admin.dashboard') }}"
               class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.dashboard') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}"
               aria-current="{{ request()->routeIs('admin.dashboard') ? 'page' : 'false' }}">
                <i class="bi bi-speedometer2 fs-5"></i>
                <span class="fw-medium">Dashboard</span>
            </a>
        </div>

        <!-- Inventory Section -->
        @if(auth()->check() && (auth()->user()->can('categories.view') || auth()->user()->can('items.view') || auth()->user()->can('transfers.view')))
        <div>
            <button class="nav-link d-flex align-items-center justify-content-between w-100 px-3 py-2 rounded border-0 text-start {{ request()->routeIs('admin.inventory') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#inventoryMenu"
                    aria-expanded="{{ request()->routeIs('admin.categories.*') || request()->routeIs('admin.items.*') || request()->routeIs('admin.transfers.*') ? 'true' : 'false' }}">
                <span class="d-flex align-items-center gap-3">
                    <i class="bi bi-box-seam fs-5"></i>
                    <span class="fw-medium">Inventory</span>
                </span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse {{ request()->routeIs('admin.categories.*') || request()->routeIs('admin.items.*') || request()->routeIs('admin.transfers.*') ? 'show' : '' }}" id="inventoryMenu">
                <div class="d-flex flex-column gap-1 ms-4 mt-1">
                    @can('categories.view')
                    <a href="{{ route('admin.categories.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.categories.*') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-tags fs-6"></i>
                        <span>Categories</span>
                    </a>
                    @endcan
                    @can('items.view')
                    <a href="{{ route('admin.items.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.items.*') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-box fs-6"></i>
                        <span>Items</span>
                    </a>
                    @endcan
                    @can('transfers.view')
                    <a href="{{ route('admin.transfers.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.transfers.index') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-arrow-left-right fs-6"></i>
                        <span>Transfers</span>
                    </a>
                    @endcan
                    @can('transfers.view')
                    <a href="{{ route('admin.transfers.pending') }}"
                       class="nav-link d-flex align-items-center justify-content-between px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.transfers.pending') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-clock-history fs-6"></i>
                            <span>Pending Approvals</span>
                        </div>
                        @if(isset($pendingTransfersCount) && $pendingTransfersCount > 0)
                        <span class="badge bg-danger rounded-pill">{{ $pendingTransfersCount }}</span>
                        @endif
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        @endif
        <!-- Purchases Section -->
        @if(auth()->check() && (auth()->user()->can('suppliers.view') || auth()->user()->can('purchases.view') || auth()->user()->can('purchases.create')))
        <div>
            <button class="nav-link d-flex align-items-center justify-content-between w-100 px-3 py-2 rounded border-0 text-start {{ request()->routeIs('admin.purchases') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#purchasesMenu"
                    aria-expanded="{{ request()->routeIs('admin.suppliers.*') || request()->routeIs('admin.purchases.*') ? 'true' : 'false' }}">
                <span class="d-flex align-items-center gap-3">
                    <i class="bi bi-cart fs-5"></i>
                    <span class="fw-medium">Purchases</span>
                </span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse {{ request()->routeIs('admin.suppliers.*') || request()->routeIs('admin.purchases.*') ? 'show' : '' }}" id="purchasesMenu">
                <div class="d-flex flex-column gap-1 ms-4 mt-1">
                    @can('purchases.create')
                    <a href="{{ route('admin.purchases.create') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.purchases.create') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-plus-circle fs-6"></i>
                        <span>Create Purchase</span>
                    </a>
                    @endcan
                    @can('suppliers.view')
                    <a href="{{ route('admin.suppliers.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.suppliers.*') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-truck fs-6"></i>
                        <span>Suppliers</span>
                    </a>
                    @endcan
                    @can('purchases.view')
                    <a href="{{ route('admin.purchases.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.purchases.index') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-file-earmark-text fs-6"></i>
                        <span>Purchase Orders</span>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        @endif

        <!-- Sales Section -->
        @if(auth()->check() && (auth()->user()->can('customers.view') || auth()->user()->can('sales.view') || auth()->user()->can('sales.create') || auth()->user()->can('proformas.view')))
        <div>
            <button class="nav-link d-flex align-items-center justify-content-between w-100 px-3 py-2 rounded border-0 text-start {{ request()->routeIs('admin.sales') || request()->routeIs('admin.proformas') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#salesMenu"
                    aria-expanded="{{ request()->routeIs('admin.customers.*') || request()->routeIs('admin.sales.*') || request()->routeIs('admin.proformas.*') ? 'true' : 'false' }}">
                <span class="d-flex align-items-center gap-3">
                    <i class="bi bi-graph-up fs-5"></i>
                    <span class="fw-medium">Sales</span>
                </span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse {{ request()->routeIs('admin.customers.*') || request()->routeIs('admin.sales.*') || request()->routeIs('admin.proformas.*') ? 'show' : '' }}" id="salesMenu">
                <div class="d-flex flex-column gap-1 ms-4 mt-1">
                    @can('sales.create')
                    <a href="{{ route('admin.sales.create') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.sales.create') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-plus-circle fs-6"></i>
                        <span>Create Sale</span>
                    </a>
                    @endcan
                    @can('customers.view')
                    <a href="{{ route('admin.customers.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.customers.*') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-people fs-6"></i>
                        <span>Customers</span>
                    </a>
                    @endcan
                    @can('sales.view')
                    <a href="{{ route('admin.sales.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.sales.index') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-receipt fs-6"></i>
                        <span>Sales Orders</span>
                    </a>
                    @endcan
                    @can('proformas.view')
                    <a href="{{ route('admin.proformas.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.proformas.*') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-file-earmark-check fs-6"></i>
                        <span>Proformas</span>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        @endif

        <!-- Single Menu Items -->
        @can('credits.view')
        <div>
            <a href="{{ route('admin.credits.index') }}"
               class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.credits.*') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                <i class="bi bi-credit-card fs-5"></i>
                <span class="fw-medium">Credits</span>
            </a>
        </div>
        @endcan
        @can('activities.view')
        <div>
            <a href="{{ route('admin.activities.index') }}"
               class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.activities.*') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                <i class="bi bi-clock-history fs-5"></i>
                <span class="fw-medium">Activity Log</span>
            </a>
        </div>
        @endcan

        <!-- Divider -->
        @if(auth()->check() && (auth()->user()->can('users.view') || auth()->user()->can('roles.view') || auth()->user()->can('settings.manage')))
        <div class="border-top border-light my-3"></div>
        <div class="px-3 py-2">
            <small class="text-uppercase text-muted fw-bold" style="font-size:0.75rem; letter-spacing:0.5px;">Account Pages</small>
        </div>
        @endif

        <!-- User Management -->
        @if(auth()->check() && (auth()->user()->can('users.view') || auth()->user()->can('roles.view')))
        <div>
            <button class="nav-link d-flex align-items-center justify-content-between w-100 px-3 py-2 rounded border-0 text-start {{ request()->routeIs('admin.users') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#usersMenu"
                    aria-expanded="{{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') ? 'true' : 'false' }}">
                <span class="d-flex align-items-center gap-3">
                    <i class="bi bi-people fs-5"></i>
                    <span class="fw-medium">User Management</span>
                </span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') ? 'show' : '' }}" id="usersMenu">
                <div class="d-flex flex-column gap-1 ms-4 mt-1">
                    @can('users.view')
                    <a href="{{ route('admin.users.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.users.*') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-person fs-6"></i>
                        <span>Users</span>
                    </a>
                    @endcan
                    @can('roles.view')
                    <a href="{{ route('admin.roles.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.roles.*') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-shield fs-6"></i>
                        <span>Roles</span>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        @endif

        <!-- Settings -->
        @if(auth()->check() && (auth()->user()->can('settings.manage') || auth()->user()->can('bank-accounts.view') || auth()->user()->can('warehouses.view') || auth()->user()->can('branches.view')))
        <div>
            <button class="nav-link d-flex align-items-center justify-content-between w-100 px-3 py-2 rounded border-0 text-start {{ request()->routeIs('admin.settings') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#settingsMenu"
                    aria-expanded="{{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.bank-accounts.*') || request()->routeIs('admin.warehouses.*') || request()->routeIs('admin.branches.*') ? 'true' : 'false' }}">
                <span class="d-flex align-items-center gap-3">
                    <i class="bi bi-gear fs-5"></i>
                    <span class="fw-medium">Settings</span>
                </span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="collapse {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.bank-accounts.*') || request()->routeIs('admin.warehouses.*') || request()->routeIs('admin.branches.*') ? 'show' : '' }}" id="settingsMenu">
                <div class="d-flex flex-column gap-1 ms-4 mt-1">
                    @can('settings.manage')
                    <a href="{{ route('admin.settings.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.settings.*') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-sliders fs-6"></i>
                        <span>General Settings</span>
                    </a>
                    @endcan
                    @can('warehouses.view')
                    <a href="{{ route('admin.warehouses.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.warehouses.*') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-building fs-6"></i>
                        <span>Warehouses</span>
                    </a>
                    @endcan
                    @can('branches.view')
                    <a href="{{ route('admin.branches.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.branches.*') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-diagram-3 fs-6"></i>
                        <span>Branches</span>
                    </a>
                    @endcan
                    @can('bank-accounts.view')
                    <a href="{{ route('admin.bank-accounts.index') }}"
                       class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded text-decoration-none {{ request()->routeIs('admin.bank-accounts.*') ? 'bg-primary bg-opacity-10 text-primary' : 'text-body-secondary' }}">
                        <i class="bi bi-bank fs-6"></i>
                        <span>Bank Accounts</span>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        @endif
    </nav>
</div>