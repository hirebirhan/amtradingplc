@props([
    'icon' => 'fa-chart-line',
    'iconClass' => '',
    'label' => 'Stat',
    'value' => '0',
    'variant' => 'primary', // primary, success, info, warning, danger
    'trend' => null, // positive, negative, neutral
    'trendValue' => null,
    'loading' => false,
    'href' => null,
])

@php
    $variantClasses = [
        'primary' => 'bg-primary-50 text-primary-700 border-primary-200',
        'success' => 'bg-success-light text-success border-success/20',
        'info' => 'bg-info-light text-info border-info/20',
        'warning' => 'bg-warning-light text-warning border-warning/20',
        'danger' => 'bg-danger-light text-danger border-danger/20',
    ];
    
    $trendColors = [
        'positive' => 'text-success',
        'negative' => 'text-danger',
        'neutral' => 'text-neutral-500'
    ];
    
    $trendIcons = [
        'positive' => 'fa-arrow-up',
        'negative' => 'fa-arrow-down',
        'neutral' => 'fa-minus'
    ];
    
    $cardClass = $variantClasses[$variant] ?? $variantClasses['primary'];
    $wrapperClass = $href ? 'cursor-pointer hover-shadow-lg transition-all d-block' : '';
@endphp

<div {{ $attributes->merge(['class' => "card border h-100 $cardClass $wrapperClass"]) }} style="overflow: hidden;">
    @if($href)
        <a href="{{ $href }}" class="text-decoration-none h-100 d-flex">
    @endif
    
    <div class="card-body p-2 p-sm-3 d-flex flex-column justify-content-between w-100" style="min-height: 0;">
        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between">
            <div class="w-100 mb-2 mb-sm-0">
                @if($loading)
                    <div class="d-flex align-items-center mb-1">
                        <div class="placeholder-glow w-100">
                            <span class="placeholder col-6"></span>
                        </div>
                    </div>
                    <div class="placeholder-glow">
                        <span class="placeholder col-8 placeholder-lg"></span>
                    </div>
                @else
                    <div class="text-secondary small mb-1 text-truncate">{{ $label }}</div>
                    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between">
                        <div class="fs-6 fs-sm-5 fw-semibold text-truncate stat-value mb-1 mb-sm-0">{{ $value }}</div>
                        
                        @if($trend && $trendValue)
                            <div class="small {{ $trendColors[$trend] ?? '' }} text-nowrap">
                                <i class="fas {{ $trendIcons[$trend] ?? '' }} me-1"></i>
                                <span class="trend-value">{{ $trendValue }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
            
            <div class="d-flex align-items-center justify-content-center rounded p-1 flex-shrink-0 ms-sm-2 align-self-end align-self-sm-center {{ $iconClass ?: "bg-{$variant}/10" }}" style="width: 24px; height: 24px; min-width: 24px;">
                <i class="fas {{ $icon }} fa-xs"></i>
            </div>
        </div>
    </div>
    
    @if($href)
        </a>
    @endif
</div>

<style>
    /* Prevent card content overflow */
    .stat-value {
        word-break: break-word;
        overflow-wrap: break-word;
        hyphens: auto;
        max-width: 100%;
        line-height: 1.2;
    }
    
    .trend-value {
        font-size: 0.85em;
    }
    
    /* Mobile responsive adjustments */
    @media (max-width: 576px) {
        .stat-value {
            font-size: clamp(0.9rem, 4vw, 1.1rem) !important;
        }
        
        .trend-value {
            font-size: 0.75rem;
        }
        
        .card-body {
            min-height: auto !important;
        }
    }
    
    /* Tablet adjustments */
    @media (min-width: 577px) and (max-width: 991px) {
        .stat-value {
            font-size: clamp(1rem, 2.5vw, 1.2rem) !important;
        }
    }
    
    /* Desktop hover effects only */
    @media (min-width: 768px) {
        .hover-shadow-lg:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            transform: translateY(-2px);
        }
    }
    
    /* Ensure proper truncation */
    .text-truncate {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
</style> 