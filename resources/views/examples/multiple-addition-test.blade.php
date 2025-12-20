{{-- Test Multiple Same Item Addition --}}
<div class="card">
    <div class="card-header">
        <h5>Test: Multiple Same Item Addition</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-medium">Search Items</label>
                <livewire:components.item-search-dropdown 
                    context="sale"
                    placeholder="Search for Teff multiple times..."
                    :show-stock="true"
                    :show-prices="true"
                />
                
                <div class="alert alert-success mt-3">
                    <h6><i class="bi bi-check-circle me-2"></i>Fixed Behavior</h6>
                    <ol class="mb-0">
                        <li>Search "Teff" → Select item → Search clears automatically</li>
                        <li>Search "Teff" again → Same item appears → Can select again</li>
                        <li>Repeat as many times as needed</li>
                    </ol>
                </div>
            </div>
            
            <div class="col-md-6">
                <label class="form-label fw-medium">Cart Items</label>
                <div class="border rounded p-3 bg-light">
                    <div id="cart-display">
                        <p class="text-muted mb-0">No items added yet</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cartItems = [];

document.addEventListener('livewire:init', () => {
    Livewire.on('item-selected', (event) => {
        const { item } = event;
        
        // Add to cart array
        cartItems.push({
            name: item.name,
            price: item.selling_price,
            timestamp: Date.now()
        });
        
        // Update display
        updateCartDisplay();
        
        console.log('Item added to cart:', item.name);
        console.log('Total cart items:', cartItems.length);
    });
});

function updateCartDisplay() {
    const cartDiv = document.getElementById('cart-display');
    
    if (cartItems.length === 0) {
        cartDiv.innerHTML = '<p class="text-muted mb-0">No items added yet</p>';
        return;
    }
    
    const itemCounts = {};
    cartItems.forEach(item => {
        itemCounts[item.name] = (itemCounts[item.name] || 0) + 1;
    });
    
    let html = '<div class="fw-medium mb-2">Cart Items:</div>';
    Object.entries(itemCounts).forEach(([name, count]) => {
        html += `<div class="d-flex justify-content-between border-bottom py-1">
            <span>${name}</span>
            <span class="badge bg-primary">${count}x</span>
        </div>`;
    });
    
    cartDiv.innerHTML = html;
}
</script>