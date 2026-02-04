@props(['status', 'size' => 'sm'])

@php
    $statusEnum = \App\Enums\PurchaseStatus::from($status);
    $sizeClass = $size === 'lg' ? 'px-3 py-2' : 'px-2 py-1';
    $iconClass = match($statusEnum) {
        \App\Enums\PurchaseStatus::DRAFT => 'bi-pencil',
        \App\Enums\PurchaseStatus::CONFIRMED => 'bi-clock-history',
        \App\Enums\PurchaseStatus::RECEIVED => 'bi-check-circle-fill',
        \App\Enums\PurchaseStatus::CANCELLED => 'bi-x-circle-fill',
    };
    $badgeClass = match($statusEnum) {
        \App\Enums\PurchaseStatus::DRAFT => 'bg-secondary-subtle text-secondary-emphasis',
        \App\Enums\PurchaseStatus::CONFIRMED => 'bg-primary-subtle text-primary-emphasis',
        \App\Enums\PurchaseStatus::RECEIVED => 'bg-success-subtle text-success-emphasis',
        \App\Enums\PurchaseStatus::CANCELLED => 'bg-danger-subtle text-danger-emphasis',
    };
@endphp

<span class="badge {{ $badgeClass }} {{ $sizeClass }} rounded-1 fw-medium">
    <i class="bi {{ $iconClass }} me-1"></i>
    {{ $statusEnum->label() }}
</span>