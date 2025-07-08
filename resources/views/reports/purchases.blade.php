<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Purchase Reports') }}
            </h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                <button class="btn btn-outline-primary" id="exportBtn">
                    <i class="fas fa-file-export me-1"></i> Export
                </button>
            </div>
        </div>
    </x-slot>

    <div class="container-fluid px-0">
        <!-- Filters Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-filter text-primary me-2"></i>
                    {{ __('Filter Options') }}
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.reports.purchases') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="date_from" class="form-label">Date From</label>
                                <div class="input-group">
                                    <input type="text" id="date_from" name="date_from" value="{{ $date_from ?? now()->subDays(30)->format('Y-m-d') }}" 
                                        class="form-control datepicker" placeholder="Select date">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="date_to" class="form-label">Date To</label>
                                <div class="input-group">
                                    <input type="text" id="date_to" name="date_to" value="{{ $date_to ?? now()->format('Y-m-d') }}" 
                                        class="form-control datepicker" placeholder="Select date">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">Supplier</label>
                                <select class="form-select" id="supplier_id" name="supplier_id">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="warehouse_id" class="form-label">Warehouse</label>
                                <select class="form-select" id="warehouse_id" name="warehouse_id">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                            {{ $warehouse->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary mt-3">
                                <i class="fas fa-filter me-1"></i> Apply Filters
                            </button>
                            <a href="{{ route('admin.reports.purchases') }}" class="btn btn-secondary mt-3">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stats Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center py-2 px-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3 d-flex align-items-center justify-content-center w-40 h-40">
                            <i class="fas fa-shopping-cart text-primary"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-0">Total Purchases</div>
                            <h6 class="stats-value fw-semibold mb-0 text-body">{{ number_format($total_purchases, 2) }}</h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center py-2 px-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3 d-flex align-items-center justify-content-center w-40 h-40">
                            <i class="fas fa-file-invoice text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-0">Purchase Count</div>
                            <h6 class="stats-value fw-semibold mb-0 text-body">{{ $purchases_count }}</h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center py-2 px-3">
                        <div class="rounded-circle bg-info bg-opacity-10 p-2 me-3 d-flex align-items-center justify-content-center w-40 h-40">
                            <i class="fas fa-cubes text-info"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-0">Items Purchased</div>
                            <h6 class="stats-value fw-semibold mb-0 text-body">{{ $total_items_purchased }}</h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center py-2 px-3">
                        <div class="rounded-circle bg-dark bg-opacity-10 p-2 me-3 d-flex align-items-center justify-content-center w-40 h-40">
                            <i class="fas fa-calculator text-dark"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-0">Avg. Purchase</div>
                            <h6 class="stats-value fw-semibold mb-0 text-body">{{ $purchases_count > 0 ? number_format($total_purchases / $purchases_count, 2) : 0 }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top Purchased Items -->
            <div class="col-md-5 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="fas fa-star text-warning me-2"></i>
                            {{ __('Top Purchased Items') }}
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Item</th>
                                        <th class="text-center">Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($top_items as $item)
                                        <tr>
                                            <td class="ps-3">{{ $item->name }}</td>
                                            <td class="text-center">{{ $item->total_quantity }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center py-4">
                                                <span class="text-muted">No data available</span>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Purchase Methods Distribution -->
            <div class="col-md-7 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="fas fa-chart-pie text-primary me-2"></i>
                            {{ __('Purchase Distribution') }}
                        </h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div id="purchaseDistributionChart" class="w-100 h-250"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchases Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-list text-primary me-2"></i>
                    {{ __('Purchase Transactions') }}
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="purchasesTable">
                                                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Supplier</th>
                                <th>Warehouse</th>
                                <th class="text-center">Items</th>
                                <th class="text-end">Total</th>
                                <th>Method</th>
                                <th class="pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $purchase)
                                <tr>
                                    <td>
                                        <a href="{{ route('admawwaqsavas2c1d`sin.purchases.show', $purchase) }}" class="text-primary fw-medium">
                                            {{ $purchase->reference_no }}
                                        </a>
                                    </td>
                                    <td>{{ $purchase->supplier->name ?? 'N/A' }}</td>
                                    <td>{{ $purchase->warehouse->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $purchase->purchaseItems->count() }}</td>
                                    <td class="text-end fw-medium">{{ number_format($purchase->total_amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ ucfirst(str_replace('_', ' ', $purchase->payment_method)) }}
                                        </span>
                                    </td>
                                    <td class="pe-3">
                                        @if($purchase->status === 'completed' || $purchase->status === 'received')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($purchase->status === 'pending' || $purchase->status === 'ordered')
wwin.purchases.show', $purchase) }}" class="text-primary fw-medium">                              <span class="badge bg-warning">Pending</span>
                                        @elseif($purchase->status === 'cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($purchase->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                            <h6 class="fw-bold">No Purchases Found</h6>
                                            <p class="text-muted">No purchases match your search criteria</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        $(document).ready(function() {
            // Initialize datepicker
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });
            
            // Initialize select2
            $('.form-select').select2({
                theme: 'bootstrap-5'
            });
            
            // Initialize chart
            const paymentMethods = {!! json_encode($payment_methods ?? []) !!};
            if(paymentMethods && Object.keys(paymentMethods).length > 0) {
                const options = {
                    series: Object.values(paymentMethods),
                    chart: {
                        type: 'donut',
                        height: 250
                    },
                    labels: Object.keys(paymentMethods).map(method => method.replace('_', ' ')),
                    colors: ['var(--bs-primary)', 'var(--bs-success)', 'var(--bs-warning)', 'var(--bs-danger)', 'var(--bs-info)'],
                    legend: {
                        position: 'bottom'
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 300
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };

                const chart = new ApexCharts(document.querySelector("#purchaseDistributionChart"), options);
                chart.render();
            }

            // Export functionality
            $('#exportBtn').click(function() {
                // Basic CSV export function
                let table = document.getElementById('purchasesTable');
                let rows = table.querySelectorAll('tr');
                let csv = [];
                
                for (let i = 0; i < rows.length; i++) {
                    let row = [], cols = rows[i].querySelectorAll('td, th');
                    
                    for (let j = 0; j < cols.length; j++) {
                        // Get the text content and handle commas and quotes
                        let data = cols[j].textContent.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                        data = data.replace(/"/g, '""');
                        row.push('"' + data + '"');
                    }
                    csv.push(row.join(','));
                }
                
                let csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
                let encodedUri = encodeURI(csvContent);
                let link = document.createElement('a');
                link.setAttribute('href', encodedUri);
                link.setAttribute('download', 'purchase_report.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>
    @endpush
</x-app-layout> 