<div>
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Employees</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Manage employee information, track roles and departments, and monitor employment status
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <a href="{{ route('admin.employees.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                <span class="d-none d-sm-inline">Add Employee</span>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header border-bottom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-people text-primary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Employees</h5>
                        <small class="text-body-secondary">Manage your workforce</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.employees.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Add Employee
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($employees->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th class="py-3 px-4 fw-semibold border-0">Employee</th>
                                <th class="py-3 px-4 fw-semibold border-0">Position</th>
                                <th class="py-3 px-4 fw-semibold border-0">Department</th>
                                <th class="py-3 px-4 fw-semibold border-0">Contact</th>
                                <th class="py-3 px-4 fw-semibold border-0">Status</th>
                                <th class="py-3 px-4 fw-semibold border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $employee)
                                <tr>
                                    <td class="py-3 px-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <i class="bi bi-person text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $employee->name }}</div>
                                                <small class="text-body-secondary">ID: {{ $employee->employee_id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">
                                            {{ $employee->position }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($employee->department)
                                            <span class="badge bg-info bg-opacity-10 text-info px-3 py-2">
                                                {{ $employee->department->name }}
                                            </span>
                                        @else
                                            <small class="text-body-secondary">No department</small>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <div>
                                            <div class="fw-medium">{{ $employee->phone }}</div>
                                            <small class="text-body-secondary">{{ $employee->email }}</small>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($employee->is_active)
                                            <span class="badge bg-success bg-opacity-15 text-success px-3 py-2">
                                                Active
                                            </span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-15 text-danger px-3 py-2">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-end">
                                        <div class="btn-group">
                                            <a href="{{ route('admin.employees.show', $employee) }}" class="btn btn-sm btn-outline-info" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete" wire:click="delete({{ $employee->id }})" wire:confirm="Are you sure you want to delete this employee?">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-people text-secondary fs-3"></i>
                    </div>
                    <h6 class="fw-bold mb-2">No Employees Found</h6>
                    <p class="text-body-secondary mb-0">No employees match your search criteria</p>
                </div>
            @endif
        </div>
        <div class="card-footer">
            @if($employees->hasPages())
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-body-secondary small">
                        Showing {{ $employees->firstItem() }} to {{ $employees->lastItem() }} of {{ $employees->total() }} employees
                    </div>
                    <div>
                        {{ $employees->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div> 