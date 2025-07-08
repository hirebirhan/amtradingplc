<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $employee->first_name }} {{ $employee->last_name }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.employees.index') }}" class="text-primary">Employees</a></li>
                    <li class="breadcrumb-item active text-muted" aria-current="page">Profile</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> Edit Profile
            </a>
            <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left column - Employee profile card -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="bg-primary text-white shadow-sm rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" 
                             style="width: 110px; height: 110px; font-size: 2.8rem;">
                            {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                        </div>
                        <h3 class="card-title fw-bold mb-1">{{ $employee->first_name }} {{ $employee->last_name }}</h3>
                        <p class="card-text mb-1 text-primary">{{ $employee->position }}</p>
                        <p class="card-text text-muted small">{{ $employee->department }}</p>
                        
                        <div class="d-flex justify-content-center my-3">
                            @if ($employee->status === 'active')
                                <span class="badge bg-success-100 text-success-600 py-2 px-3 rounded-pill">
                                    <i class="fas fa-circle me-1 fa-xs"></i> Active
                                </span>
                            @elseif ($employee->status === 'inactive')
                                <span class="badge bg-danger-100 text-danger-600 py-2 px-3 rounded-pill">
                                    <i class="fas fa-circle me-1 fa-xs"></i> Inactive
                                </span>
                            @elseif ($employee->status === 'on_leave')
                                <span class="badge bg-warning-100 text-warning-600 py-2 px-3 rounded-pill">
                                    <i class="fas fa-circle me-1 fa-xs"></i> On Leave
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="contact-info mt-4">
                        <h6 class="fw-bold text-uppercase small text-muted mb-3">Contact Information</h6>
                        <ul class="list-unstyled">
                            @if ($employee->phone)
                                <li class="d-flex align-items-center mb-3">
                                    <div class="bg-primary-100 rounded-circle p-2 me-3">
                                        <i class="fas fa-phone-alt text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Phone</div>
                                        <div>{{ $employee->phone }}</div>
                                    </div>
                                </li>
                            @endif
                            
                            @if ($employee->email)
                                <li class="d-flex align-items-center mb-3">
                                    <div class="bg-primary-100 rounded-circle p-2 me-3">
                                        <i class="fas fa-envelope text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Email</div>
                                        <div class="text-break">{{ $employee->email }}</div>
                                    </div>
                                </li>
                            @endif
                            
                            @if ($employee->employee_id)
                                <li class="d-flex align-items-center mb-3">
                                    <div class="bg-primary-100 rounded-circle p-2 me-3">
                                        <i class="fas fa-id-card text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Employee ID</div>
                                        <div>{{ $employee->employee_id }}</div>
                                    </div>
                                </li>
                            @endif
                            
                            @if ($employee->birth_date)
                                <li class="d-flex align-items-center mb-3">
                                    <div class="bg-primary-100 rounded-circle p-2 me-3">
                                        <i class="fas fa-birthday-cake text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Birth Date</div>
                                        <div>{{ $employee->birth_date ? $employee->birth_date->format('F d, Y') : 'Not specified' }}</div>
                                    </div>
                                </li>
                            @endif
                            
                            @if ($employee->hire_date)
                                <li class="d-flex align-items-center">
                                    <div class="bg-primary-100 rounded-circle p-2 me-3">
                                        <i class="fas fa-calendar-alt text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Hire Date</div>
                                        <div>{{ $employee->hire_date ? $employee->hire_date->format('F d, Y') : 'Not specified' }}</div>
                                    </div>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right column - Employee details -->
        <div class="col-md-8">
            <!-- Employment Details Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary-100 rounded-circle p-2 me-3">
                            <i class="fas fa-briefcase text-primary"></i>
                        </div>
                        <h5 class="card-title mb-0">Employment Details</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded h-100">
                                <h6 class="text-muted small mb-2">Branch</h6>
                                <p class="mb-0 fw-medium">{{ $employee->branch ? $employee->branch->name : 'Not assigned' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded h-100">
                                <h6 class="text-muted small mb-2">User Account</h6>
                                <p class="mb-0 fw-medium">{{ $employee->user ? $employee->user->name : 'Not linked' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Salary Information Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary-100 rounded-circle p-2 me-3">
                            <i class="fas fa-money-bill-wave text-primary"></i>
                        </div>
                        <h5 class="card-title mb-0">Salary Information</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="text-center bg-light p-3 rounded h-100">
                                <h6 class="text-muted small mb-2">Base Salary</h6>
                                <h4 class="fw-bold mb-0">ETB {{ number_format($employee->base_salary, 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center bg-light p-3 rounded h-100">
                                <h6 class="text-muted small mb-2">Allowance</h6>
                                <h4 class="fw-bold mb-0">ETB {{ number_format($employee->allowance ?? 0, 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center bg-success-100 p-3 rounded h-100">
                                <h6 class="text-success-600 small mb-2">Total Salary</h6>
                                <h4 class="fw-bold mb-0 text-success-600">ETB {{ number_format($employee->total_salary, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Card -->
            @if($employee->address)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary-100 rounded-circle p-2 me-3">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                        </div>
                        <h5 class="card-title mb-0">Address</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="bg-light p-3 rounded">
                        {{ $employee->address }}
                    </div>
                </div>
            </div>
            @endif

            <!-- Emergency Contact Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary-100 rounded-circle p-2 me-3">
                            <i class="fas fa-ambulance text-primary"></i>
                        </div>
                        <h5 class="card-title mb-0">Emergency Contact</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded h-100">
                                <h6 class="text-muted small mb-2">Contact Person</h6>
                                <p class="mb-0 fw-medium">{{ $employee->emergency_contact ?? 'Not specified' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded h-100">
                                <h6 class="text-muted small mb-2">Contact Phone</h6>
                                <p class="mb-0 fw-medium">{{ $employee->emergency_phone ?? 'Not specified' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Card -->
            @if($employee->notes)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary-100 rounded-circle p-2 me-3">
                            <i class="fas fa-clipboard-list text-primary"></i>
                        </div>
                        <h5 class="card-title mb-0">Notes</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="bg-light p-3 rounded">
                        {{ $employee->notes }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div> 