<x-app-layout>
    <div class="container-fluid py-4">
        <!-- Modern 2-Row Page Header -->
        <div class="row align-items-center mb-4">
            <div class="col">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-graph-up text-warning fs-4"></i>
                    </div>
                    <div>
                        <h1 class="h3 fw-bold mb-0">Financial Reports</h1>
                        <p class="text-body-secondary mb-0">Comprehensive financial analysis and insights</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2">
                        <i class="bi bi-currency-dollar me-1"></i>ETB {{ number_format($sales, 2) }} Sales
                    </span>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2">
                        <i class="bi bi-cart me-1"></i>ETB {{ number_format($purchases, 2) }} Purchases
                    </span>
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2">
                        <i class="bi bi-receipt me-1"></i>ETB {{ number_format($expenses, 2) }} Expenses
                    </span>
                    <span class="badge bg-{{ ($sales - $purchases - $expenses) >= 0 ? 'success' : 'danger' }} bg-opacity-10 text-{{ ($sales - $purchases - $expenses) >= 0 ? 'success' : 'danger' }} border border-{{ ($sales - $purchases - $expenses) >= 0 ? 'success' : 'danger' }} border-opacity-25 px-3 py-2">
                        <i class="bi bi-cash-stack me-1"></i>ETB {{ number_format($sales - $purchases - $expenses, 2) }} Net Profit
                    </span>
                    @if(auth()->user()->branch_id)
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2">
                            <i class="bi bi-geo-alt me-1"></i>{{ auth()->user()->branch->name }}
                        </span>
                    @elseif(auth()->user()->warehouse_id)
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2">
                            <i class="bi bi-building me-1"></i>{{ auth()->user()->warehouse->name }}
                        </span>
                    @elseif(auth()->user()->hasRole(['SystemAdmin', 'Manager']))
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2">
                            <i class="bi bi-globe me-1"></i>All Locations
                        </span>
                    @endif
                </div>
            </div>
            <div class="col-auto">
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary border-0 shadow-sm" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i>Print
                    </button>
                    <button class="btn btn-primary shadow-sm" id="exportBtn">
                        <i class="bi bi-download me-2"></i>Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-0 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 rounded-2 p-2">
                        <i class="bi bi-funnel text-primary"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Filter Options</h5>
                        <small class="text-body-secondary">Customize your financial analysis period</small>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <form action="{{ route('admin.reports.financial') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label for="date_from" class="form-label fw-semibold">Date From</label>
                            <input type="text" class="form-control border-0 shadow-sm" id="date_from" name="date_from" 
                                   value="{{ $date_from ?? now()->subDays(30)->format('Y-m-d') }}" placeholder="Select start date">
                        </div>
                        <div class="col-lg-4">
                            <label for="date_to" class="form-label fw-semibold">Date To</label>
                            <input type="text" class="form-control border-0 shadow-sm" id="date_to" name="date_to" 
                                   value="{{ $date_to ?? now()->format('Y-m-d') }}" placeholder="Select end date">
                        </div>
                        <div class="col-lg-4">
                            <label for="warehouse_id" class="form-label fw-semibold">
                                Location Filter
                                <i class="bi bi-info-circle text-body-secondary ms-1" data-bs-toggle="tooltip" 
                                   title="Sales & Purchases by warehouse, Expenses by branch"></i>
                            </label>
                            <select class="form-select border-0 shadow-sm" id="warehouse_id" name="warehouse_id">
                                <option value="">All Locations</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                    <i class="bi bi-search me-2"></i>Apply Filters
                                </button>
                                <a href="{{ route('admin.reports.financial') }}" class="btn btn-outline-secondary border-0 shadow-sm px-4">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Revenue Distribution -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 rounded-2 p-2">
                                <i class="bi bi-pie-chart text-primary"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Revenue Distribution</h5>
                                <small class="text-body-secondary">Financial breakdown by category</small>
                            </div>
                        </div>
                    </div>
                                    <div class="card-body d-flex align-items-center justify-content-center min-vh-30">
                    <canvas id="pieChart" class="w-100" style="height: 280px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Outstanding Credits -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-warning bg-opacity-10 rounded-2 p-2">
                                <i class="bi bi-credit-card text-warning"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Outstanding Credits</h5>
                                <small class="text-body-secondary">Pending receivables and payables</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center min-vh-30">
                        <div class="text-center">
                            <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                                <i class="bi bi-cash-stack text-warning fs-1"></i>
                            </div>
                            <h3 class="h1 fw-bold mb-2">ETB {{ number_format($outstanding_credits, 2) }}</h3>
                            <p class="text-body-secondary mb-0">Total outstanding balance</p>
                            @if($outstanding_credits > 0)
                                <div class="mt-3">
                                    <span class="badge bg-warning bg-opacity-15 text-warning px-3 py-2">
                                        <i class="bi bi-clock me-1"></i>Requires attention
                                    </span>
                                </div>
                            @else
                                <div class="mt-3">
                                    <span class="badge bg-success bg-opacity-15 text-success px-3 py-2">
                                        <i class="bi bi-check-circle me-1"></i>All settled
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Trends Chart -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-0 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-info bg-opacity-10 rounded-2 p-2">
                        <i class="bi bi-graph-up text-info"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Monthly Financial Trends</h5>
                        <small class="text-body-secondary">Revenue and expense patterns over time</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="trendChart" class="w-100 min-vh-40"></canvas>
            </div>
        </div>

        <!-- Income vs Expense Comparison -->
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 rounded-2 p-2">
                        <i class="bi bi-bar-chart text-success"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Income vs Expenses</h5>
                        <small class="text-body-secondary">Monthly comparison of income and operational costs</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="barChart" class="w-100 min-vh-40"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize datepicker
            $('#date_from, #date_to').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true,
                showOnFocus: false,
                container: 'body'
            });
            
            // Chart configuration
            const chartConfig = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    }
                }
            };

            // Revenue Distribution Pie Chart
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Sales', 'Purchases', 'Expenses'],
                    datasets: [{
                        data: [{{ $sales }}, {{ $purchases }}, {{ $expenses }}],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(23, 162, 184, 0.8)', 
                            'rgba(255, 193, 7, 0.8)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(23, 162, 184, 1)',
                            'rgba(255, 193, 7, 1)'
                        ],
                        borderWidth: 2,
                        hoverOffset: 8
                    }]
                },
                options: {
                    ...chartConfig,
                    cutout: '60%',
                    plugins: {
                        ...chartConfig.plugins,
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 13,
                                    weight: '500'
                                }
                            }
                        }
                    }
                }
            });

            // Monthly Trends Chart
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: [
                        @foreach($monthly_sales as $item)
                            '{{ date("M Y", mktime(0, 0, 0, $item->month, 1, $item->year)) }}',
                        @endforeach
                    ],
                    datasets: [{
                        label: 'Sales',
                        data: [
                            @foreach($monthly_sales as $item)
                                {{ $item->total }},
                            @endforeach
                        ],
                        borderColor: 'rgba(40, 167, 69, 1)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                        pointBorderColor: 'var(--bs-white)',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    ...chartConfig,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });

            // Income vs Expense Bar Chart
            const barCtx = document.getElementById('barChart').getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: [
                        @foreach($monthly_sales as $item)
                            '{{ date("M Y", mktime(0, 0, 0, $item->month, 1, $item->year)) }}',
                        @endforeach
                    ],
                    datasets: [{
                        label: 'Income (Sales)',
                        data: [
                            @foreach($monthly_sales as $item)
                                {{ $item->total }},
                            @endforeach
                        ],
                        backgroundColor: 'rgba(40, 167, 69, 0.8)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false
                    }, {
                        label: 'Expenses',
                        data: [
                            @foreach($monthly_expenses as $item)
                                {{ $item->total }},
                            @endforeach
                        ],
                        backgroundColor: 'rgba(255, 193, 7, 0.8)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false
                    }]
                },
                options: {
                    ...chartConfig,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });

            // Enhanced export functionality
            $('#exportBtn').click(function() {
                const button = $(this);
                const originalText = button.html();
                
                // Show loading state
                button.html('<i class="bi bi-arrow-clockwise spin me-2"></i>Exporting...');
                button.prop('disabled', true);
                
                const reportData = {
                    reportName: 'Financial Report',
                    dateRange: `From {{ $date_from }} to {{ $date_to }}`,
                    summary: {
                        totalSales: {{ $sales }},
                        totalPurchases: {{ $purchases }},
                        totalExpenses: {{ $expenses }},
                        outstandingCredits: {{ $outstanding_credits }},
                        netProfit: {{ $sales - $purchases - $expenses }}
                    },
                    generatedAt: new Date().toISOString()
                };
                
                const jsonString = JSON.stringify(reportData, null, 2);
                const blob = new Blob([jsonString], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                
                const link = document.createElement('a');
                link.href = url;
                link.download = `financial_report_${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
                
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
