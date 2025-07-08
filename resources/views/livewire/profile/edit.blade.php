@php use Illuminate\Support\Facades\Storage; @endphp
<div>
    @script
    <script>
        $wire.on('notify', (event) => {
            const notification = event[0] || event;
            
            // Create notification element
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${notification.type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 1060; min-width: 300px;';
            alertDiv.innerHTML = `
                <strong>${notification.title || 'Notification'}</strong> ${notification.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Add to body
            document.body.appendChild(alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        });
    </script>
    @endscript
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1 fw-semibold">Profile Settings</h1>
            <p class="text-muted mb-0">Manage your account settings and preferences</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="row g-4">
        <!-- Profile Sidebar -->
        <div class="col-lg-4 mb-4 mb-lg-0">
            <div class="card border-0 shadow-sm h-100 text-center text-lg-start">
                <div class="card-body p-4">
                    <div class="mb-4">
                        <div class="position-relative mx-auto mb-3" style="width: 120px;">
                            <div class="bg-primary bg-opacity-10 rounded-circle mx-auto d-flex justify-content-center align-items-center" 
                                 style="width: 120px; height: 120px">
                                @if($user->avatar)
                                    <img src="{{ Storage::url($user->avatar) }}" alt="Avatar" class="rounded-circle w-100 h-100 object-fit-cover">
                                @else
                                    <span class="display-4 text-primary">{{ substr($user->name, 0, 1) }}</span>
                                @endif
                            </div>
                            <label class="btn btn-sm btn-primary position-absolute bottom-0 end-0 translate-middle p-2 border border-2 border-white rounded-circle" style="cursor:pointer;">
                                <i class="fas fa-camera"></i>
                                <input type="file" wire:model="avatar" class="d-none" accept="image/*">
                            </label>
                        </div>
                        @error('avatar') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        
                        <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                        <p class="text-muted mb-2">{{ $user->email }}</p>
                        
                        <div class="d-flex justify-content-center gap-2">
                            <span class="badge bg-success-100 text-success-600 px-3 py-2">
                                <i class="fas fa-check-circle me-1"></i> Active
                            </span>
                            @if($user->roles->isNotEmpty())
                                <span class="badge bg-primary-100 text-primary-600 px-3 py-2">
                                    <i class="fas fa-user-shield me-1"></i> {{ $user->roles->first()->name }}
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="border-top pt-3">
                        <ul class="list-group list-group-flush">
                            @if($branch_name)
                            <li class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted">
                                    <i class="fas fa-map-marker-alt me-2 text-primary-500"></i> Branch
                                </span>
                                <span class="fw-medium">{{ $branch_name }}</span>
                            </li>
                            @endif
                            @if($warehouse_name)
                            <li class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted">
                                    <i class="fas fa-warehouse me-2 text-primary-500"></i> Warehouse
                                </span>
                                <span class="fw-medium">{{ $warehouse_name }}</span>
                            </li>
                            @endif
                            <li class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted">
                                    <i class="fas fa-calendar-check me-2 text-primary-500"></i> Joined
                                </span>
                                <span class="fw-medium">{{ $user->created_at->format('M d, Y') }}</span>
                            </li>
                            <li class="list-group-item border-0 px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted">
                                    <i class="fas fa-clock me-2 text-primary-500"></i> Last Updated
                                </span>
                                <span class="fw-medium">{{ $user->updated_at->format('M d, Y') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Forms -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-3 border-bottom">
                    <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="personal-tab" data-bs-toggle="tab" href="#personal" role="tab">
                                <i class="fas fa-user me-2"></i> Personal Information
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="security-tab" data-bs-toggle="tab" href="#security" role="tab">
                                <i class="fas fa-lock me-2"></i> Security
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body p-3 p-md-4">
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Personal Information Tab -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel">
                            <form wire:submit.prevent="saveProfile" class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user text-muted"></i>
                                        </span>
                                        <input type="text" wire:model.defer="name" class="form-control @error('name') is-invalid @enderror">
                                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope text-muted"></i>
                                        </span>
                                        <input type="email" wire:model.defer="email" class="form-control @error('email') is-invalid @enderror">
                                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone text-muted"></i>
                                        </span>
                                        <input type="text" wire:model.defer="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="+251 91 234 5678">
                                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                
                                <div class="col-12 {{ ($branch_name || $warehouse_name) ? 'col-md-6' : '' }}">
                                    <label class="form-label">Position</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user-tie text-muted"></i>
                                        </span>
                                        <input type="text" wire:model.defer="position" class="form-control @error('position') is-invalid @enderror" placeholder="e.g. Store Manager">
                                        @error('position') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                @if($branch_name)
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Assigned Branch</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-map-marker-alt text-muted"></i>
                                        </span>
                                                                    <input type="text" value="{{ $branch_name }}" class="form-control" readonly>
                            <span class="input-group-text bg-transparent border-start-0">
                                            <i class="fas fa-lock text-muted small"></i>
                                        </span>
                                    </div>
                                    <small class="form-text text-muted">Your assigned branch (contact admin to change)</small>
                                </div>
                                @endif

                                @if($warehouse_name)
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Assigned Warehouse</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-warehouse text-muted"></i>
                                        </span>
                                                                    <input type="text" value="{{ $warehouse_name }}" class="form-control" readonly>
                            <span class="input-group-text bg-transparent border-start-0">
                                            <i class="fas fa-lock text-muted small"></i>
                                        </span>
                                    </div>
                                    <small class="form-text text-muted">Your assigned warehouse (contact admin to change)</small>
                                </div>
                                @endif
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fas fa-save me-1"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <form wire:submit.prevent="changePassword" class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock text-muted"></i>
                                        </span>
                                        <input type="password" wire:model.defer="current_password" class="form-control @error('current_password') is-invalid @enderror">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-key text-muted"></i>
                                        </span>
                                        <input type="password" wire:model.defer="new_password" class="form-control @error('new_password') is-invalid @enderror">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @error('new_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-key text-muted"></i>
                                        </span>
                                        <input type="password" wire:model.defer="new_password_confirmation" class="form-control">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Password must be at least 8 characters long and include uppercase, lowercase, number and special character.
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
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

@push('scripts')
<script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
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
</script>
@endpush 