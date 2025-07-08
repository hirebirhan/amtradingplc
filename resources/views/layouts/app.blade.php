<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    @php
        if (!isset($slot)) {
            $slot = '';
        }
    @endphp
    
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Am Trading PlC') }} - @yield('title', 'Dashboard')</title>

    <!-- Critical CSS -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></noscript>
    
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"></noscript>
    
    <!-- Non-critical CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"></noscript>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="{{ asset('css/theme/theme.css') }}">
  
    <style>
        @media (min-width: 992px) {
            main.bg-body-primary.flex-grow-1 {
                margin-left: var(--sidebar-width, 280px);
            }
        }
    </style>
    
    @livewireStyles
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navbar -->
    <header class="sticky-top bg-body border-bottom" style="height: 64px;">
        <nav class="navbar navbar-expand-lg h-100">
            <div class="container-fluid px-3 px-lg-4">
                <!-- Mobile Toggle -->
                <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Brand -->
                <a class="navbar-brand d-none d-lg-flex align-items-center me-0 me-lg-4" href="{{ route('admin.dashboard') }}">
                    <div class="bg-primary text-white rounded p-1 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <span class="fw-bold">{{ config('app.name', 'Stock360') }}</span>
                </a>

                <!-- Breadcrumb -->
                <nav class="d-none d-lg-block me-auto">
                    <ol class="breadcrumb mb-0">
                        @yield('breadcrumbs')
                    </ol>
                </nav>

                <!-- Right Nav -->
                <div class="d-flex align-items-center gap-3">
                    <!-- Theme Toggle -->
                    <button class="btn btn-link text-body p-2" id="themeToggle" type="button" aria-label="Toggle theme">
                        <i class="bi bi-sun-fill d-dark-none" id="lightIcon"></i>
                        <i class="bi bi-moon-stars-fill d-none" id="darkIcon"></i>
                    </button>

                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-link text-decoration-none text-body p-0 d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-circle fs-5 me-1"></i>
                                <span class="d-none d-sm-inline">{{ Auth::user()->name ?? 'User' }}</span>
                                <i class="bi bi-chevron-down ms-1 small"></i>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border mt-2">
                            <li><a class="dropdown-item" href="{{ route('admin.profile.edit') }}">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">Sign Out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Layout -->
    <div class="d-flex flex-grow-1">
        <!-- Sidebar -->
        <aside class="bg-body position-fixed start-0 top-0 h-100 d-none d-lg-block" style="width: var(--sidebar-width, 280px); top: 64px; border-right: 1px solid var(--bs-border-color);">
            <div class="d-flex flex-column h-100">
                <div class="p-3 border-bottom">
                    <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center text-decoration-none">
                        <div class="bg-primary text-white rounded p-1 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <span class="fw-bold">{{ config('app.name', 'Stock360') }}</span>
                    </a>
                </div>
                <div class="flex-grow-1 overflow-auto">
                    @include('components.partials.sidebar')
                </div>
            </div>
        </aside>

        <!-- Mobile Sidebar Offcanvas -->
        <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title" id="sidebarLabel">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0">
                @include('components.partials.sidebar')
            </div>
        </div>

        <!-- Main Content -->
        <main class="bg-body-primary flex-grow-1">
            <div class="p-4">
                {{ $slot }}
                @yield('content')
            </div>
        </main>
        
    </div>

    <!-- Scripts (defer non-critical) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" defer></script>
    
    <script>
        // Wait for the DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize theme
            const html = document.documentElement;
            const theme = localStorage.getItem('theme') || 'light';
            // Set initial theme
            html.setAttribute('data-bs-theme', theme);
            
            // Update theme icons based on initial theme
            const lightIcon = document.getElementById('lightIcon');
            const darkIcon = document.getElementById('darkIcon');
            
            if (theme === 'dark') {
                lightIcon.classList.add('d-none');
                darkIcon.classList.remove('d-none');
            } else {
                lightIcon.classList.remove('d-none');
                darkIcon.classList.add('d-none');
            }
            
            // Theme toggle functionality
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    const current = html.getAttribute('data-bs-theme');
                    const newTheme = current === 'dark' ? 'light' : 'dark';
                    
                    // Update theme
                    html.setAttribute('data-bs-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                    
                    // Toggle icons
                    lightIcon.classList.toggle('d-none');
                    darkIcon.classList.toggle('d-none');
                });
            }
            
            // Initialize Toastr
            if (typeof toastr !== 'undefined') {
                toastr.options = {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-top-right",
                    timeOut: 5000,
                    preventDuplicates: true,
                    newestOnTop: true
                };

                // Show session messages
                @if(session('success'))
                    toastr.success("{{ session('success') }}");
                @endif
                @if(session('error'))
                    toastr.error("{{ session('error') }}");
                @endif
                @if(session('warning'))
                    toastr.warning("{{ session('warning') }}");
                @endif
                @if(session('info'))
                    toastr.info("{{ session('info') }}");
                @endif
            }

            // Livewire event listeners
            if (typeof Livewire !== 'undefined') {
                document.addEventListener('livewire:initialized', () => {
                    Livewire.on('notify', (event) => {
                        const { type, message } = event.detail || event;
                        if (toastr && toastr[type]) {
                            toastr[type](message);
                        }
                    });
                });
            }
            
            // Handle mobile sidebar toggle
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.addEventListener('show.bs.offcanvas', function () {
                    document.body.classList.add('overflow-hidden');
                });
                
                sidebar.addEventListener('hidden.bs.offcanvas', function () {
                    document.body.classList.remove('overflow-hidden');
                });
            }
        });
    </script>

    @livewireScripts
    @stack('scripts')
</body>
</html>
