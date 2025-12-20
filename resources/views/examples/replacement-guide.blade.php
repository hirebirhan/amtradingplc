{{-- REPLACEMENT GUIDE: Old vs New Item Search --}}

{{-- ❌ OLD PATTERN (Replace this) --}}
<div class="position-relative">
    <input wire:model.live.debounce.300ms="itemSearch" 
           class="form-control" 
           placeholder="Search items..."
           autocomplete="off">
    
    @if(strlen($itemSearch) >= 2)
        <div class="dropdown-menu show w-100" style="max-height: 200px; overflow-y: auto;">
            @if(count($this->filteredItemOptions) > 0)
                @foreach($this->filteredItemOptions as $item)
                    <button type="button" 
                            class="dropdown-item py-2" 
                            wire:click="selectItem({{ $item['id'] }})">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1 me-2">
                                <div class="fw-medium text-truncate">{{ $item['name'] }}</div>
                                <small class="text-muted">{{ $item['sku'] }}</small>
                            </div>
                            <div class="text-end flex-shrink-0">
                                @if($item['quantity'] <= 0)
                                    <span class="badge bg-warning text-dark">Out of Stock</span>
                                @else
                                    <span class="badge bg-success">Stock: {{ $item['quantity'] }}</span>
                                @endif
                            </div>
                        </div>
                    </button>
                @endforeach
            @else
                <div class="dropdown-item-text text-muted">
                    <small>No items found</small>
                </div>
            @endif
        </div>
    @endif
</div>

{{-- ✅ NEW PATTERN (Use this instead) --}}
<livewire:components.item-search-dropdown 
    context="sale"
    :warehouse-id="$selectedWarehouse"
    placeholder="Search items by name, SKU, or barcode..."
    :show-stock="true"
    :show-prices="true"
/>

{{-- JAVASCRIPT INTEGRATION --}}
<script>
document.addEventListener('livewire:init', () => {
    // Listen for item selection
    Livewire.on('item-selected', (event) => {
        const { item, context, stock } = event;
        
        // Add to your form
        if (context === 'sale') {
            @this.call('addSaleItem', {
                id: item.id,
                name: item.name,
                sku: item.sku,
                price: item.selling_price,
                stock: stock
            });
        } else if (context === 'purchase') {
            @this.call('addPurchaseItem', {
                id: item.id,
                name: item.name,
                sku: item.sku,
                cost: item.cost_price,
                unit_quantity: item.unit_quantity
            });
        }
    });

    // Handle stock warnings
    Livewire.on('item-out-of-stock', (event) => {
        // Show notification
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-bg-warning border-0 position-fixed top-0 end-0 m-3';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${event.item} is out of stock!</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        document.body.appendChild(toast);
        new bootstrap.Toast(toast).show();
    });
});
</script>

{{-- MIGRATION CHECKLIST --}}
<div class="alert alert-info bg-blue-50 bg-info bg-opacity-10 border border-blue-200 border-info p-4 rounded-lg">
    <h6 class="font-semibold fw-semibold text-blue-800 text-info mb-3">
        <i class="bi bi-list-check me-2"></i>Migration Checklist
    </h6>
    
    <div class="space-y-2">
        <div class="flex items-center space-x-2 d-flex align-items-center">
            <input type="checkbox" class="form-check-input rounded border-gray-300">
            <span class="text-sm small text-gray-700 text-dark">Replace old dropdown search with new component</span>
        </div>
        
        <div class="flex items-center space-x-2 d-flex align-items-center">
            <input type="checkbox" class="form-check-input rounded border-gray-300">
            <span class="text-sm small text-gray-700 text-dark">Add JavaScript event listeners for item-selected</span>
        </div>
        
        <div class="flex items-center space-x-2 d-flex align-items-center">
            <input type="checkbox" class="form-check-input rounded border-gray-300">
            <span class="text-sm small text-gray-700 text-dark">Remove old itemSearch properties from Livewire component</span>
        </div>
        
        <div class="flex items-center space-x-2 d-flex align-items-center">
            <input type="checkbox" class="form-check-input rounded border-gray-300">
            <span class="text-sm small text-gray-700 text-dark">Update form methods to handle new item structure</span>
        </div>
        
        <div class="flex items-center space-x-2 d-flex align-items-center">
            <input type="checkbox" class="form-check-input rounded border-gray-300">
            <span class="text-sm small text-gray-700 text-dark">Test keyboard navigation and accessibility</span>
        </div>
    </div>
</div>

{{-- PERFORMANCE BENEFITS --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 row g-3 mt-4">
    <div class="col-md-4">
        <div class="card border border-green-200 border-success h-100">
            <div class="card-body text-center">
                <i class="bi bi-lightning-charge text-green-600 text-success display-6 mb-2"></i>
                <h6 class="font-semibold fw-semibold text-green-800 text-success">Faster Search</h6>
                <p class="text-sm small text-gray-600 text-muted mb-0">Optimized queries with caching</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border border-blue-200 border-primary h-100">
            <div class="card-body text-center">
                <i class="bi bi-keyboard text-blue-600 text-primary display-6 mb-2"></i>
                <h6 class="font-semibold fw-semibold text-blue-800 text-primary">Keyboard Nav</h6>
                <p class="text-sm small text-gray-600 text-muted mb-0">Arrow keys, Enter, Escape</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border border-purple-200 border-secondary h-100">
            <div class="card-body text-center">
                <i class="bi bi-phone text-purple-600 text-secondary display-6 mb-2"></i>
                <h6 class="font-semibold fw-semibold text-purple-800 text-secondary">Mobile Ready</h6>
                <p class="text-sm small text-gray-600 text-muted mb-0">Touch-friendly interface</p>
            </div>
        </div>
    </div>
</div>