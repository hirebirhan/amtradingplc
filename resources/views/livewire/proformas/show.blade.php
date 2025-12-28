<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                <div>
                    <h1 class="h3 mb-1">Proforma #{{ substr($proforma->reference_no, -8) }}</h1>
                    <p class="text-muted mb-0">{{ $proforma->reference_no }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.proformas.print', $proforma) }}" class="btn btn-primary" target="_blank">
                        <i class="bi bi-printer"></i> Print
                    </a>
                    @can('proformas.edit')
                        <a href="{{ route('admin.proformas.edit', $proforma) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Status Badge -->
            <div class="mb-3">
                <span class="badge bg-{{ $proforma->status === 'approved' ? 'success' : 'warning' }} fs-6 px-3 py-2">
                    <i class="bi bi-{{ $proforma->status === 'approved' ? 'check-circle' : 'clock' }}"></i> {{ ucfirst($proforma->status) }}
                </span>
            </div>

            <!-- Customer Information -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-person"></i> Customer Information</h6>
                </div>
                <div class="card-body">
                    <h5 class="mb-2">{{ $proforma->customer->name }}</h5>
                    <div class="text-muted">
                        @if($proforma->customer->email)
                            <div><i class="bi bi-envelope"></i> {{ $proforma->customer->email }}</div>
                        @else
                            <div><i class="bi bi-envelope"></i> N/A</div>
                        @endif
                        @if($proforma->customer->phone)
                            <div><i class="bi bi-telephone"></i> {{ $proforma->customer->phone }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Proforma Details -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Proforma Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <strong>Date:</strong><br>
                            {{ $proforma->created_at->format('M d, Y') }}
                        </div>
                        <div class="col-6">
                            <strong>Valid Until:</strong><br>
                            {{ $proforma->valid_until ? $proforma->valid_until->format('M d, Y') : 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-list"></i> Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Item</th>
                                    <th width="15%" class="text-end">Unit Price</th>
                                    <th width="10%" class="text-center">Quantity</th>
                                    <th width="15%" class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($proforma->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->item->name }}</td>
                                        <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end">Total:</th>
                                    <th class="text-end">{{ number_format($proforma->total_amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Summary -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-calculator"></i> Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>{{ number_format($proforma->total_amount, 2) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total:</span>
                        <span>{{ number_format($proforma->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Back Button -->
            <div class="d-grid">
                <a href="{{ route('admin.proformas.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="text-muted small text-center">
                Created by {{ $proforma->user->name ?? 'System' }} on {{ $proforma->created_at->format('M d, Y H:i') }}
            </div>
        </div>
    </div>
</div>