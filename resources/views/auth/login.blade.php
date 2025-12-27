<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', env('APP_NAME', 'Muhdin General Trading')) }} - Login</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('Logo.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Google Fonts CSS is dynamic; SRI not applicable -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" crossorigin>

    <!-- Core CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('css/theme/theme.css') }}">
    
    <style>
        /* Login-specific styles using ONLY theme variables - no custom classes */
        body {
            background: linear-gradient(135deg, var(--background) 0%, var(--muted) 100%);
            min-height: 100vh;
        }
        
        .card {
            border-radius: calc(var(--border-radius) * 2);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            max-width: 36rem;
            width: 100%;
            margin: 0 auto;
        }
        
        .card:hover {
            box-shadow: 0 1rem 2rem rgb(0 0 0 / 20%);
            transform: translateY(-2px);
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-info) 100%);
            box-shadow: 0 0.5rem 1rem rgb(var(--bs-primary-rgb) / 25%);
        }
        
        .form-floating .form-control:focus ~ label,
        .form-floating .form-control:not(:placeholder-shown) ~ label {
            color: var(--bs-primary);
        }
        
        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted-foreground);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: var(--border-radius);
            transition: all 0.15s ease;
        }
        
        .password-toggle:hover {
            color: var(--foreground);
            background-color: var(--muted);
        }
        
        .btn-primary {
            padding: 0.875rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: all 0.2s ease;
        }
        
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .alert-danger {
            background-color: rgb(var(--bs-danger-rgb) / 10%);
            border-color: var(--bs-danger);
            color: var(--bs-danger);
            border-radius: var(--border-radius);
        }
        
        /* Responsive adjustments using Bootstrap breakpoints */
        @media (max-width: 768px) {
            .card {
                max-width: 32rem;
                margin: 1rem;
                padding: 2rem 1.5rem !important;
            }
        }
        
        @media (max-width: 576px) {
            .card {
                max-width: 28rem;
                padding: 1.5rem 1rem !important;
            }
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">
    <div class="card border-0 shadow-sm p-4 p-md-5">
        <div class="d-flex flex-column align-items-center mb-4">
            <div class="bg-gradient-primary text-white rounded d-flex align-items-center justify-content-center mb-3" style="width: 4rem; height: 4rem;">
                <i class="bi bi-box fs-2"></i>
            </div>
            <h1 class="fw-bold mb-1 text-center">{{ config('app.name', env('APP_NAME', 'Muhdin General Trading')) }}</h1>
            <p class="text-muted-foreground text-center mb-0 small">Welcome back! Please sign in to continue.</p>
        </div>
        
        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf
            
            <div class="form-floating mb-3">
                <input id="email" type="email" class="form-control" name="email" 
                       value="{{ old('email') }}" required autofocus autocomplete="username" 
                       placeholder="Enter your email">
                <label for="email">Email Address</label>
            </div>
            
            <div class="form-floating position-relative mb-3">
                <input id="password" type="password" class="form-control" name="password" 
                       required autocomplete="current-password" placeholder="Enter your password">
                <label for="password">Password</label>
                <button type="button" class="password-toggle toggle-password" title="Show password">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" 
                       {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label text-muted-foreground" for="remember">
                    Remember me
                </label>
            </div>
            
            @if ($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif
            
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary" id="loginBtn">
                    <span class="btn-content">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Sign In
                    </span>
                    <span class="btn-loading d-none">
                        <div class="spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Signing in...</span>
                    </span>
                </button>
            </div>
            
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <a href="#" class="text-primary small text-decoration-none fw-medium" 
                   onclick="showMessage('Please contact your system administrator to reset your password.')">
                    <i class="bi bi-question-circle me-1"></i>
                    Forgot password?
                </a>
                <a href="#" class="text-primary small text-decoration-none fw-medium" 
                   onclick="showMessage('User registration is managed by system administrators. Please contact your administrator for account creation.')">
                    <i class="bi bi-person-plus me-1"></i>
                    Need an account?
                </a>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <script>
        // Password toggle functionality
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.closest('.form-floating').querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                    this.setAttribute('title', 'Hide password');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                    this.setAttribute('title', 'Show password');
                }
            });
        });
        
        // Form submission with loading state
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        
        if (loginForm && loginBtn) {
            loginForm.addEventListener('submit', function() {
                const btnContent = loginBtn.querySelector('.btn-content');
                const btnLoading = loginBtn.querySelector('.btn-loading');
                
                btnContent.classList.add('d-none');
                btnLoading.classList.remove('d-none');
                loginBtn.disabled = true;
                
                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    btnContent.classList.remove('d-none');
                    btnLoading.classList.add('d-none');
                    loginBtn.disabled = false;
                }, 10000);
            });
        }
        
        // Form validation feedback
        const inputs = document.querySelectorAll('input[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
        });
        
        // Message display function
        function showMessage(message) {
            alert(message);
        }
        
        // Add subtle animation on page load
        document.addEventListener('DOMContentLoaded', function() {
            const loginCard = document.querySelector('.card');
            if (loginCard) {
                loginCard.style.opacity = '0';
                loginCard.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    loginCard.style.transition = 'all 0.5s ease';
                    loginCard.style.opacity = '1';
                    loginCard.style.transform = 'translateY(0)';
                }, 100);
            }
        });
    </script>
</body>
</html>
