@props([
    'type' => 'button',
    'variant' => 'primary', // primary, secondary, success, danger, warning, info, light, dark, link
    'size' => '', // sm, lg, empty string for default
    'icon' => null,
    'iconPosition' => 'left', // left, right
    'outline' => false,
    'block' => false,
    'rounded' => false,
    'disabled' => false,
    'loading' => false,
    'loadingText' => 'Loading...',
    'tooltip' => null,
    'tooltipPlacement' => 'top', // top, bottom, left, right
])

@php
    // Button classes
    $btnClass = 'btn';
    $btnClass .= $outline ? ' btn-outline-' . $variant : ' btn-' . $variant;
    $btnClass .= $size ? ' btn-' . $size : '';
    $btnClass .= $block ? ' w-100' : '';
    $btnClass .= $rounded ? ' rounded-pill' : '';
    
    // Handle tooltip
    $tooltipAttrs = $tooltip ? 'data-bs-toggle="tooltip" data-bs-placement="' . $tooltipPlacement . '" title="' . $tooltip . '"' : '';
    
    // Handle loading state
    $loadingClass = $loading ? 'position-relative' : '';
    $disabled = $disabled || $loading;
@endphp

<button 
    type="{{ $type }}" 
    {{ $attributes->merge(['class' => $btnClass . ' ' . $loadingClass]) }}
    {{ $disabled ? 'disabled' : '' }}
    {!! $tooltipAttrs !!}
>
    @if($loading)
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        <span class="visually-hidden">{{ $loadingText }}</span>
    @endif
    
    <span class="{{ $loading ? 'opacity-50' : '' }}">
        @if($icon && $iconPosition === 'left')
            <i class="bi {{ $icon }} {{ $slot->isEmpty() ? '' : 'me-2' }}"></i>
        @endif
        
        {{ $slot }}
        
        @if($icon && $iconPosition === 'right')
            <i class="bi {{ $icon }} {{ $slot->isEmpty() ? '' : 'ms-2' }}"></i>
        @endif
    </span>
</button>

@once
    @push('scripts')
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Reinitialize tooltips when Livewire updates the DOM
            document.addEventListener('livewire:initialized', () => {
                Livewire.hook('morph.updated', ({ el }) => {
                    var tooltipTriggerList = [].slice.call(el.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                });
            });
        });
    </script>
    @endpush
@endonce 