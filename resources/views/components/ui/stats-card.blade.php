@props([
    'title' => 'Title',
    'value' => '0',
    'icon' => 'chart-bar',
    'color' => 'primary'
])

@php
    $colorMap = [
        'primary' => ['bg' => 'bg-primary-subtle', 'icon' => 'text-primary', 'text' => 'text-primary'],
        'success' => ['bg' => 'bg-success-subtle', 'icon' => 'text-success', 'text' => 'text-success'],
        'info' => ['bg' => 'bg-info-subtle', 'icon' => 'text-info', 'text' => 'text-info'],
        'warning' => ['bg' => 'bg-warning-subtle', 'icon' => 'text-warning', 'text' => 'text-warning'],
        'danger' => ['bg' => 'bg-danger-subtle', 'icon' => 'text-danger', 'text' => 'text-danger'],
    ];
    $colors = $colorMap[$color] ?? $colorMap['primary'];
@endphp

<div class="col-6 col-md-4">
    <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="rounded p-2 d-flex align-items-center justify-content-center {{ $colors['bg'] }}" style="width: 32px; height: 32px;">
                            <i class="bi bi-{{ $icon }} {{ $colors['icon'] }} small"></i>
                        </div>
                        <div>
                            <div class="small fw-medium mb-1 text-secondary">{{ $title }}</div>
                            <div class="h6 fw-bold mb-0">{{ $value }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 