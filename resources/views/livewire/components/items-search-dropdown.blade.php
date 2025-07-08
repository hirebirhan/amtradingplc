<div class="items-search-dropdown">
    <div class="position-relative">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            wire:keydown.escape="$set('showDropdown', false)"
            wire:keydown.arrow-down="$set('showDropdown', true)"
            wire:keydown.arrow-up="$set('showDropdown', true)"
            wire:click="$set('showDropdown', true)"
            wire:focus="$set('showDropdown', true)"
            placeholder="{{ $placeholder }}"
            class="form-control @error('selected') is-invalid @enderror"
            autocomplete="off"
        />
        <input type="hidden" wire:model="selected" />

        @if($showDropdown && count($filteredItems) > 0)
            <ul class="list-unstyled mb-0 bg-body border rounded shadow-sm position-absolute w-100 mt-1" style="z-index: 1050;">
                @foreach($filteredItems as $item)
                    <li class="py-2 px-3 cursor-pointer hover-bg-light border-bottom" wire:click="selectItem({{ $item['id'] }})">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="fw-medium text-body">{{ $item['name'] }}</div>
                                <div class="small text-muted">
                                    <span>SKU: {{ $item['formatted_sku'] ?? $item['sku'] }}</span>
                                    @if($item['unit'])
                                        <span> • Unit: {{ $item['unit'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-end">
                                @if($item['cost_price'])
                                    <div class="small fw-medium text-body">ETB {{ number_format($item['cost_price'], 2) }}</div>
                                @endif
                                <div class="small text-muted">Stock: {{ $item['current_stock'] }}</div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @elseif($showDropdown && strlen($search) >= $minimumCharacters)
            <div class="position-absolute w-100 mt-1 rounded shadow-sm border" style="background: var(--card-bg); z-index: 1050; border-color: var(--border-color) !important;">
                <div class="py-3 px-3 small text-muted">
                    No items found matching "{{ $search }}"
                </div>
            </div>
        @endif

        @error('selected')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>