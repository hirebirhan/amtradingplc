<div class="position-relative" x-data="{ 
    open: @entangle('isOpen'),
    selectedIndex: @entangle('selectedIndex')
}">
    <div class="input-group">
        <span class="input-group-text bg-light border-end-0">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input 
            type="text" 
            wire:model.live.debounce.300ms="search"
            @keydown.arrow-down.prevent="$wire.keyDown('ArrowDown')"
            @keydown.arrow-up.prevent="$wire.keyDown('ArrowUp')"
            @keydown.enter.prevent="$wire.keyDown('Enter')"
            @keydown.escape="$wire.keyDown('Escape')"
            @focus="open = true"
            placeholder="{{ $placeholder }}"
            class="form-control border-start-0 ps-0"
            autocomplete="off"
        />
        @if($search)
            <button 
                type="button"
                wire:click="$set('search', '')"
                class="btn btn-outline-secondary border-start-0"
                title="Clear search"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        @endif
    </div>

    @if($isOpen && strlen($search) >= 2)
        <div 
            class="position-absolute w-100 mt-1 bg-white border rounded shadow-lg"
            style="z-index: 1050; max-height: 320px; overflow-y: auto;"
            x-show="open"
            @click.away="open = false"
        >
            <div wire:loading class="p-3 text-center text-muted">
                <div class="spinner-border spinner-border-sm me-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                Searching items...
            </div>

            <div wire:loading.remove>
                @forelse($this->searchResults as $index => $item)
                    <button
                        type="button"
                        wire:key="item-{{ $item->id }}"
                        wire:click="selectItem({{ $item->id }})"
                        class="w-100 px-3 py-2 text-start border-0 border-bottom
                            {{ $selectedIndex === $index ? 'bg-primary bg-opacity-10' : 'bg-transparent' }}"
                        style="transition: background-color 0.15s ease;"
                        onmouseover="this.classList.add('bg-light')"
                        onmouseout="this.classList.remove('bg-light')"
                    >
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="flex-grow-1 me-3 min-w-0">
                                <div class="fw-medium text-truncate mb-1">{{ $item->name }}</div>
                                <div class="small text-muted">
                                    <span class="badge bg-light text-dark me-1">{{ $item->sku }}</span>
                                    @if($item->barcode)
                                        <span class="badge bg-light text-dark">{{ $item->barcode }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($showPrices || $showStock)
                                <div class="text-end flex-shrink-0">
                                    @if($context === 'sale' && $showPrices)
                                        <div class="fw-semibold text-success small">
                                            {{ number_format($item->selling_price, 2) }} ETB
                                        </div>
                                    @elseif($context === 'purchase' && $showPrices)
                                        <div class="fw-semibold text-primary small">
                                            {{ number_format($item->cost_price, 2) }} ETB
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            Unit: {{ $item->unit_quantity }}
                                        </div>
                                    @endif
                                    
                                    @if($context === 'sale' && $showStock && $warehouseId && $item->stocks->isNotEmpty())
                                        @php
                                            $stock = $item->stocks->first();
                                            $available = $stock->available_quantity ?? 0;
                                        @endphp
                                        <div class="mt-1">
                                            @if($available <= 0)
                                                <span class="badge bg-danger text-white">Out of Stock</span>
                                            @elseif($available < 10)
                                                <span class="badge bg-warning text-dark">
                                                    {{ number_format($available, 1) }} ⚠️
                                                </span>
                                            @else
                                                <span class="badge bg-success text-white">
                                                    {{ number_format($available, 1) }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </button>
                @empty
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-search display-6 mb-2 d-block text-secondary"></i>
                        <div class="fw-medium">No items found</div>
                        <small>Try a different search term</small>
                    </div>
                @endforelse
            </div>
        </div>
    @elseif($isOpen && strlen($search) > 0 && strlen($search) < 2)
        <div class="position-absolute w-100 mt-1 bg-white border rounded shadow p-3 text-center text-muted" style="z-index: 1050;">
            <i class="bi bi-info-circle me-1"></i>
            Type at least 2 characters to search
        </div>
    @endif
</div>