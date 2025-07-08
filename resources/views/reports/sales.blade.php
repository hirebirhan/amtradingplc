<x-app-layout>
    <div class="container-fluid py-4">
        <!-- Modern Page Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
            <div class="mb-3 mb-md-0">
                <h1 class="h3 fw-bold mb-2">Sales Reports</h1>
                <div class="d-flex align-items-center gap-3 small text-body-secondary">
                    <span>Revenue analysis and sales performance</span>
                    @if(auth()->user()->branch_id)
                        <span class="mx-2">|</span>
                        <span class="badge bg-body-secondary text-body border fw-normal">{{ auth()->user()->branch->name }}</span>
                    @elseif(auth()->user()->warehouse_id)
                        <span class="mx-2">|</span>
                        <span class="badge bg-body-secondary text-body border fw-normal">{{ auth()->user()->warehouse->name }}</span>
                    @elseif(auth()->user()->hasRole(['SystemAdmin', 'Manager']))
                        <span class="mx-2">|</span>
                        <span class="badge bg-body-secondary text-body border fw-normal">All Locations</span>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <button class="btn btn-outline-secondary" onclick="window.print()">
                    Print Report
                </button>
                <button class="btn btn-primary" id="exportBtn">
                    Export CSV
                </button>
            </div>
        </div>

        <!-- Modern Filters -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header border-0 py-3">
                <h5 class="mb-0 fw-normal">Filters</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.reports.sales') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small text-body-secondary mb-1">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                value="{{ $date_from ?? now()->subDays(30)->format('Y-m-d') }}">
                        </div>
                        
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small text-body-secondary mb-1">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                value="{{ $date_to ?? now()->format('Y-m-d') }}">
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small text-body-secondary mb-1">Customer</label>
                            <select class="form-select" id="customer_id" name="customer_id">
                                <option value="">All Customers</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if(auth()->user()->hasRole(['SystemAdmin', 'Manager']) || count($warehouses) > 1)
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label small text-body-secondary mb-1">Location</label>
                            <select class="form-select" id="warehouse_id" name="warehouse_id">
                                <option value="">{{ count($warehouses) > 1 ? 'All Locations' : ($warehouses->first()->name ?? 'No locations') }}</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="col-12 d-flex gap-2 justify-content-end">
                            <a href="{{ route('admin.reports.sales') }}" class="btn btn-outline-secondary">Reset</a>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modern Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body text-center py-4">
                        <div class="text-body-secondary mb-2">
                            <i class="fas fa-dollar-sign fs-4"></i>
                        </div>
                        <div class="h4 fw-bold mb-1">ETB {{ number_format($total_sales, 2) }}</div>
                        <div class="text-body-secondary small">Total Revenue</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body text-center py-4">
                        <div class="text-body-secondary mb-2">
                            <i class="fas fa-shopping-bag fs-4"></i>
                        </div>
                        <div class="h4 fw-bold mb-1">{{ number_format($sales_count) }}</div>
                        <div class="text-body-secondary small">Total Sales</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body text-center py-4">
                        <div class="text-body-secondary mb-2">
                            <i class="fas fa-cubes fs-4"></i>
                        </div>
                        <div class="h4 fw-bold mb-1">{{ number_format($total_items_sold) }}</div>
                        <div class="text-body-secondary small">Items Sold</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body text-center py-4">
                        <div class="text-body-secondary mb-2">
                            <i class="fas fa-calculator fs-4"></i>
                        </div>
                        <div class="h4 fw-bold mb-1">${{ $sales_count > 0 ? number_format($total_sales / $sales_count, 2) : '0.00' }}</div>
                        <div class="text-body-secondary small">Average Sale</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row g-4 mb-4">
            <!-- Top Selling Items -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header border-0 py-3">
                        <h5 class="mb-0 fw-normal">Top Selling Items</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="fw-normal">Item</th>
                                        <th class="fw-normal text-center">Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($top_items as $item)
                                        <tr>
                                            <td>{{ $item->name }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-1">
                                                    {{ number_format($item->total_quantity) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="py-5 text-center">
                                                <div class="text-body-secondary">
                                                    <div class="mb-2">No data available</div>
                                                    <small>No sales in selected period</small>
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
            
            <!-- Sales Distribution Chart -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header border-0 py-3">
                        <h5 class="mb-0 fw-normal">Sales Distribution</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center min-vh-30">
                        @if(isset($payment_methods) && count($payment_methods) > 0)
                            <div id="salesDistributionChart" class="w-100 h-280"></div>
                        @else
                            <div class="text-center text-body-secondary">
                                <div class="mb-2">No chart data</div>
                                <small>No sales data to display</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Transactions Table -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-normal">Sales Transactions</h5>
                    <div class="text-body-secondary small">
                        {{ $sales->count() }} transactions
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0" id="salesTable">
                        <thead>
                            <tr>
                                <th class="fw-normal">Date</th>
                                <th class="fw-normal">Reference</th>
                                <th class="fw-normal">Customer</th>
                                <th class="fw-normal">Location</th>
                                <th class="fw-normal text-center">Items</th>
                                <th class="fw-normal text-end">Total</th>
                                <th class="fw-normal">Payment</th>
                                <th class="fw-normal">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sales as $sale)
                                <tr>
                                    <td>
                                        <div class="fw-normal">{{ $sale->sale_date->format('M d, Y') }}</div>
                                        <small class="text-body-secondary">{{ $sale->sale_date->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.sales.show', $sale) }}" class="text-body text-decoration-none">
                                            {{ $sale->reference_no }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-normal">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                                        @if($sale->customer && $sale->customer->phone)
                                            <small class="text-body-secondary">{{ $sale->customer->phone }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($sale->warehouse)
                                            <div class="small">{{ $sale->warehouse->name }}</div>
                                        @elseif($sale->branch)
                                            <div class="small">{{ $sale->branch->name }}</div>
                                        @else
                                            <small class="text-body-secondary">No location</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ $sale->saleItems->count() }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold">ETB {{ number_format($sale->total_amount, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-1">
                                            {{ ucfirst(str_replace('_', ' ', $sale->payment_method ?? 'cash')) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $status = \App\Enums\PaymentStatus::tryFrom($sale->payment_status);
                                        @endphp
                                        @if($status)
                                            <span class="badge bg-{{ $status->color() }}-subtle text-{{ $status->color() }}-emphasis rounded-1">
                                                {{ $status->label() }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-1">
                                                {{ ucfirst($sale->payment_status) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-5 text-center">
                                        <div class="text-body-secondary">
                                            <div class="mb-2">No sales found</div>
                                            <small>No sales match your search criteria</small>
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
            // Initialize chart if data exists
            @if(isset($payment_methods) && count($payment_methods) > 0)
            const paymentMethods = {!! json_encode($payment_methods) !!};
            if(paymentMethods && Object.keys(paymentMethods).length > 0) {
                const options = {
                    series: Object.values(paymentMethods),
                    chart: {
                        type: 'donut',
                        height: 280,
                        fontFamily: 'inherit'
                    },
                    labels: Object.keys(paymentMethods).map(method => 
                        method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
                    ),
                    colors: ['var(--bs-primary)', 'var(--bs-success)', 'var(--bs-warning)', 'var(--bs-danger)', 'var(--bs-info)'],
                    legend: {
                        position: 'bottom',
                        fontSize: '14px',
                        fontWeight: 400
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '60%'
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return val.toFixed(0) + "%"
                        },
                        style: {
                            fontSize: '12px',
                            fontWeight: 500
                        }
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

                const chart = new ApexCharts(document.querySelector("#salesDistributionChart"), options);
                chart.render();
            }
            @endif

            // Enhanced export functionality
            $('#exportBtn').click(function() {
                const button = $(this);
                const originalText = button.html();
                
                // Show loading state
                button.html('Exporting...');
                button.prop('disabled', true);
                
                // Get table data
                let table = document.getElementById('salesTable');
                let rows = table.querySelectorAll('tr');
                let csv = [];
                
                for (let i = 0; i < rows.length; i++) {
                    let row = [], cols = rows[i].querySelectorAll('td, th');
                    
                    for (let j = 0; j < cols.length; j++) {
                        // Clean up the text content
                        let data = cols[j].textContent.replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
                        data = data.replace(/"/g, '""');
                        row.push('"' + data + '"');
                    }
                    csv.push(row.join(','));
                }
                
                // Create and download file
                let csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
                let encodedUri = encodeURI(csvContent);
                let link = document.createElement('a');
                link.setAttribute('href', encodedUri);
                link.setAttribute('download', `sales_report_${new Date().toISOString().split('T')[0]}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Reset button state
                setTimeout(() => {
                    button.html(originalText);
                    button.prop('disabled', false);
                }, 1000);
            });
        });
    </script>
    @endpush
</x-app-layout> 