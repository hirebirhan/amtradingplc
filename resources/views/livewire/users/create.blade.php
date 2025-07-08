{{-- User Creation Form --}}
<div>
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <h1 class="h4 mb-1 fw-semibold text-dark">Create New User</h1>
                    <p class="text-muted mb-0 small">Add a new user to the system</p>
                </div>
            </div>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
            ←
            <span class="d-none d-sm-inline">Back to Users</span>
        </a>
    </div>

    <!-- Form Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-semibold text-dark">User Information</h5>
        </div>

        <div class="card-body p-4">
            <form wire:submit.prevent="create">
                <div class="row g-3">
                    <!-- Name -->
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-medium">
                            Full Name <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            class="form-control @error('form.name') is-invalid @enderror" 
                            wire:model.live.debounce.300ms="form.name"
                            placeholder="Enter user's full name"
                            autofocus
                        >
                        @error('form.name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-medium">
                            Email Address <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            class="form-control @error('form.email') is-invalid @enderror" 
                            wire:model.live.debounce.300ms="form.email"
                            placeholder="user@example.com"
                        >
                        @error('form.email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="col-md-6">
                        <label for="password" class="form-label fw-medium">
                            Password <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            class="form-control @error('form.password') is-invalid @enderror" 
                            wire:model.live.debounce.300ms="form.password"
                            placeholder="Minimum 8 characters"
                        >
                        @error('form.password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password Confirmation -->
                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label fw-medium">
                            Confirm Password <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            class="form-control @error('form.password_confirmation') is-invalid @enderror" 
                            wire:model.live.debounce.300ms="form.password_confirmation"
                            placeholder="Confirm your password"
                        >
                        @error('form.password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-medium">
                            Phone Number
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            class="form-control @error('form.phone') is-invalid @enderror" 
                            wire:model.live.debounce.300ms="form.phone"
                            placeholder="+251 9XX XXX XXX (optional)"
                        >
                        @error('form.phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Position -->
                    <div class="col-md-6">
                        <label for="position" class="form-label fw-medium">
                            Job Position
                        </label>
                        <input 
                            type="text" 
                            id="position" 
                            class="form-control @error('form.position') is-invalid @enderror" 
                            wire:model.live.debounce.300ms="form.position"
                            placeholder="e.g., Sales Manager, Warehouse Supervisor (optional)"
                        >
                        @error('form.position')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Role -->
                    <div class="col-md-12">
                        <label for="role" class="form-label fw-medium">
                            User Role <span class="text-danger">*</span>
                        </label>
                        <select 
                            id="role" 
                            class="form-select @error('form.role') is-invalid @enderror" 
                            wire:model.live="form.role"
                        >
                            <option value="">Select user role and permissions...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role['name'] }}">{{ $role['label'] }} - {{ $role['description'] }}</option>
                            @endforeach
                        </select>
                        @error('form.role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Branch -->
                    <div class="col-md-6">
                        <label for="branch_id" class="form-label fw-medium">
                            Branch
                        </label>
                        <select 
                            id="branch_id" 
                            class="form-select @error('form.branch_id') is-invalid @enderror" 
                            wire:model.live="form.branch_id"
                        >
                            <option value="">Select branch (optional)...</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('form.branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Warehouse -->
                    <div class="col-md-6">
                        <label for="warehouse_id" class="form-label fw-medium">
                            Warehouse
                        </label>
                        <select 
                            id="warehouse_id" 
                            class="form-select @error('form.warehouse_id') is-invalid @enderror" 
                            wire:model.live="form.warehouse_id"
                            @if(empty($availableWarehouses)) disabled @endif
                        >
                            @if(empty($form['branch_id']))
                                <option value="">Select branch first to see warehouses...</option>
                            @else
                                <option value="">Select warehouse (optional)...</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        @error('form.warehouse_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="border-top pt-4 mt-4">
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-end">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button 
                            type="submit" 
                            class="btn btn-primary d-flex align-items-center gap-2"
                            @if($isSubmitting) disabled @endif
                        >
                            @if($isSubmitting)
                                <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
                                Creating...
                            @else
                                <span>Create User</span>
                            @endif
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
