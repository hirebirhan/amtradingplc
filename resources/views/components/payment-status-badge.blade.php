@props(['status', 'size' => 'sm'])

@php
    $paymentEnum = \App\Enums\PaymentStatus::from($status);
    $sizeClass = $size === 'lg' ? 'px-3 py-2' : 'px-2 py-1';
    $iconClass = match($paymentEnum) {
        \App\Enums\PaymentStatus::PAID => 'bi-check-circle-fill',
        \App\Enums\PaymentStatus::PARTIAL => 'bi-clock-fill',
        \App\Enums\PaymentStatus::PENDING => 'bi-hourglass-split',
        \App\Enums\PaymentStatus::DUE => 'bi-exclamation-circle-fill',
    };
    $badgeClass = match($paymentEnum) {
        \App\Enums\PaymentStatus::PAID => 'bg-success-subtle text-success-emphasis',
        \App\Enums\PaymentStatus::PARTIAL => 'bg-warning-subtle text-warning-emphasis',
        \App\Enums\PaymentStatus::PENDING => 'bg-info-subtle text-info-emphasis',
        \App\Enums\PaymentStatus::DUE => 'bg-danger-subtle text-danger-emphasis',
    };
@endphp

<span class="badge {{ $badgeClass }} {{ $sizeClass }} rounded-1 fw-medium">
    <i class="bi {{ $iconClass }} me-1"></i>
    {{ $paymentEnum->label() }}
</span>