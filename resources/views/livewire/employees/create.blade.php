<div>
    <!-- Main Card Container -->
    <div class="card border-0 shadow-sm">
        <!-- Header -->
        <div class="card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Create New Employee</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary mb-0 small">Add a new employee and optionally set up their user account for system access</span>
                    </div>
                </div>
                <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Back to Employees</span>
                </a>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body p-0">
            <!-- Error Alert -->
            @if (session()->has('error'))
                <div class="p-4 pb-0">
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <div class="d-flex">
                            <i class="bi bi-exclamation-triangle me-2 mt-1"></i>
                            <div>
                                <strong>Error!</strong>
                                <p class="mb-0">{{ session('error') }}</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            <!-- Form Content -->
            <form id="employeeForm" wire:submit.prevent="save" class="p-4 pt-0">
                <!-- Basic Information -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h6 class="fw-medium mb-3 text-primary">Basic Information</h6>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="firstName" class="form-label fw-medium">
                            First Name <span class="text-primary">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('firstName') is-invalid @enderror" 
                               id="firstName" 
                               wire:model="firstName" 
                               placeholder="Enter first name">
                        @error('firstName') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="lastName" class="form-label fw-medium">
                            Last Name <span class="text-primary">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('lastName') is-invalid @enderror" 
                               id="lastName" 
                               wire:model="lastName" 
                               placeholder="Enter last name">
                        @error('lastName') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-medium">
                            Email Address <span class="text-primary">*</span>
                        </label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               wire:model="email" 
                               placeholder="employee@company.com">
                        @error('email') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-medium">Phone Number</label>
                        <input type="text" 
                               class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" 
                               wire:model="phone" 
                               placeholder="+251 912 345 678">
                        @error('phone') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>
                </div>

                <!-- Employment Information -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h6 class="fw-medium mb-3 text-primary">Employment Information</h6>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="position" class="form-label fw-medium">
                            Position <span class="text-primary">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('position') is-invalid @enderror" 
                               id="position" 
                               wire:model="position" 
                               placeholder="Enter job position">
                        @error('position') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="department" class="form-label fw-medium">
                            Department <span class="text-primary">*</span>
                        </label>
                        <select class="form-select @error('department') is-invalid @enderror" 
                                id="department" 
                                wire:model="department">
                            <option value="">Select Department</option>
                            @foreach($departments as $name => $label)
                                <option value="{{ $name }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('department') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="branchId" class="form-label fw-medium">Branch</label>
                        <select class="form-select @error('branchId') is-invalid @enderror" 
                                id="branchId" 
                                wire:model="branchId">
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branchId') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                        <div class="form-text small">Select either a branch or warehouse for the employee</div>
                    </div>

                    <div class="col-md-6">
                        <label for="warehouseId" class="form-label fw-medium">Warehouse</label>
                        <select class="form-select @error('warehouseId') is-invalid @enderror" 
                                id="warehouseId" 
                                wire:model="warehouseId">
                            <option value="">Select Warehouse</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                        @error('warehouseId') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                        <div class="form-text small">Select either a branch or warehouse for the employee</div>
                    </div>

                    <div class="col-md-6">
                        <label for="hireDate" class="form-label fw-medium">
                            Hire Date <span class="text-primary">*</span>
                        </label>
                        <input type="date" 
                               class="form-control @error('hireDate') is-invalid @enderror" 
                               id="hireDate" 
                               wire:model="hireDate" 
                               max="{{ now()->format('Y-m-d') }}">
                        @error('hireDate') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                        <div class="form-text small">The date when the employee started or will start working</div>
                    </div>
                </div>

                <!-- Salary Information -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h6 class="fw-medium mb-3 text-primary">Salary Information</h6>
                    </div>

                    <div class="col-md-6">
                        <label for="baseSalary" class="form-label fw-medium">
                            Base Salary <span class="text-primary">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">ETB</span>
                            <input type="number" 
                                   step="0.01" 
                                   class="form-control @error('baseSalary') is-invalid @enderror" 
                                   id="baseSalary" 
                                   wire:model="baseSalary" 
                                   placeholder="Enter base salary">
                        </div>
                        @error('baseSalary') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="allowance" class="form-label fw-medium">Allowance</label>
                        <div class="input-group">
                            <span class="input-group-text">ETB</span>
                            <input type="number" 
                                   step="0.01" 
                                   class="form-control @error('allowance') is-invalid @enderror" 
                                   id="allowance" 
                                   wire:model="allowance" 
                                   placeholder="Enter allowance amount">
                        </div>
                        @error('allowance') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h6 class="fw-medium mb-3 text-primary">Additional Information</h6>
                    </div>

                    <div class="col-md-6">
                        <label for="birthDate" class="form-label fw-medium">Birth Date</label>
                        <input type="date" 
                               class="form-control @error('birthDate') is-invalid @enderror" 
                               id="birthDate" 
                               wire:model="birthDate" 
                               max="{{ now()->format('Y-m-d') }}">
                        @error('birthDate') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="address" class="form-label fw-medium">Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  id="address" 
                                  wire:model="address" 
                                  rows="1" 
                                  placeholder="Enter address"></textarea>
                        @error('address') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="row g-3">
                    <div class="col-12">
                        <h6 class="fw-medium mb-3 text-primary">Emergency Contact</h6>
                    </div>

                    <div class="col-md-6">
                        <label for="emergencyContact" class="form-label fw-medium">Contact Person</label>
                        <input type="text" 
                               class="form-control @error('emergencyContact') is-invalid @enderror" 
                               id="emergencyContact" 
                               wire:model="emergencyContact" 
                               placeholder="Enter emergency contact name">
                        @error('emergencyContact') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="emergencyPhone" class="form-label fw-medium">Emergency Phone</label>
                        <input type="text" 
                               class="form-control @error('emergencyPhone') is-invalid @enderror" 
                               id="emergencyPhone" 
                               wire:model="emergencyPhone" 
                               placeholder="Enter emergency phone number">
                        @error('emergencyPhone') 
                            <div class="invalid-feedback">{{ $message }}</div> 
                        @enderror
                    </div>
                </div>
            </form>
        </div>

        <!-- Card Footer -->
        <div class="card-footer border-top-0 px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </a>
                <button type="submit" 
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        form="employeeForm">
                    <span wire:loading.remove wire:target="save">
                        <i class="bi bi-check-lg me-1"></i>Create Employee
                    </span>
                    <span wire:loading wire:target="save">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                        Creating...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div> 