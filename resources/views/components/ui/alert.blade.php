@props([
    'type' => 'info', // primary, secondary, success, danger, warning, info, light, dark
    'icon' => null,
    'title' => null,
    'dismissible' => false,
    'autoDismiss' => false,
    'autoDismissDelay' => 5000,
    'border' => false,
    'borderLeft' => false,
])

@php
    $alertClass = 'alert';
    $alertClass .= ' alert-' . $type;
    $alertClass .= $dismissible ? ' alert-dismissible fade show' : '';
    $alertClass .= $border ? ' border' : '';
    $alertClass .= $borderLeft ? ' border-start border-5' : '';
    
    $iconClass = 'fas';
    if (!$icon) {
        switch ($type) {
            case 'success':
                $iconClass .= ' fa-check-circle';
                break;
            case 'danger':
                $iconClass .= ' fa-exclamation-circle';
                break;
            case 'warning':
                $iconClass .= ' fa-exclamation-triangle';
                break;
            case 'info':
                $iconClass .= ' fa-info-circle';
                break;
            default:
                $iconClass .= ' fa-bell';
        }
    } else {
        $iconClass .= ' ' . $icon;
    }
    
    $alertId = $autoDismiss ? 'alert-' . uniqid() : null;
@endphp

<div 
    {{ $attributes->merge(['class' => $alertClass]) }}
    @if($alertId) id="{{ $alertId }}" @endif
    role="alert"
>
    <div class="d-flex">
        <div class="flex-shrink-0">
            <i class="{{ $iconClass }}"></i>
        </div>
        
        <div class="flex-grow-1 ms-3">
            @if($title)
                <h5 class="alert-heading">{{ $title }}</h5>
            @endif
            
            {{ $slot }}
        </div>
    </div>
    
    @if($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>

@if($autoDismiss)
    @once
        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const alert = document.getElementById('{{ $alertId }}');
                if (alert) {
                    setTimeout(function() {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }, {{ $autoDismissDelay }});
                }
            });
        </script>
        @endpush
    @endonce
@endif 