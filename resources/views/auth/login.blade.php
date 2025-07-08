<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Muhdin General Trading') }} - Login</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('Logo.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Core CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/theme/theme.css') }}">
    
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-body">
    <div class="card shadow-sm border-0 p-4" style="max-width: 400px; width: 100%;">
        <div class="d-flex flex-column align-items-center mb-4">
            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                <i class="bi bi-box fs-2"></i>
            </div>
            <h4 class="fw-bold mb-1">{{ config('app.name', 'Muhdin General Trading') }}</h4>
            <p class="text-secondary small mb-0">Welcome back! Please sign in to continue.</p>
        </div>
        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input id="password" type="password" class="form-control" name="password" required autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary toggle-password" title="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3 form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary" id="loginBtn">Sign In</button>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <a href="#" class="text-primary small" onclick="alert('Please contact your system administrator to reset your password.')">Forgot password?</a>
                <a href="#" class="text-primary small" onclick="alert('User registration is managed by system administrators. Please contact your administrator for account creation.')">Need an account?</a>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password toggle functionality
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.closest('.input-group').querySelector('input');
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
                const originalContent = loginBtn.innerHTML;
                loginBtn.innerHTML = `
                    <span class="d-flex align-items-center justify-content-center">
                        <div class="spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Signing in...</span>
                    </span>
                `;
                loginBtn.disabled = true;
                
                // Re-enable after 5 seconds as fallback
                setTimeout(() => {
                    loginBtn.innerHTML = originalContent;
                    loginBtn.disabled = false;
                }, 5000);
            });
        }
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
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
    </script>
</body>
</html>