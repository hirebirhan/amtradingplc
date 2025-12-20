{{-- Search Test Component --}}
<div class="card">
    <div class="card-header">
        <h5>Item Search Test</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Test Search</label>
                <livewire:components.item-search-dropdown 
                    context="sale"
                    placeholder="Try: Ethiopian, coffee, beans..."
                    :show-stock="true"
                    :show-prices="true"
                />
            </div>
            <div class="col-md-6">
                <label class="form-label">Debug Info</label>
                <div class="alert alert-info">
                    <strong>Search Tips:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Search is case-insensitive</li>
                        <li>Matches partial words</li>
                        <li>Try "Ethiopian" or "coffee" or "beans"</li>
                        <li>Minimum 2 characters required</li>
                    </ul>
                </div>
            </div>
        </div>
        
        {{-- Manual SQL Test --}}
        <div class="mt-4">
            <h6>Manual Database Check</h6>
            <div class="bg-light p-3 rounded">
                <code>
                    SELECT name, sku, status FROM items 
                    WHERE LOWER(name) LIKE '%ethiopian%' 
                    OR LOWER(name) LIKE '%coffee%' 
                    OR LOWER(name) LIKE '%beans%'
                    LIMIT 10;
                </code>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('item-selected', (event) => {
        console.log('Item selected:', event);
        alert(`Selected: ${event.item.name}`);
    });
});
</script>