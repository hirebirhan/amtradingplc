@props([
    'total_sales' => 0,
    'total_revenue' => 0,
    'total_purchases' => 0,
    'total_purchase_amount' => 0,
    'can_view_revenue' => false,
    'can_view_purchases' => false,
])
<div class="row g-3 mb-4">
    <!-- Sales Card -->
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded text-primary icon-container">
                        <i class="bi bi-cart"></i>
                    </div>
                    <div class="min-w-0 flex-fill">
                        <div class="text-truncate mb-1">Sales</div>
                        <p class="fw-semibold mb-0 h5">{{ number_format($total_sales ?? 0) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Card -->
    @if(isset($can_view_revenue) && $can_view_revenue)
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded text-success icon-container">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="min-w-0 flex-fill">
                        <div class="text-truncate mb-1">Revenue</div>
                        <p class="fw-semibold mb-0 h5">
                            @php
                                $revenue = $total_revenue ?? 0;
                                if ($revenue >= 1000000) {
                                    echo number_format($revenue / 1000000, 1) . 'M';
                                } elseif ($revenue >= 1000) {
                                    echo number_format($revenue / 1000, 1) . 'K';
                                } else {
                                    echo number_format($revenue, 0);
                                }
                            @endphp
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Purchases Card -->
    @if(isset($can_view_purchases) && $can_view_purchases)
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded text-info icon-container">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="min-w-0 flex-fill">
                        <div class="text-truncate mb-1">Purchases</div>
                        <p class="fw-semibold mb-0 h5">{{ number_format($total_purchases ?? 0) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Purchase Amount Card -->
    @if(isset($can_view_purchases) && $can_view_purchases)
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded text-warning icon-container">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="min-w-0 flex-fill">
                        <div class="text-truncate mb-1">Purchase Amount</div>
                        <p class="fw-semibold mb-0 h5">
                            @php
                                $purchaseAmount = $total_purchase_amount ?? 0;
                                if ($purchaseAmount >= 1000000) {
                                    echo number_format($purchaseAmount / 1000000, 1) . 'M';
                                } elseif ($purchaseAmount >= 1000) {
                                    echo number_format($purchaseAmount / 1000, 1) . 'K';
                                } else {
                                    echo number_format($purchaseAmount, 0);
                                }
                            @endphp
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div> 