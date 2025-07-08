<x-app-layout>
    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Edit Sale</h1>
                <p class="text-muted mb-0">Modify sale information and details</p>
            </div>
            <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Sales
            </a>
        </div>

        <!-- Current Sale Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-1">Sale #{{ $sale->reference_no }}</h5>
                        <p class="text-muted mb-0">{{ $sale->customer->name }} • {{ $sale->sale_date->format('M d, Y') }}</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="badge bg-{{ $sale->payment_status === 'paid' ? 'success' : ($sale->payment_status === 'partial' ? 'warning' : 'danger') }} fs-6">
                            {{ ucfirst($sale->payment_status) }}
                        </span>
                        <div class="h5 mt-2 mb-0">ETB {{ number_format($sale->total_amount, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhancement Notice -->
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-tools fa-3x text-primary mb-3"></i>
                    <h3 class="h4 fw-bold">Enhancement in Progress</h3>
                    <p class="text-muted mb-4">We're working on improving the sales editing feature with advanced capabilities including:</p>
                </div>
                
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="fas fa-search fa-2x text-info"></i>
                        </div>
                        <h6>Searchable Item Dropdown</h6>
                        <p class="small text-muted">Better item selection from large catalogs</p>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="fas fa-edit fa-2x text-success"></i>
                        </div>
                        <h6>Inline Editing</h6>
                        <p class="small text-muted">Edit quantities and prices directly</p>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="fas fa-history fa-2x text-warning"></i>
                        </div>
                        <h6>Change History</h6>
                        <p class="small text-muted">Track all modifications made to sales</p>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-center">
                    <a href="{{ route('admin.sales.show', $sale) }}" class="btn btn-primary">
                        <i class="fas fa-eye me-2"></i> View Sale Details
                    </a>
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-list me-2"></i> Back to Sales List
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>