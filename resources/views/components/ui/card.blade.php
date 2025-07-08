@props([
    'type' => 'default', // default, primary, success, warning, danger, info
    'header' => null,
    'footer' => null,
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'border' => true,
    'shadow' => false,
    'hover' => false,
    'loading' => false,
    'collapsible' => false,
    'collapsed' => false,
])

@php
    $cardClass = 'card';
    $cardClass .= $border ? '' : ' border-0';
    $cardClass .= $shadow ? ' shadow' : '';
    
    $headerClass = 'card-header';
    if ($type !== 'default') {
        $headerClass .= ' bg-' . $type . ' text-white';
    }
    
    $bodyClass = 'card-body';
    if ($loading) {
        $bodyClass .= ' position-relative';
    }
    
    $cardId = $collapsible ? 'card-' . uniqid() : null;
@endphp

<div {{ $attributes->merge(['class' => $cardClass]) }} @if($cardId) id="{{ $cardId }}" @endif>
    @if($header || $title || $icon)
        <div class="{{ $headerClass }}">
            <div class="d-flex align-items-center">
                @if($icon)
                    <i class="fas {{ $icon }} me-2"></i>
                @endif
                
                @if($title)
                    <h5 class="card-title mb-0">{{ $title }}</h5>
                @endif
                
                @if($subtitle)
                    <small class="text-muted ms-2">{{ $subtitle }}</small>
                @endif
                
                @if($collapsible)
                    <button class="btn btn-link ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $cardId }}-body" aria-expanded="{{ $collapsed ? 'false' : 'true' }}">
                        <i class="fas fa-chevron-{{ $collapsed ? 'down' : 'up' }}"></i>
                    </button>
                @endif
            </div>
            
            @if($header)
                <div class="mt-2">{{ $header }}</div>
            @endif
        </div>
    @endif
    
    <div class="{{ $bodyClass }}" @if($collapsible) id="{{ $cardId }}-body" class="collapse {{ $collapsed ? '' : 'show' }}" @endif>
        @if($loading)
            <div class="d-flex justify-content-center py-3">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
            </div>
        @endif
        
        {{ $slot }}
    </div>
    
    @if($footer)
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endif
</div> 