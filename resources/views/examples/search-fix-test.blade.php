{{-- Search Test for All Items --}}
<div class="alert alert-success">
    <h6><i class="bi bi-check-circle me-2"></i>Search Fix Applied</h6>
    <p class="mb-2"><strong>Problem:</strong> Search was filtering out items with zero stock</p>
    <p class="mb-2"><strong>Solution:</strong> Removed stock quantity filter from search</p>
    <p class="mb-0"><strong>Result:</strong> All active items now searchable regardless of stock level</p>
</div>

<div class="card">
    <div class="card-header">
        <h5>Test Search - All Items Available</h5>
    </div>
    <div class="card-body">
        <livewire:components.item-search-dropdown 
            context="sale"
            placeholder="Search 'Teff Flour' - should work now..."
            :show-stock="true"
            :show-prices="true"
        />
        
        <div class="mt-3">
            <h6>Search Behavior:</h6>
            <ul class="mb-0">
                <li>✅ Shows all active items (regardless of stock)</li>
                <li>✅ Stock warnings shown in dropdown</li>
                <li>✅ Same item can be added multiple times</li>
                <li>✅ Search clears after each selection</li>
            </ul>
        </div>
    </div>
</div>