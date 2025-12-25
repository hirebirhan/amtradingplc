<x-app-layout>
    <div class="min-vh-100">
        <!-- Header Section -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0">{{ $page_title ?? 'Dashboard' }}</h4>
                <p class="text-secondary mb-0 small">{{ $page_description ?? 'Welcome back, ' . auth()->user()->name . '!' }}</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <!-- Time Range Selector -->
                <div class="position-relative" x-data="{ open: false }">
                    <button @click="open = !open" class="btn btn-outline-secondary d-flex align-items-center gap-2 px-3 py-2 small">
                        <i class="bi bi-calendar3"></i>
                        <span id="selectedRange">Last 30 Days</span>
                        <i class="bi bi-chevron-down small"></i>
                    </button>
                    <div x-show="open" @click.away="open = false" 
                         class="position-absolute end-0 mt-2 bg-body-tertiary rounded shadow-lg border dropdown-menu show py-1">
                        <a href="#" class="dropdown-item small" data-range="today">Today</a>
                        <a href="#" class="dropdown-item small" data-range="yesterday">Yesterday</a>
                        <a href="#" class="dropdown-item small" data-range="week">Last 7 Days</a>
                        <a href="#" class="dropdown-item small active" data-range="month">Last 30 Days</a>
                        <a href="#" class="dropdown-item small" data-range="this_month">This Month</a>
                        <hr class="dropdown-divider">
                        <a href="#" class="dropdown-item small" data-range="year">This Year</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="py-2">
            <!-- Error State -->
            @if(isset($error))
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <div class="flex-shrink-0 me-3">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>{{ $error }}</div>
                </div>
            @endif

            <!-- Stats Grid -->
            <div class="row g-3 mb-4">
                <x-ui.dashboard-stats-cards
                    :total_sales="$total_sales"
                    :total_revenue="$total_revenue"
                    :total_purchases="$total_purchases"
                    :total_purchase_amount="$total_purchase_amount"
                    :total_inventory_value="$total_inventory_value ?? 0"
                    :customers_count="$customers_count ?? 0"
                    :can_view_revenue="$can_view_revenue"
                    :can_view_purchases="$can_view_purchases"
                    :can_view_inventory="$can_view_inventory"
                    :is_sales="$is_sales ?? false"
                    :is_admin_or_manager="$is_admin_or_manager ?? false"
                />
            </div>

            <!-- Charts and Activity Section -->
            <div class="row g-4">
                <!-- Sales Chart -->
                <div class="col-12 col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-2 p-md-4">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div>
                                    <h3 class="h5 fw-semibold mb-1">
                                        {{ $is_sales ? 'My Sales' : 'Sales Overview' }}
                                    </h3>
                                    <p class="small text-secondary mb-0">
                                        {{ $is_sales ? 'Track your sales performance' : 'Track sales and purchase trends' }}
                                    </p>
                                </div>
                                <div class="d-flex align-items-center gap-3 small">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary" style="width: 1rem; height: 1rem;"></div>
                                        <span class="fw-medium text-muted">Sales</span>
                                    </div>
                                    @if($can_view_purchases)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-success" style="width: 1rem; height: 1rem;"></div>
                                        <span class="fw-medium text-muted">Purchases</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div id="chartContainer" class="w-100" style="min-height: 250px;">
                                <canvas id="salesChart" class="w-100" style="max-width:100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Feed -->
                <div class="col-12 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-2 p-md-4">
                            <div class="mb-4">
                                <h3 class="h5 fw-semibold mb-1">
                                    {{ $is_sales ? 'My Recent Activity' : 'Recent Activity' }}
                                </h3>
                                <p class="small text-secondary mb-0">
                                    {{ $is_sales ? 'Your latest transactions' : 'Latest inventory movements' }}
                                </p>
                            </div>
                            
                            <div>
                                @forelse($activities ?? [] as $activity)
                                    <div class="d-flex align-items-start gap-3 p-3 mb-2 border rounded flex-wrap">
                                        <div class="flex-shrink-0">
                                            @if($activity->quantity_change > 0)
                                                <div class="d-flex align-items-center justify-content-center rounded text-success icon-container">
                                                    <i class="bi bi-arrow-up"></i>
                                                </div>
                                            @else
                                                <div class="d-flex align-items-center justify-content-center rounded text-danger icon-container">
                                                    <i class="bi bi-arrow-down"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-fill min-w-0 overflow-hidden text-break">
                                            <div class="d-flex align-items-start gap-2 mb-1">
                                                <p class="small fw-semibold mb-0 text-truncate text-body flex-fill min-w-0">
                                                    {{ $activity->description }}
                                                </p>
                                                <span class="badge fw-medium flex-shrink-0 {{ $activity->quantity_change > 0 ? 'text-success border border-success' : 'text-danger border border-danger' }}">
                                                    {{ $activity->quantity_change > 0 ? '+' : '' }}{{ $activity->quantity_change }}
                                                </span>
                                            </div>
                                            <p class="small mb-0 text-truncate text-secondary">
                                                <span class="fw-medium">{{ $activity->item?->name ?? 'N/A' }}</span>
                                            </p>
                                            <div class="d-flex align-items-center justify-content-between mt-1">
                                                <p class="mb-0 text-truncate text-muted small">
                                                    {{ $activity->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-5">
                                        <div class="d-flex align-items-center justify-content-center mx-auto mb-3 rounded icon-container" style="width: 4rem; height: 4rem;">
                                            <i class="bi bi-clock-history fs-4 text-muted"></i>
                                        </div>
                                        <h4 class="h6 fw-medium mb-2">No Recent Activity</h4>
                                        <p class="small mb-0 text-secondary">Activity will appear here as transactions occur.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvasElement = document.getElementById('salesChart');
            if (!canvasElement) {
                return;
            }
            
            const ctx = canvasElement.getContext('2d');
            if (!ctx) {
                return;
            }
            
            let salesChart;

            function getThemeColors() {
                const style = getComputedStyle(document.documentElement);
                return {
                    primary: style.getPropertyValue('--bs-primary').trim() || 'rgb(13, 110, 253)',
                    success: style.getPropertyValue('--bs-success').trim() || 'rgb(25, 135, 84)',
                    info: style.getPropertyValue('--bs-info').trim() || 'rgb(13, 202, 240)',
                    warning: style.getPropertyValue('--bs-warning').trim() || 'rgb(255, 193, 7)',
                    danger: style.getPropertyValue('--bs-danger').trim() || 'rgb(220, 53, 69)',
                    textSecondary: style.getPropertyValue('--bs-secondary').trim() || 'rgb(108, 117, 125)',
                    borderColor: style.getPropertyValue('--bs-border-color').trim() || 'rgb(222, 226, 230)',
                    bgSurface: style.getPropertyValue('--bs-body-bg').trim() || 'rgb(255, 255, 255)'
                };
            }

            function rgbToRgba(rgb, alpha) {
                const result = rgb.match(/\d+/g);
                if (!result) return rgb;
                return `rgba(${result[0]}, ${result[1]}, ${result[2]}, ${alpha})`;
            }

            function updateChart(range) {
                const selectedRangeElement = document.getElementById('selectedRange');
                const activeLink = document.querySelector(`[data-range="${range}"]`);
                if (activeLink && selectedRangeElement) {
                    selectedRangeElement.textContent = activeLink.textContent;
                    
                    document.querySelectorAll('[data-range]').forEach(link => {
                        link.classList.remove('active');
                        link.style.backgroundColor = '';
                        link.style.color = '';
                    });
                    activeLink.classList.add('active');
                    activeLink.style.backgroundColor = 'var(--hover-bg)';
                }

                const chartContainer = document.getElementById('chartContainer');
                const noDataMessage = document.getElementById('noDataMessage');
                
                if (chartContainer) {
                chartContainer.style.opacity = '0.5';
                }

                const chartDataUrl = '{{ route("admin.dashboard.chart-data", "RANGE_PLACEHOLDER") }}'.replace('RANGE_PLACEHOLDER', range);
                
                fetch(chartDataUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                    .then(data => {
                    const salesTotal = (data.sales || []).reduce((a, b) => Number(a) + Number(b), 0);
                    const purchasesTotal = (data.purchases || []).reduce((a, b) => Number(a) + Number(b), 0);
                    
                    if (chartContainer) {
                        chartContainer.style.opacity = '1';
                    }
                    
                        if (salesChart) {
                            salesChart.destroy();
                        }
                        
                        const hasData = data.labels && data.labels.length > 0 && 
                                   (salesTotal > 0 || (purchasesTotal > 0 && @json($can_view_purchases)));
                        
                        if (hasData) {
                        if (chartContainer) {
                            chartContainer.classList.remove('d-none');
                        }
                        const noDataMessage = document.getElementById('noDataMessage');
                        if (noDataMessage) {
                            noDataMessage.classList.add('d-none');
                        }
                        
                        const colors = getThemeColors();
                        const showSales = salesTotal > 0;
                        const showPurchases = purchasesTotal > 0 && @json($can_view_purchases);
                        
                            salesChart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                labels: data.labels || [],
                                    datasets: [{
                                    label: `Sales${!showSales ? ' (No Data)' : ` (${new Intl.NumberFormat().format(salesTotal)})`}`,
                                    data: data.sales || [],
                                    borderColor: colors.primary,
                                    backgroundColor: rgbToRgba(colors.primary, 0.12),
                                        borderWidth: 2,
                                        tension: 0.4,
                                        fill: true,
                                    pointBackgroundColor: colors.primary,
                                    pointBorderColor: colors.bgSurface,
                                        pointBorderWidth: 2,
                                        pointRadius: 4,
                                    pointHoverRadius: 6,
                                    hidden: !showSales
                                    }, {
                                    label: `Purchases${!showPurchases ? ' (No Data)' : ` (${new Intl.NumberFormat().format(purchasesTotal)})`}`,
                                    data: data.purchases || [],
                                    borderColor: colors.success,
                                    backgroundColor: rgbToRgba(colors.success, 0.12),
                                        borderWidth: 2,
                                        tension: 0.4,
                                        fill: true,
                                    pointBackgroundColor: colors.success,
                                    pointBorderColor: colors.bgSurface,
                                        pointBorderWidth: 2,
                                        pointRadius: 4,
                                    pointHoverRadius: 6,
                                    hidden: !showPurchases
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        },
                                    title: {
                                        display: !showSales || !showPurchases,
                                        text: !showSales && showPurchases ? 'Purchases Only (No Sales Data)' :
                                              showSales && !showPurchases ? 'Sales Only (No Purchases Data)' : '',
                                        color: colors.textSecondary,
                                        font: { size: 12 },
                                        padding: { bottom: 20 }
                                    },
                                        tooltip: {
                                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                            titleColor: colors.bgSurface,
                                            bodyColor: colors.bgSurface,
                                            cornerRadius: 8,
                                        displayColors: true,
                                        callbacks: {
                                            label: function(context) {
                                                const value = new Intl.NumberFormat().format(context.parsed.y);
                                                return `${context.dataset.label}: ${value}`;
                                            }
                                        }
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            grid: {
                                            color: colors.borderColor
                                            },
                                            ticks: {
                                            color: colors.textSecondary,
                                            callback: function(value) {
                                                return new Intl.NumberFormat().format(value);
                                            }
                                            }
                                        },
                                        x: {
                                            grid: {
                                                display: false
                                            },
                                            ticks: {
                                            color: colors.textSecondary
                                            }
                                        }
                                    },
                                    interaction: {
                                        intersect: false,
                                        mode: 'index'
                                    }
                                }
                            });
                        
                        } else {
                        if (chartContainer) {
                            chartContainer.classList.add('d-none');
                        }
                        const noDataMessage = document.getElementById('noDataMessage');
                        if (noDataMessage) {
                            noDataMessage.classList.remove('d-none');
                        }
                        }
                    })
                    .catch(error => {
                        console.error('Chart data failed:', error);
                        if (chartContainer) {
                            chartContainer.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-exclamation-triangle fs-1 mb-3 d-block"></i><h5>Chart unavailable</h5></div>';
                        }
                    });
            }

            document.querySelectorAll('[data-range]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const range = this.getAttribute('data-range');
                    updateChart(range);
                    
                    document.querySelectorAll('[data-range]').forEach(el => {
                        el.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    document.getElementById('selectedRange').textContent = this.textContent.trim();
                });
            });
            
            updateChart('month');
        });
    </script>
    @endpush
</x-app-layout>
