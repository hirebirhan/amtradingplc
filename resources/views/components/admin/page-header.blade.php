@props([
    'title' => '',
    'subtitle' => null,
    'createRoute' => null,
    'createText' => 'Create New',
    'createIcon' => 'bi-plus-lg',
    'icon' => null,
    'iconBg' => 'primary',
    'headerClass' => '',
    'actions' => null
])

<div class="card shadow-sm border-0 overflow-hidden">
    <div class="card-header bg-white border-bottom-0 py-4 px-4 {{ $headerClass }}">
        <div class="position-relative">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    @if($icon)
                        <div class="rounded-circle bg-{{ $iconBg }} bg-opacity-10 p-2 me-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                            <i class="bi {{ $icon }} text-{{ $iconBg }} fs-5"></i>
                        </div>
                    @endif
                    
                    <div>
                        <h4 class="card-title fw-bold mb-1 text-dark">{{ $title }}</h4>
                        @if($subtitle)
                            <p class="text-muted mb-0">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
                
                <div class="d-flex gap-2 align-items-center">
                    @if($createRoute)
                        <x-admin.header-action 
                            :route="$createRoute" 
                            :icon="$createIcon"
                            variant="primary"
                        >
                            {{ $createText }}
                        </x-admin.header-action>
                    @endif
                    
                    {{ $actions }}
                </div>
            </div>
        </div>
    </div>
    
    <div class="border-top"></div>
    {{ $slot }}
</div> 