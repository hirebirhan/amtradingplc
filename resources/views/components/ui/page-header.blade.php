@props([
    'title' => '',
    'subtitle' => null,
    'icon' => null,
    'actions' => null,
    'backRoute' => null,
    'backText' => 'Back',
])

<!-- Modern 2-Row Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
    <div class="flex-grow-1">
        <!-- Row 1: Title with optional icon -->
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
            <div class="d-flex align-items-center gap-2">
                @if($icon)
                    <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 32px; height: 32px;">
                        <i class="bi {{ $icon }}"></i>
                    </div>
                @endif
                <h4 class="fw-bold mb-0">{{ $title }}</h4>
            </div>
        </div>
        <!-- Row 2: Description -->
        @if($subtitle)
            <p class="text-secondary mb-0 small">{{ $subtitle }}</p>
        @endif
    </div>
    
    <!-- Action Buttons -->
    <div class="d-flex align-items-center gap-2 flex-shrink-0">
        @if($backRoute)
            <a href="{{ $backRoute }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i>
                <span class="d-none d-sm-inline">{{ $backText }}</span>
            </a>
        @endif
        
        {{ $actions }}
    </div>
</div>