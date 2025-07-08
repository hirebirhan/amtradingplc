{{-- Modern Customer Details Page --}}
<div>
    <!-- Modern Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <h1 class="h4 mb-0 fw-semibold">Customer Details</h1>
                <div class="d-flex gap-2">
                    <span class="badge bg-{{ $customer->customer_type === 'retail' ? 'blue' : 'purple' }}-subtle 
                                     text-{{ $customer->customer_type === 'retail' ? 'blue' : 'purple' }}-emphasis">
                        <i class="bi bi-tag me-1"></i>{{ ucfirst($customer->customer_type) }}
                </span>
                @if($customer->is_active)
                        <span class="badge bg-success-subtle text-success-emphasis">
                            <i class="bi bi-check-circle me-1"></i>Active
                        </span>
                @else
                        <span class="badge bg-danger-subtle text-danger-emphasis">
                            <i class="bi bi-x-circle me-1"></i>Inactive
                        </span>
                @endif
                </div>
            </div>
            <p class="text-secondary mb-0">{{ $customer->name }}</p>
        </div>
        <div class="d-flex gap-2">
            @can('customers.edit')
                <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-primary btn-sm d-flex align-items-center gap-2">
                    <i class="bi bi-pencil"></i>
                    <span>Edit Customer</span>
            </a>
            @endcan
            <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>Back to List</span>
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small">Total Orders</span>
                        <i class="bi bi-cart text-primary opacity-50 fs-4"></i>
                        </div>
                    <h3 class="mb-0">{{ number_format($stats['total_sales']) }}</h3>
                    <div class="small text-secondary">Lifetime purchases</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small">Total Spent</span>
                        <i class="bi bi-cash text-success opacity-50 fs-4"></i>
                    </div>
                    <h3 class="mb-0">ETB {{ number_format($stats['total_spent']) }}</h3>
                    <div class="small text-secondary">All time revenue</div>
                </div>
                        </div>
                    </div>
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small">Average Order</span>
                        <i class="bi bi-graph-up text-info opacity-50 fs-4"></i>
                    </div>
                    <h3 class="mb-0">ETB {{ number_format($stats['average_sale']) }}</h3>
                    <div class="small text-secondary">Per transaction</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small">Credit Status</span>
                        <i class="bi bi-credit-card text-{{ $customer->balance > $customer->credit_limit ? 'danger' : 'success' }} opacity-50 fs-4"></i>
                    </div>
                    <h3 class="mb-0 text-{{ $customer->balance > $customer->credit_limit ? 'danger' : 'success' }}">
                        {{ $customer->balance > $customer->credit_limit ? 'Over Limit' : 'Good Standing' }}
                    </h3>
                    <div class="small text-secondary">Current balance: ETB {{ number_format($customer->balance) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Customer Information -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-person text-primary"></i>
                        Contact Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        @if($customer->email)
                            <div>
                                <div class="d-flex align-items-center gap-2 text-secondary small mb-1">
                                    <i class="bi bi-envelope"></i>
                                    <span>Email Address</span>
                                </div>
                                <div class="fw-medium">{{ $customer->email }}</div>
                            </div>
                        @endif

                        @if($customer->phone)
                            <div>
                                <div class="d-flex align-items-center gap-2 text-secondary small mb-1">
                                    <i class="bi bi-telephone"></i>
                                    <span>Phone Number</span>
                                </div>
                                <div class="fw-medium">{{ $customer->phone }}</div>
                            </div>
                        @endif

                        @if($customer->address || $customer->city)
                            <div>
                                <div class="d-flex align-items-center gap-2 text-secondary small mb-1">
                                    <i class="bi bi-geo-alt"></i>
                                    <span>Address</span>
                                </div>
                                <div class="fw-medium">
                                    @if($customer->address)
                                        <div>{{ $customer->address }}</div>
                                    @endif
                                    @if($customer->city)
                                        <div class="text-secondary">{{ $customer->city }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($customer->branch)
                            <div>
                                <div class="d-flex align-items-center gap-2 text-secondary small mb-1">
                                    <i class="bi bi-building"></i>
                                    <span>Branch</span>
                                </div>
                                <div class="fw-medium">{{ $customer->branch->name }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Information -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-wallet2 text-success"></i>
                        Financial Details
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-4">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-secondary small">Credit Limit</span>
                                <span class="badge bg-primary-subtle text-primary-emphasis">
                                    ETB {{ number_format($customer->credit_limit) }}
                                </span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                @php
                                    $percentage = $customer->credit_limit > 0 ? ($customer->balance / $customer->credit_limit) * 100 : 0;
                                @endphp
                                <div class="progress-bar bg-{{ $percentage > 100 ? 'danger' : 'success' }}" 
                                     role="progressbar" 
                                     style="width: {{ min($percentage, 100) }}%" 
                                     aria-valuenow="{{ $percentage }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="small text-secondary">Current Balance</span>
                                <span class="fw-medium text-{{ $percentage > 100 ? 'danger' : 'success' }}">
                                    ETB {{ number_format($customer->balance) }}
                                </span>
                            </div>
                        </div>

                        <div class="border-top pt-3">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="text-secondary small mb-1">Last Purchase</div>
                                    <div class="fw-medium">
                                        {{ $sales->first() ? $sales->first()->sale_date->format('M d, Y') : 'No purchases yet' }}
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-secondary small mb-1">Payment Terms</div>
                                    <div class="fw-medium">{{ ucfirst($customer->customer_type) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-info"></i>
                        Additional Information
                    </h6>
                </div>
                <div class="card-body">
                    @if($customer->notes)
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 text-secondary small mb-2">
                                <i class="bi bi-sticky"></i>
                                <span>Notes</span>
                            </div>
                            <div class="bg-light rounded p-3">
                                {{ $customer->notes }}
                            </div>
                        </div>
                    @endif

                    <div>
                        <div class="d-flex align-items-center gap-2 text-secondary small mb-2">
                            <i class="bi bi-clock-history"></i>
                            <span>Account Details</span>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary small">Created</span>
                                <span class="fw-medium">{{ $customer->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary small">Last Updated</span>
                                <span class="fw-medium">{{ $customer->updated_at->format('M d, Y') }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary small">Status</span>
                                <span class="badge bg-{{ $customer->is_active ? 'success' : 'danger' }}-subtle 
                                             text-{{ $customer->is_active ? 'success' : 'danger' }}-emphasis">
                                    {{ $customer->is_active ? 'Active' : 'Inactive' }}
                                </span>
            </div>
        </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sales -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-receipt text-primary"></i>
                            Recent Sales History
                        </h6>
                        @if(count($sales) > 0)
                            <a href="#" class="btn btn-outline-primary btn-sm">View All Sales</a>
                        @endif
                    </div>
        </div>

        <div class="card-body p-0">
            @if(count($sales) > 0)
                <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                            <tr>
                                        <th class="border-0 text-secondary small">Date</th>
                                        <th class="border-0 text-secondary small">Reference</th>
                                        <th class="border-0 text-secondary small">Items</th>
                                        <th class="border-0 text-secondary small">Total</th>
                                        <th class="border-0 text-secondary small">Status</th>
                                        <th class="border-0 text-secondary small text-end">Actions</th>
                            </tr>
                        </thead>
                                <tbody class="border-top-0">
                            @foreach($sales as $sale)
                                        <tr>
                                            <td class="small">{{ $sale->sale_date->format('M d, Y') }}</td>
                                            <td>
                                                <span class="fw-medium">{{ $sale->reference_no }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                                    {{ $sale->items->count() }} items
                                                </span>
                                            </td>
                                            <td class="fw-medium">ETB {{ number_format($sale->total_amount, 2) }}</td>
                                            <td>
                                                <span class="badge bg-{{ 
                                                    $sale->status === 'completed' ? 'success' : 
                                                    ($sale->status === 'pending' ? 'warning' : 'danger') 
                                                }}-subtle text-{{ 
                                                    $sale->status === 'completed' ? 'success' : 
                                                    ($sale->status === 'pending' ? 'warning' : 'danger') 
                                                }}-emphasis">
                                                    <i class="bi bi-{{ 
                                                        $sale->status === 'completed' ? 'check-circle' : 
                                                        ($sale->status === 'pending' ? 'clock' : 'x-circle') 
                                                    }} me-1"></i>
                                            {{ ucfirst($sale->status) }}
                                        </span>
                                    </td>
                                            <td class="text-end">
                                                <a href="#" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-receipt text-secondary" style="font-size: 2rem;"></i>
                            </div>
                            <h6 class="fw-medium text-secondary mb-1">No Sales History</h6>
                            <p class="text-secondary small mb-0">This customer hasn't made any purchases yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
