{{-- Modern Item Search Integration Examples --}}

{{-- 1. SALES FORM INTEGRATION --}}
<div class="card mb-4 rounded-lg border border-gray-200 shadow-sm">
    <div class="card-header bg-gray-50 bg-light border-bottom border-gray-200">
        <h5 class="mb-0 font-semibold fw-semibold text-gray-900 text-dark">Sales Form - Modern Item Search</h5>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="col-md-6">
                <label class="form-label fw-medium font-medium text-gray-700 text-dark">Warehouse</label>
                <select wire:model.live="selectedWarehouse" class="form-select border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select Warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="col-md-6">
                <label class="form-label fw-medium font-medium text-gray-700 text-dark">Search Items</label>
                {{-- REPLACE OLD DROPDOWN WITH THIS --}}
                <livewire:components.item-search-dropdown 
                    context="sale"
                    :warehouse-id="$selectedWarehouse"
                    placeholder="Search items by name, SKU, or barcode..."
                    :show-stock="true"
                    :show-prices="true"
                />
            </div>
        </div>
    </div>
</div>

{{-- 2. PURCHASE FORM INTEGRATION --}}
<div class="card mb-4 rounded-lg border border-gray-200 shadow-sm">
    <div class="card-header bg-gray-50 bg-light border-bottom border-gray-200">
        <h5 class="mb-0 font-semibold fw-semibold text-gray-900 text-dark">Purchase Form - Modern Item Search</h5>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="col-md-8 md:col-span-3">
                <label class="form-label fw-medium font-medium text-gray-700 text-dark">Search Items</label>
                {{-- REPLACE OLD DROPDOWN WITH THIS --}}
                <livewire:components.item-search-dropdown 
                    context="purchase"
                    placeholder="Search items to purchase..."
                    :show-stock="false"
                    :show-prices="true"
                />
            </div>
            
            <div class="col-md-4 d-flex align-items-end flex items-end">
                <button type="button" class="btn btn-primary bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                    <i class="bi bi-plus-lg me-1"></i>Create New Item
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 3. JAVASCRIPT INTEGRATION --}}
<script>
document.addEventListener('livewire:init', () => {
    // Handle item selection for sales
    Livewire.on('item-selected', (event) => {
        const { item, context, stock } = event;
        
        if (context === 'sale') {
            addItemToSale({
                id: item.id,
                name: item.name,
                sku: item.sku,
                selling_price: item.selling_price,
                available_stock: stock,
                unit_quantity: item.unit_quantity
            });
        } else if (context === 'purchase') {
            addItemToPurchase({
                id: item.id,
                name: item.name,
                sku: item.sku,
                cost_price: item.cost_price,
                unit_quantity: item.unit_quantity
            });
        }
    });

    // Handle out of stock items
    Livewire.on('item-out-of-stock', (event) => {
        showNotification('warning', `${event.item} is out of stock!`);
    });
});

function addItemToSale(item) {
    // Modern sale form integration
    @this.call('addSaleItem', item);
}

function addItemToPurchase(item) {
    // Modern purchase form integration  
    @this.call('addPurchaseItem', item);
}

function showNotification(type, message) {
    // Bootstrap toast or alert
    const toast = `
        <div class="toast align-items-center text-bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    document.querySelector('#notification-container').insertAdjacentHTML('beforeend', toast);
    const toastElement = document.querySelector('.toast:last-child');
    new bootstrap.Toast(toastElement).show();
}
</script>

{{-- 4. MIGRATION GUIDE --}}
<div class="alert alert-info">
    <h6><i class="bi bi-info-circle me-2"></i>Migration from Old Search</h6>
    <p class="mb-2"><strong>Replace this old pattern:</strong></p>
    <pre class="bg-light p-2 rounded small"><code>&lt;div class="position-relative"&gt;
    &lt;input wire:model.live.debounce.300ms="itemSearch" class="form-control" placeholder="Search items..."&gt;
    @if(strlen($itemSearch) >= 2)
        &lt;div class="dropdown-menu show"&gt;
            @foreach($this->filteredItemOptions as $item)
                &lt;button wire:click="selectItem({{ $item['id'] }})"&gt;{{ $item['name'] }}&lt;/button&gt;
            @endforeach
        &lt;/div&gt;
    @endif
&lt;/div&gt;</code></pre>
    
    <p class="mb-2 mt-3"><strong>With this modern component:</strong></p>
    <pre class="bg-success bg-opacity-10 p-2 rounded small"><code>&lt;livewire:components.item-search-dropdown 
    context="sale"
    :warehouse-id="$warehouseId"
    placeholder="Search items..."
    :show-stock="true"
    :show-prices="true"
/&gt;</code></pre>
</div>