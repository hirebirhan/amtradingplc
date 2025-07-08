@props([
    'id' => null,
    'title' => null,
    'size' => null, // sm, lg, xl, fullscreen
    'centered' => false,
    'scrollable' => false,
    'static' => false,
    'type' => 'default', // default, success, warning, danger, info
    'closeButton' => true,
    'footer' => true,
    'dismissible' => true,
])

@php
    $modalClass = 'modal fade';
    $modalClass .= $centered ? ' modal-dialog-centered' : '';
    $modalClass .= $scrollable ? ' modal-dialog-scrollable' : '';
    $modalClass .= $static ? ' modal-static' : '';
    
    $dialogClass = 'modal-dialog';
    if ($size) {
        $dialogClass .= ' modal-' . $size;
    }
    
    $headerClass = 'modal-header';
    if ($type !== 'default') {
        $headerClass .= ' bg-' . $type . ' text-white';
    }
    
    $closeButtonClass = 'btn-close';
    if ($type !== 'default') {
        $closeButtonClass .= ' btn-close-white';
    }
@endphp

<div 
    {{ $attributes->merge(['class' => $modalClass]) }}
    id="{{ $id }}"
    tabindex="-1"
    aria-labelledby="{{ $id }}-label"
    aria-hidden="true"
    @if($static) data-bs-backdrop="static" data-bs-keyboard="false" @endif
>
    <div class="{{ $dialogClass }}">
        <div class="modal-content">
            @if($title || $closeButton)
                <div class="{{ $headerClass }}">
                    @if($title)
                        <h5 class="modal-title" id="{{ $id }}-label">{{ $title }}</h5>
                    @endif
                    
                    @if($closeButton && $dismissible)
                        <button type="button" class="{{ $closeButtonClass }}" data-bs-dismiss="modal" aria-label="Close"></button>
                    @endif
                </div>
            @endif
            
            <div class="modal-body">
                {{ $slot }}
            </div>
            
            @if($footer)
                <div class="modal-footer">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all modals
            var modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                new bootstrap.Modal(modal);
            });
            
            // Handle static backdrop
            document.querySelectorAll('.modal-static').forEach(function(modal) {
                modal.addEventListener('click', function(event) {
                    if (event.target === this) {
                        // Prevent closing on backdrop click
                        event.stopPropagation();
                    }
                });
            });
        });
    </script>
    @endpush
@endonce 