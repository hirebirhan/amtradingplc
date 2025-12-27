<div class="relative" x-data="itemSearchDropdown()" x-init="init()">
    <!-- Search Input -->
    <div class="relative">
        <input 
            type="text" 
            wire:model.live.debounce.300ms="searchTerm"
            x-ref="searchInput"
            x-on:keydown="handleKeydown($event)"
            x-on:focus="$wire.isOpen = true"
            x-on:blur="setTimeout(() => $wire.closeDropdown(), 150)"
            placeholder="{{ $placeholder }}"
            class="form-control pr-10 @error('selectedItemId') is-invalid @enderror"
            autocomplete="off"
        >
        
        <!-- Clear Button -->
        @if($selectedItem)
            <button 
                type="button" 
                wire:click="clearSelection"
                class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
                <i class="fas fa-times"></i>
            </button>
        @else
            <div class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400">
                <i class="fas fa-search"></i>
            </div>
        @endif
    </div>

    <!-- Dropdown Results -->
    @if($isOpen && count($searchResults) > 0)
        <div class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
            @foreach($searchResults as $index => $item)
                <div 
                    wire:click="selectItem({{ $item['id'] }})"
                    class="px-4 py-3 cursor-pointer hover:bg-gray-50 border-b border-gray-100 last:border-b-0 
                           {{ $highlightedIndex === $index ? 'bg-blue-50' : '' }}
                           {{ $item['is_low_stock'] ? 'bg-yellow-50' : '' }}"
                >
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">
                                {{ $item['name'] }}
                                @if($item['is_low_stock'])
                                    <span class="text-yellow-600 ml-1">⚠️</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500">
                                SKU: {{ $item['sku'] }}
                                @if($item['barcode'])
                                    | Barcode: {{ $item['barcode'] }}
                                @endif
                            </div>
                            @if($context === 'purchase')
                                <div class="text-sm text-blue-600">
                                    Cost: {{ number_format($item['cost_price'], 2) }} ETB
                                    @if($item['unit_quantity'] > 1)
                                        ({{ number_format($item['cost_price_per_unit'], 2) }} ETB per {{ $item['item_unit'] }})
                                    @endif
                                </div>
                            @elseif($context === 'sale')
                                <div class="text-sm text-green-600">
                                    Price: {{ number_format($item['selling_price'], 2) }} ETB
                                    @if($item['unit_quantity'] > 1)
                                        ({{ number_format($item['selling_price_per_unit'], 2) }} ETB per {{ $item['item_unit'] }})
                                    @endif
                                </div>
                            @endif
                        </div>
                        
                        @if($showAvailableStock && $warehouseId)
                            <div class="text-right ml-4">
                                <div class="text-sm font-medium 
                                    {{ $item['available_stock'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    Available: {{ number_format($item['available_stock'], 2) }}
                                </div>
                                @if($item['is_low_stock'])
                                    <div class="text-xs text-yellow-600">Low Stock</div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @elseif($isOpen && strlen($searchTerm) >= $minSearchLength && count($searchResults) === 0)
        <div class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
            <div class="px-4 py-3 text-gray-500 text-center">
                @if($context === 'sale' && $warehouseId)
                    No items with available stock found at this location.
                @else
                    No items found matching "{{ $searchTerm }}".
                @endif
            </div>
        </div>
    @endif

    @error('selectedItemId')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<script>
function itemSearchDropdown() {
    return {
        init() {
            // Initialize component
        },
        
        handleKeydown(event) {
            const key = event.key;
            
            if (['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].includes(key)) {
                event.preventDefault();
                this.$wire.handleKeydown(key);
            }
        }
    }
}
</script>

<style>
/* Custom scrollbar for dropdown */
.max-h-60::-webkit-scrollbar {
    width: 6px;
}

.max-h-60::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.max-h-60::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.max-h-60::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>