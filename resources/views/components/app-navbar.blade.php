{{-- Reusable Navbar Component --}}
<nav class="layout-navbar navbar navbar-expand-lg">
    <div class="container-fluid px-3">
        <!-- Menu Toggles -->
        <div class="d-flex align-items-center">
            <button id="menu-toggle" class="btn btn-sm d-lg-none me-2" type="button" aria-label="Toggle menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <button id="compact-toggle" class="btn btn-sm d-none d-lg-flex me-2" type="button" aria-label="Toggle sidebar width">
                <i class="fa-solid fa-angle-left"></i>
            </button>
        </div>

        <!-- Toggle Button for Mobile Navigation -->
        <button class="navbar-toggler ms-auto d-lg-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Search Bar - Only visible on larger screens -->
        <div class="search-wrapper me-auto d-none d-md-block">
            <form action="#" method="GET">
                <div class="position-relative">
                    <i class="fa-solid fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" class="form-control search-input" placeholder="Search..." aria-label="Search">
                </div>
            </form>
        </div>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Mobile Search - Only visible on small screens -->
            <div class="d-md-none my-3">
                <form action="#" method="GET">
                    <div class="input-group">
                        <span class="input-group-text border-end-0">
                            <i class="fa-solid fa-search"></i>
                        </span>
                        <input class="form-control border-start-0" type="search" placeholder="Search...">
                    </div>
                </form>
            </div>

            <!-- Right Menu -->
            <ul class="navbar-nav ms-auto d-flex align-items-center navbar-actions gap-2">
                <!-- Quick Actions -->
                <li class="nav-item d-none d-lg-block">
                    <div class="dropdown">
                        <button class="navbar-action-btn" type="button" id="quickActions" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Quick actions">
                            <i class="fa-solid fa-bolt"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="quickActions">
                            <li><h6 class="dropdown-header">Quick Actions</h6></li>
                            <li><a class="dropdown-item" href="{{ route('admin.items.create') }}">
                                <i class="fa-solid fa-cube me-2 text-primary"></i>New Item
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.items.import') }}">
                                <i class="fa-solid fa-upload me-2 text-warning"></i>Import Items
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.sales.create') }}">
                                <i class="fa-solid fa-cash-register me-2 text-success"></i>New Sale
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.purchases.create') }}">
                                <i class="fa-solid fa-shopping-cart me-2 text-info"></i>New Purchase
                            </a></li>
                        </ul>
                    </div>
                </li>
                
                <!-- Notifications -->
                <li class="nav-item">
                    <div class="dropdown">
                        <button class="navbar-action-btn position-relative" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                            <i class="fa-regular fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                3
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" style="width: 320px;">
                            <li>
                                <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                    Notifications <span class="badge bg-primary rounded-pill">3</span>
                                </h6>
                            </li>
                            <li><a class="dropdown-item py-2" href="#">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 bg-primary text-white rounded p-1">
                                        <i class="fa-solid fa-cube"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <p class="mb-0 fw-medium">Low stock alert: Product ABC</p>
                                        <p class="text-muted mb-0 fs-xs">10 minutes ago</p>
                                    </div>
                                </div>
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 bg-success text-white rounded p-1">
                                        <i class="fa-solid fa-cash-register"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <p class="mb-0 fw-medium">New sale completed</p>
                                        <p class="text-muted mb-0 fs-xs">1 hour ago</p>
                                    </div>
                                </div>
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 bg-warning text-white rounded p-1">
                                        <i class="fa-solid fa-credit-card"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <p class="mb-0 fw-medium">Payment due reminder</p>
                                        <p class="text-muted mb-0 fs-xs">5 hours ago</p>
                                    </div>
                                </div>
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="#">View all notifications</a></li>
                        </ul>
                    </div>
                </li>
                
                <!-- User Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" 
                       data-bs-toggle="dropdown" id="userDropdown" aria-expanded="false">
                        <div class="avatar">
                            {{ Auth::user() ? substr(Auth::user()->name, 0, 1) : 'U' }}
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <div class="dropdown-item-text">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        {{ Auth::user() ? substr(Auth::user()->name, 0, 1) : 'U' }}
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-medium">{{ Auth::user()->name ?? 'User' }}</p>
                                        <p class="text-muted mb-0 fs-xs">{{ Auth::user()->email ?? 'user@example.com' }}</p>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('admin.profile.edit') }}">
                            <i class="fa-solid fa-user me-2 text-primary"></i>My Profile
                        </a></li>
                        <li>
                            {{-- Theme menu removed --}}
                            <div class="d-none">
                                <div class="dropdown-item dropdown">
                                    <a href="#" class="d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-palette me-2 text-info"></i>
                                        <span class="flex-grow-1">Theme</span>
                                        <i class="fa-solid fa-angle-right ms-auto fs-xs"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><h6 class="dropdown-header">Select Theme</h6></li>
                                        <li><button class="dropdown-item theme-item d-flex align-items-center" data-theme-value="light">
                                            <i class="fa-solid fa-sun me-2 text-warning"></i>Light
                                            <i class="fa-solid fa-check ms-auto theme-check"></i>
                                        </button></li>
                                        <li><button class="dropdown-item theme-item d-flex align-items-center" data-theme-value="dark">
                                            <i class="fa-solid fa-moon me-2 text-primary"></i>Dark
                                            <i class="fa-solid fa-check ms-auto theme-check"></i>
                                        </button></li>
                                        <li><button class="dropdown-item theme-item d-flex align-items-center" data-theme-value="system">
                                            <i class="fa-solid fa-desktop me-2 text-success"></i>System
                                            <i class="fa-solid fa-check ms-auto theme-check"></i>
                                        </button></li>
                                    </ul>
                                </div>
                            </div>
                        </li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fa-solid fa-gear me-2 text-secondary"></i>Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav> 