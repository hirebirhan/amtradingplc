{{-- Global Flash Messages Component --}}
<div id="flash-messages-container" class="position-fixed" style="top: 20px; right: 20px; z-index: 9999; max-width: 400px;">
    {{-- Success Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 8px; border: none;">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Error Messages --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 8px; border: none;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Warning Messages --}}
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 8px; border: none;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Info Messages --}}
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 8px; border: none;">
            <i class="bi bi-info-circle-fill me-2"></i>
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
</div>

{{-- Auto-dismiss script --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 3 seconds
    const alerts = document.querySelectorAll('#flash-messages-container .alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert && alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 3000);
    });
});

// Listen for Livewire flash messages
document.addEventListener('livewire:initialized', () => {
    Livewire.on('flash-message', (data) => {
        // Handle both array format (Livewire 3) and object format
        let type, message;
        if (Array.isArray(data)) {
            type = data[0]?.type || 'info';
            message = data[0]?.message || '';
        } else if (data && typeof data === 'object') {
            type = data.type || 'info';
            message = data.message || '';
        }
        
        // Only show if message is not empty/undefined
        if (message && typeof showFlashMessage === 'function') {
            showFlashMessage(type, message);
        }
    });
});

// Function to show dynamic flash messages
function showFlashMessage(type, message) {
    const container = document.getElementById('flash-messages-container');
    if (!container) return;

    const iconMap = {
        'success': 'bi-check-circle-fill',
        'error': 'bi-exclamation-triangle-fill',
        'warning': 'bi-exclamation-triangle-fill',
        'info': 'bi-info-circle-fill'
    };

    const alertClass = `alert-${type === 'error' ? 'danger' : type}`;
    const icon = iconMap[type] || 'bi-info-circle-fill';

    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 8px; border: none;">
            <i class="bi ${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', alertHtml);

    // Auto-dismiss after 3 seconds
    const newAlert = container.lastElementChild;
    setTimeout(function() {
        if (newAlert && newAlert.parentNode) {
            const bsAlert = new bootstrap.Alert(newAlert);
            bsAlert.close();
        }
    }, 3000);
}
</script>

<style>
#flash-messages-container .alert {
    margin-bottom: 10px;
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

#flash-messages-container .alert.fade:not(.show) {
    animation: slideOutRight 0.3s ease-in;
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}
</style>