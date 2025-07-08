@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 pb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 fw-semibold text-slate-800 mb-0">
                <i class="fas fa-user-circle me-2 text-primary-600"></i> My Profile
            </h2>
        </div>

        <div class="row g-4">
            <!-- Profile Sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="mb-4 position-relative">
                                <div class="bg-primary bg-opacity-10 rounded-circle mx-auto d-flex justify-content-center align-items-center" 
                                     style="width: 110px; height: 110px">
                                    <span class="display-4 text-primary">{{ Auth::user() ? substr(Auth::user()->name, 0, 1) : 'A' }}</span>
                                </div>
                            </div>
                            
                            <h5 class="fw-bold mb-1 text-slate-800">{{ Auth::user()->name }}</h5>
                            <p class="text-slate-500">{{ Auth::user()->email }}</p>
                            
                            <div class="d-flex justify-content-center gap-2 mb-3">
                                <span class="badge bg-success-100 text-success-600 px-3 py-2">
                                    <i class="fas fa-check-circle me-1"></i> Active
                                </span>
                                @if(Auth::user()->roles->isNotEmpty())
                                <span class="badge bg-primary-100 text-primary-600 px-3 py-2">
                                        <i class="fas fa-user-shield me-1"></i> {{ Auth::user()->roles->first()->name }}
                                </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="border-top pt-3">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                                    <span class="text-slate-500">
                                        <i class="fas fa-calendar-check me-2 text-primary-500"></i> Joined
                                    </span>
                                    <span class="fw-medium text-slate-700">{{ Auth::user()->created_at->format('M d, Y') }}</span>
                                </li>
                                <li class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                                    <span class="text-slate-500">
                                        <i class="fas fa-clock me-2 text-primary-500"></i> Last Updated
                                    </span>
                                    <span class="fw-medium text-slate-700">{{ Auth::user()->updated_at->format('M d, Y') }}</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="mt-4">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Tabs and Forms -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="personal-tab" data-bs-toggle="tab" href="#personal" role="tab">
                                    <i class="fas fa-user me-2"></i> Personal Information
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="security-tab" data-bs-toggle="tab" href="#security" role="tab">
                                    <i class="fas fa-lock me-2"></i> Change Password
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="tab-content" id="profileTabsContent">
                            <!-- Personal Information Tab -->
                            <div class="tab-pane fade show active" id="personal" role="tabpanel">
                                <form id="profile-form" class="needs-validation" novalidate>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">Full Name</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-user text-muted"></i>
                                                </span>
                                                <input type="text" class="form-control" id="name" value="{{ Auth::user()->name }}" required>
                                                <div class="invalid-feedback">
                                                    Please provide your full name.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email Address</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-envelope text-muted"></i>
                                                </span>
                                                <input type="email" class="form-control" id="email" value="{{ Auth::user()->email }}" required>
                                                <div class="invalid-feedback">
                                                    Please provide a valid email address.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-phone text-muted"></i>
                                                </span>
                                                <input type="text" class="form-control" id="phone" placeholder="+251 91 234 5678">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="position" class="form-label">Position</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-user-tie text-muted"></i>
                                                </span>
                                                <input type="text" class="form-control" id="position" value="{{ Auth::user()->position ?? '' }}" placeholder="e.g. Store Manager">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="reset" class="btn btn-secondary me-2">
                                            <i class="fas fa-undo me-1"></i> Reset
                                        </button>
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="fas fa-save me-1"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Security Tab -->
                            <div class="tab-pane fade" id="security" role="tabpanel">
                                <form id="password-form" class="needs-validation" novalidate>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <div>
                                                    Your password should be at least 8 characters and include a mix of letters, numbers, and special characters.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-lock text-muted"></i>
                                                </span>
                                                <input type="password" class="form-control" id="current_password" required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <div class="invalid-feedback">
                                                    Please enter your current password.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-key text-muted"></i>
                                                </span>
                                                <input type="password" class="form-control" id="new_password" required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <div class="invalid-feedback">
                                                    Please enter a new password.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="confirm_password" class="form-label">Confirm Password</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-key text-muted"></i>
                                                </span>
                                                <input type="password" class="form-control" id="confirm_password" required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <div class="invalid-feedback">
                                                    Passwords must match.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="fas fa-lock me-1"></i> Update Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Bootstrap Toast markup -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
        <div id="profileSuccessToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    Your changes have been saved successfully!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        // Form validation
        (() => {
            'use strict';
            
            // Fetch all forms we want to apply validation styles to
            const forms = document.querySelectorAll('.needs-validation');
            
            // Loop over them and prevent submission
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    } else {
                        event.preventDefault();
                        // Show Bootstrap toast instead of alert
                        const toastEl = document.getElementById('profileSuccessToast');
                        if (toastEl) {
                            const toast = new bootstrap.Toast(toastEl);
                            toast.show();
                        }
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
            
            // Toggle password visibility
            const toggleButtons = document.querySelectorAll('.toggle-password');
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
        })();
    </script>
    @endpush
@endsection