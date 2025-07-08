@props([
    'route' => null,
    'icon' => null,
    'variant' => 'outline-secondary',
    'size' => 'md',
    'pill' => true
])

@if($route)
    <a 
        href="{{ $route }}" 
        {{ $attributes->merge(['class' => 'btn btn-' . $variant . ' btn-' . $size . ($pill ? ' rounded-pill' : '') . ' shadow-sm hover-lift']) }}
    >
        @if($icon)
            <i class="fas {{ $icon }} me-2"></i>
        @endif
        {{ $slot }}
    </a>
@else
    <button 
        {{ $attributes->merge(['class' => 'btn btn-' . $variant . ' btn-' . $size . ($pill ? ' rounded-pill' : '') . ' shadow-sm hover-lift', 'type' => 'button']) }}
    >
        @if($icon)
            <i class="fas {{ $icon }} me-2"></i>
        @endif
        {{ $slot }}
    </button>
@endif

<style>
.hover-lift {
    transition: all 0.2s ease-in-out;
}
.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.08) !important;
}
</style> 