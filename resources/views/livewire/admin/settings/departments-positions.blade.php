<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Departments & Positions</h1>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Departments Section -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Departments</h5>
                </div>
                <div class="list-group list-group-flush">
                    <button wire:click="selectDepartment(null)" 
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $selectedDepartment === null ? 'active' : '' }}">
                        All Departments
                    </button>
                    @foreach ($departments as $name => $label)
                        <button wire:click="selectDepartment('{{ $name }}')" 
                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $selectedDepartment === $name ? 'active' : '' }}">
                            {{ $label }}
                            <span class="badge bg-primary rounded-pill">
                                {{-- This would count positions in each department --}}
                            </span>
                        </button>
                    @endforeach
                </div>
                <div class="card-footer bg-white">
                    <p class="text-muted mb-0 small">
                        <i class="fas fa-info-circle me-1"></i> 
                        Departments are managed through code configuration.
                    </p>
                </div>
            </div>
        </div>

        <!-- Positions Section -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        @if($selectedDepartment)
                            Positions - {{ $departments[$selectedDepartment] }}
                        @else
                            All Positions
                        @endif
                    </h5>
                    <button wire:click="toggleAddPosition" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Add Position
                    </button>
                </div>
                
                @if($isAddingPosition)
                <div class="card-body border-bottom">
                    <h6 class="fw-bold mb-3">Add New Position</h6>
                    <form wire:submit.prevent="addPosition">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="newPositionName" class="form-label">Position Name</label>
                                <input type="text" class="form-control @error('newPositionName') is-invalid @enderror" 
                                    id="newPositionName" wire:model="newPositionName" required>
                                @error('newPositionName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="newPositionDepartment" class="form-label">Department</label>
                                <select class="form-select @error('newPositionDepartment') is-invalid @enderror" 
                                    id="newPositionDepartment" wire:model="newPositionDepartment" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $name => $label)
                                        <option value="{{ $name }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('newPositionDepartment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="button" class="btn btn-outline-secondary me-2" wire:click="toggleAddPosition">
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    Save Position
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                @endif
                
                @if($editingPosition)
                <div class="card-body border-bottom bg-light">
                    <h6 class="fw-bold mb-3">Edit Position</h6>
                    <form wire:submit.prevent="updatePosition">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="editPositionName" class="form-label">Position Name</label>
                                <input type="text" class="form-control @error('editPositionName') is-invalid @enderror" 
                                    id="editPositionName" wire:model="editPositionName" required>
                                @error('editPositionName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="editPositionDepartment" class="form-label">Department</label>
                                <select class="form-select @error('editPositionDepartment') is-invalid @enderror" 
                                    id="editPositionDepartment" wire:model="editPositionDepartment" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $name => $label)
                                        <option value="{{ $name }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('editPositionDepartment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="button" class="btn btn-outline-secondary me-2" wire:click="$set('editingPosition', null)">
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    Update Position
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                @endif
                
                <div class="card-body">
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search positions..." 
                                wire:model.live.debounce.300ms="search">
                            @if($search)
                                <button class="btn btn-outline-secondary" type="button" wire:click="$set('search', '')">
                                    <i class="fas fa-times"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Position Name</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($positions as $position)
                                <tr>
                                    <td>{{ $position->name }}</td>
                                    <td>{{ $departments[$position->department] ?? $position->department }}</td>
                                    <td>
                                        <button wire:click="togglePositionStatus({{ $position->id }})" 
                                            class="badge {{ $position->is_active ? 'bg-success' : 'bg-danger' }} border-0">
                                            {{ $position->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button wire:click="editPosition({{ $position->id }})" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="deletePosition({{ $position->id }})"
                                                wire:confirm="Are you sure you want to delete this position?"
                                                class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        @if($search)
                                            <p class="mb-0">No positions matching "{{ $search }}".</p>
                                        @elseif($selectedDepartment)
                                            <p class="mb-0">No positions found for the selected department.</p>
                                        @else
                                            <p class="mb-0">No positions found. Add your first position!</p>
                                        @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $positions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 