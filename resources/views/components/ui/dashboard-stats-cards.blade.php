@props([
    'total_sales' => 0,
    'total_revenue' => 0,
    'total_purchases' => 0,
    'total_purchase_amount' => 0,
    'total_inventory_value' => 0,
    'customers_count' => 0,
    'can_view_revenue' => false,
    'can_view_purchases' => false,
    'can_view_inventory' => false,
    'is_sales' => false,
    'is_admin_or_manager' => false,
])

@php
    function formatNumber($value, $includeSymbol = false) {
        try {
            $value = is_numeric($value) ? (float)$value : 0;
            $symbol = $includeSymbol ? '$' : '';
            if ($value >= 1000000) {
                return $symbol . number_format($value / 1000000, 1) . 'M';
            } elseif ($value >= 1000) {
                return $symbol . number_format($value / 1000, 1) . 'K';
            } else {
                return $symbol . number_format($value, 0);
            }
        } catch (Exception $e) {
            return $includeSymbol ? '$0' : '0';
        }
    }
    
    function safeNumberFormat($value) {
        try {
            return number_format(is_numeric($value) ? (float)$value : 0);
        } catch (Exception $e) {
            return '0';
        }
    }
    
    $colClass = ($is_admin_or_manager || $is_sales) ? 'col-md-6 col-xl-3' : 'col-md-6 col-xl-4';
@endphp
<div class="row g-3 mb-4">
    <!-- Sales Card -->
    <div class="{{ $colClass }}">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded text-primary icon-container">
                        <i class="bi bi-cart"></i>
                    </div>
                    <div class="min-w-0 flex-fill">
                        <div class="text-truncate mb-1">Sales</div>
                        <p class="fw-semibold mb-0 h5">{{ safeNumberFormat($total_sales ?? 0) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($is_admin_or_manager)
    <div class="{{ $colClass }}">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded text-warning icon-container">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="min-w-0 flex-fill">
                        <div class="text-truncate mb-1">Customers</div>
                        <p class="fw-semibold mb-0 h5">{{ safeNumberFormat($customers_count ?? 0) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Revenue Card -->
    @if($can_view_revenue)
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded text-success icon-container">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="min-w-0 flex-fill">
                        <div class="text-truncate mb-1">Revenue</div>
                        <p class="fw-semibold mb-0 h5">
                            {{ formatNumber($total_revenue ?? 0) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Inventory Value Card -->
    @if($can_view_inventory)
    <div class="{{ $colClass }}">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded text-warning icon-container">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <div class="min-w-0 flex-fill">
                        <div class="text-truncate mb-1">Inventory Value</div>
                        <p class="fw-semibold mb-0 h5">
                            {{ formatNumber($total_inventory_value ?? 0, true) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Purchases Card -->
    @if($can_view_purchases)
    <div class="{{ $colClass }}">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded text-info icon-container">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="min-w-0 flex-fill">
                        <div class="text-truncate mb-1">Purchases</div>
                        <p class="fw-semibold mb-0 h5">{{ safeNumberFormat($total_purchases ?? 0) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Purchase Amount Card -->
    @if($can_view_purchases)
    <div class="{{ $colClass }}">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded text-warning icon-container">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="min-w-0 flex-fill">
                        <div class="text-truncate mb-1">Purchase Amount</div>
                        <p class="fw-semibold mb-0 h5">
                            {{ formatNumber($total_purchase_amount ?? 0) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div> 