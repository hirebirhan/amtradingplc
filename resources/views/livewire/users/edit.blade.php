<div>
    <x-partials.main title="Edit User">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Edit User - {{ $user->name }}</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-info">
                        <i class="fas fa-eye me-1"></i> View Details
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Users
                    </a>
                </div>
            </div>

            <div class="card-body">
                <form wire:submit="save">
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="fw-semibold text-muted mb-3">
                                <i class="fas fa-user me-2"></i>Basic Information
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" class="form-control @error('name') is-invalid @enderror"
                                wire:model="name" autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                wire:model="email">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" id="phone" class="form-control @error('phone') is-invalid @enderror"
                                wire:model="phone">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="position" class="form-label">Position</label>
                            <input type="text" id="position" class="form-control @error('position') is-invalid @enderror"
                                wire:model="position">
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Password Change Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="fw-semibold text-muted mb-3">
                                <i class="fas fa-key me-2"></i>Password Change
                            </h6>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Leave password fields blank to keep the current password.
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" id="password" class="form-control @error('password') is-invalid @enderror"
                                wire:model="password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <input type="password" id="password_confirmation" class="form-control"
                                wire:model="password_confirmation">
                        </div>
                    </div>

                    <!-- Assignment Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="fw-semibold text-muted mb-3">
                                <i class="fas fa-building me-2"></i>Assignment
                            </h6>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                A user can be assigned to either a branch OR a warehouse, but not both. If you select a branch, you can optionally select one of its warehouses.
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="branch_id" class="form-label">Branch</label>
                            <select id="branch_id" class="form-select @error('branch_id') is-invalid @enderror"
                                wire:model.live="branch_id">
                                <option value="">-- Select Branch --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="warehouse_id" class="form-label">Warehouse</label>
                            <select id="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror"
                                wire:model="warehouse_id">
                                <option value="">-- Select Warehouse --</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted">
                                @if($branch_id)
                                    Select a warehouse from the chosen branch, or leave blank for branch-wide access
                                @else
                                    Select a branch first to see available warehouses
                                @endif
                            </div>
                            @error('warehouse_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Roles Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="fw-semibold text-muted mb-3">
                                <i class="fas fa-shield-alt me-2"></i>Roles & Permissions
                            </h6>
                            <label class="form-label">Assign Roles <span class="text-danger">*</span></label>
                            @error('selectedRoles')
                                <div class="text-danger small mb-2">{{ $message }}</div>
                            @enderror
                            
                            <div class="row">
                                @foreach($roles as $role)
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                id="role_{{ $role->id }}" 
                                                value="{{ $role->name }}"
                                                wire:model="selectedRoles">
                                            <label class="form-check-label" for="role_{{ $role->id }}">
                                                <strong>{{ $role->name }}</strong>
                                                @if($role->description)
                                                    <br><small class="text-muted">{{ $role->description }}</small>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Status Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="fw-semibold text-muted mb-3">
                                <i class="fas fa-toggle-on me-2"></i>Status
                            </h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                                    wire:model="is_active">
                                <label class="form-check-label" for="is_active">
                                    <strong>Active User</strong>
                                    <br><small class="text-muted">Inactive users cannot log in to the system</small>
                                </label>
                            </div>
                            
                            @if($user->id === auth()->id())
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    You are editing your own account. Be careful not to remove your own access.
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </x-partials.main>
</div>
