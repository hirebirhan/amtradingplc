@props([
    'id' => 'toast-' . uniqid(),
    'title' => 'Notification',
    'message' => '',
    'type' => 'info', // info, success, warning, danger
    'autohide' => true,
    'delay' => 5000,
    'position' => 'top-right', // top-left, top-center, top-right, bottom-left, bottom-center, bottom-right
    'live' => false,
])

@php
    $types = [
        'info' => [
            'bg' => 'bg-info-light',
            'text' => 'text-info',
            'icon' => 'fa-info-circle'
        ],
        'success' => [
            'bg' => 'bg-success-light',
            'text' => 'text-success',
            'icon' => 'fa-check-circle'
        ],
        'warning' => [
            'bg' => 'bg-warning-light',
            'text' => 'text-warning',
            'icon' => 'fa-exclamation-triangle'
        ],
        'danger' => [
            'bg' => 'bg-danger-light',
            'text' => 'text-danger',
            'icon' => 'fa-exclamation-circle'
        ],
    ];
    
    $typeClasses = $types[$type] ?? $types['info'];
@endphp

<div 
    id="{{ $id }}" 
    class="toast {{ $typeClasses['bg'] }}" 
    role="alert" 
    aria-live="{{ $live ? 'assertive' : 'polite' }}" 
    aria-atomic="true"
    data-bs-autohide="{{ $autohide ? 'true' : 'false' }}"
    data-bs-delay="{{ $delay }}"
>
    <div class="toast-header {{ $typeClasses['bg'] }}">
        <i class="fas {{ $typeClasses['icon'] }} me-2 {{ $typeClasses['text'] }}"></i>
        <strong class="me-auto">{{ $title }}</strong>
        <small>{{ $attributes->get('time') ?? 'Just now' }}</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
        {{ $slot ?: $message }}
    </div>
</div>

@once
    @push('scripts')
    <script>
        // Toast container initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Create toast container if it doesn't exist
            const positions = ['top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right'];
            positions.forEach(position => {
                if (!document.querySelector(`.toast-container.${position}`)) {
                    const container = document.createElement('div');
                    container.className = `toast-container position-fixed ${position} p-3`;
                    
                    // Set position styles
                    if (position.includes('top')) {
                        container.style.top = '0';
                    } else {
                        container.style.bottom = '0';
                    }
                    
                    if (position.includes('left')) {
                        container.style.left = '0';
                    } else if (position.includes('right')) {
                        container.style.right = '0';
                    } else {
                        container.style.left = '50%';
                        container.style.transform = 'translateX(-50%)';
                    }
                    
                    document.body.appendChild(container);
                }
            });
            
            // Helper function to show toast programmatically
            window.showToast = function({
                message, 
                title = 'Notification', 
                type = 'info', 
                autohide = true, 
                delay = 5000, 
                position = 'top-right'
            }) {
                // Create toast element
                const toast = document.createElement('div');
                const id = 'toast-' + Date.now();
                toast.id = id;
                toast.className = `toast`;
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'polite');
                toast.setAttribute('aria-atomic', 'true');
                toast.setAttribute('data-bs-autohide', autohide ? 'true' : 'false');
                toast.setAttribute('data-bs-delay', delay);
                
                // Set toast type classes
                const types = {
                    'info': {
                        'bg': 'bg-info-light',
                        'text': 'text-info',
                        'icon': 'fa-info-circle'
                    },
                    'success': {
                        'bg': 'bg-success-light',
                        'text': 'text-success',
                        'icon': 'fa-check-circle'
                    },
                    'warning': {
                        'bg': 'bg-warning-light',
                        'text': 'text-warning',
                        'icon': 'fa-exclamation-triangle'
                    },
                    'danger': {
                        'bg': 'bg-danger-light',
                        'text': 'text-danger',
                        'icon': 'fa-exclamation-circle'
                    },
                };
                
                const typeClasses = types[type] || types['info'];
                toast.classList.add(typeClasses.bg);
                
                // Create toast content
                toast.innerHTML = `
                    <div class="toast-header ${typeClasses.bg}">
                        <i class="fas ${typeClasses.icon} me-2 ${typeClasses.text}"></i>
                        <strong class="me-auto">${title}</strong>
                        <small>Just now</small>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                `;
                
                // Add toast to container
                const container = document.querySelector(`.toast-container.${position}`);
                if (container) {
                    container.appendChild(toast);
                    const bsToast = new bootstrap.Toast(toast);
                    bsToast.show();
                }
                
                return id;
            };
            
            // Listen for Livewire toast events
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('showToast', (data) => {
                    window.showToast(data);
                });
            });
        });
    </script>
    
    <style>
        .toast-container {
            z-index: 1090;
        }
    </style>
    @endpush
@endonce 