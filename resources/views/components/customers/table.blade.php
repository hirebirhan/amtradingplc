@props([
    'customers' => [],
    'sortField' => 'name',
    'sortDirection' => 'asc',
    'search' => '',
    'typeFilter' => '',
    'branchFilter' => '',
    'statusFilter' => ''
])

<!-- Desktop Table View -->
<div class="table-responsive d-none d-lg-block">
    <table class="table table-hover table-nowrap">
        <thead class="table-light">
            <tr>
                <th scope="col" class="small">
                    <a href="#" wire:click.prevent="sortBy('name')">
                        Customer
                        <i class="bi bi-arrow-down-up small ms-1"></i>
                    </a>
                </th>
                <th scope="col" class="small">
                    <a href="#" wire:click.prevent="sortBy('type')">
                        Type
                        <i class="bi bi-arrow-down-up small ms-1"></i>
                    </a>
                </th>
                <th scope="col" class="small">Branch</th>
                <th scope="col" class="small text-center">
                    <a href="#" wire:click.prevent="sortBy('is_active')">
                        Status
                        <i class="bi bi-arrow-down-up small ms-1"></i>
                    </a>
                </th>
                <th scope="col" class="small">Joined</th>
                <th scope="col" class="text-end small">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr wire:key="desktop-{{ $customer->id }}">
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="d-flex flex-column">
                                <span class="fw-semibold">{{ $customer->name }}</span>
                                <span class="text-secondary small">{{ $customer->email }}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-primary-subtle border border-primary-subtle text-primary-emphasis rounded-pill">
                            {{ ucfirst($customer->type) }}
                        </span>
                    </td>
                    <td>{{ $customer->branch->name ?? 'N/A' }}</td>
                    <td class="text-center">
                        @if ($customer->is_active)
                            <span class="badge bg-success-subtle border border-success-subtle text-success-emphasis rounded-pill">
                                Active
                            </span>
                        @else
                            <span class="badge bg-danger-subtle border border-danger-subtle text-danger-emphasis rounded-pill">
                                Inactive
                            </span>
                        @endif
                    </td>
                    <td>{{ $customer->created_at->format('d M, Y') }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-sm btn-outline-secondary me-1">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <button 
                            type="button" 
                            class="btn btn-sm btn-outline-danger" 
                            wire:click="$dispatch('showDeleteModal', { customerId: {{ $customer->id }}, customerName: '{{ $customer->name }}' })"
                            title="Delete Customer">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="text-center py-5">
                            <i class="bi bi-person-slash fs-2 text-secondary"></i>
                            <h6 class="mt-3">No customers found</h6>
                            <p class="text-secondary small">Try adjusting your search or filters.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile Card View -->
<div class="d-lg-none">
    @forelse($customers as $customer)
        <div class="p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="flex-grow-1">
                    <span class="fw-semibold text-body">{{ $customer->name }}</span>
                </div>
                <span class="badge bg-primary-subtle border border-primary-subtle text-primary-emphasis rounded-pill">
                    {{ ucfirst($customer->type) }}
                </span>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-secondary small">
                    {{ $customer->email ?? 'No email' }}
                </div>
                @if ($customer->is_active)
                    <span class="badge bg-success-subtle border border-success-subtle text-success-emphasis rounded-pill">
                        Active
                    </span>
                @else
                    <span class="badge bg-danger-subtle border border-danger-subtle text-danger-emphasis rounded-pill">
                        Inactive
                    </span>
                @endif
            </div>

            <div class="d-flex align-items-center justify-content-between">
                <div class="text-secondary small">
                    <i class="bi bi-geo-alt me-1"></i>
                    {{ $customer->branch->name ?? 'N/A' }}
                </div>

                <div class="d-flex gap-1">
                    <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                    <button 
                        type="button" 
                        class="btn btn-sm btn-outline-danger" 
                        wire:click="$dispatch('showDeleteModal', { customerId: {{ $customer->id }}, customerName: '{{ $customer->name }}' })"
                        title="Delete Customer">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5">
            <i class="bi bi-person-slash fs-2 text-secondary"></i>
            <h6 class="mt-3">No customers found</h6>
            <p class="text-secondary small">Try adjusting your search or filters.</p>
        </div>
    @endforelse
</div>

<!-- Pagination -->
@if($customers->hasPages())
    <div class="p-4 border-top">
        {{ $customers->links() }}
    </div>
@endif 