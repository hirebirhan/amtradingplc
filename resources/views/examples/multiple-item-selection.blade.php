{{-- Multiple Item Selection Example --}}

{{-- SALES FORM WITH MULTIPLE SAME ITEMS --}}
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Sales Form - Multiple Same Items Support</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-medium">Warehouse</label>
                <select wire:model.live="selectedWarehouse" class="form-select">
                    <option value="">Select Warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="col-md-6">
                <label class="form-label fw-medium">Search Items</label>
                <livewire:components.item-search-dropdown 
                    context="sale"
                    :warehouse-id="$selectedWarehouse"
                    placeholder="Search and add items multiple times..."
                    :show-stock="true"
                    :show-prices="true"
                />
            </div>
        </div>

        {{-- Cart Items Display --}}
        <div class="mt-4">
            <h6 class="fw-semibold mb-3">Cart Items</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cartItems as $index => $cartItem)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $cartItem['name'] }}</div>
                                <small class="text-muted">{{ $cartItem['sku'] }}</small>
                            </td>
                            <td class="text-center">
                                <input type="number" 
                                       wire:model.live="cartItems.{{ $index }}.quantity" 
                                       class="form-control form-control-sm text-center" 
                                       style="width: 80px;" 
                                       min="1">
                            </td>
                            <td class="text-end">{{ number_format($cartItem['price'], 2) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($cartItem['quantity'] * $cartItem['price'], 2) }}</td>
                            <td class="text-end">
                                <button type="button" 
                                        wire:click="removeCartItem({{ $index }})" 
                                        class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total:</th>
                            <th class="text-end">{{ number_format($cartTotal, 2) }} ETB</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- JAVASCRIPT INTEGRATION --}}
<script>
document.addEventListener('livewire:init', () => {
    // Handle item selection - allows multiple same items
    Livewire.on('item-selected', (event) => {
        const { item, context, stock, stock_warning } = event;
        
        if (context === 'sale') {
            // Show stock warning if needed
            if (stock_warning) {
                showStockWarning(item.name, stock);
            }
            
            // Add item to cart (allows duplicates)
            @this.call('addItemToCart', {
                id: item.id,
                name: item.name,
                sku: item.sku,
                price: item.selling_price,
                quantity: 1,
                available_stock: stock,
                timestamp: Date.now() // Unique identifier for same items
            });
        }
    });
});

function showStockWarning(itemName, stock) {
    const toast = `
        <div class="toast align-items-center text-bg-warning border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${itemName}</strong> has low/no stock (${stock} available)
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    document.querySelector('#notification-container').insertAdjacentHTML('beforeend', toast);
    const toastElement = document.querySelector('.toast:last-child');
    new bootstrap.Toast(toastElement).show();
}
</script>

{{-- LIVEWIRE COMPONENT METHODS --}}
{{-- Add these methods to your Livewire component --}}
<div class="alert alert-info mt-4">
    <h6><i class="bi bi-code-square me-2"></i>Required Livewire Methods</h6>
    <pre class="bg-light p-3 rounded small"><code>// In your Livewire component class

public array $cartItems = [];

public function addItemToCart($itemData)
{
    // Add item with unique timestamp to allow duplicates
    $this->cartItems[] = [
        'id' => $itemData['id'],
        'name' => $itemData['name'],
        'sku' => $itemData['sku'],
        'price' => $itemData['price'],
        'quantity' => $itemData['quantity'],
        'available_stock' => $itemData['available_stock'],
        'timestamp' => $itemData['timestamp']
    ];
    
    $this->dispatch('item-added-to-cart');
}

public function removeCartItem($index)
{
    unset($this->cartItems[$index]);
    $this->cartItems = array_values($this->cartItems); // Reindex
}

public function getCartTotalProperty()
{
    return collect($this->cartItems)->sum(function ($item) {
        return $item['quantity'] * $item['price'];
    });
}

// Validate total stock before checkout
public function validateCartStock()
{
    $itemQuantities = [];
    
    foreach ($this->cartItems as $cartItem) {
        $itemId = $cartItem['id'];
        $itemQuantities[$itemId] = ($itemQuantities[$itemId] ?? 0) + $cartItem['quantity'];
    }
    
    foreach ($itemQuantities as $itemId => $totalQuantity) {
        $item = Item::with('stocks')->find($itemId);
        $availableStock = $item->stocks->where('warehouse_id', $this->selectedWarehouse)->first()?->available_quantity ?? 0;
        
        if ($totalQuantity > $availableStock) {
            $this->addError('cart', "Insufficient stock for {$item->name}. Requested: {$totalQuantity}, Available: {$availableStock}");
            return false;
        }
    }
    
    return true;
}</code></pre>
</div>

{{-- BUSINESS LOGIC EXPLANATION --}}
<div class="alert alert-success">
    <h6><i class="bi bi-lightbulb me-2"></i>Business Logic Benefits</h6>
    <ul class="mb-0">
        <li><strong>Real-world Usage:</strong> Customers often buy multiple quantities of same item</li>
        <li><strong>Flexible Pricing:</strong> Different prices for same item (promotions, bulk discounts)</li>
        <li><strong>Stock Validation:</strong> Total quantity validation at checkout, not per addition</li>
        <li><strong>User Experience:</strong> No blocking, just warnings for stock issues</li>
        <li><strong>Audit Trail:</strong> Each cart entry has timestamp for tracking</li>
    </ul>
</div>