{{-- Purchase Form Integration Example --}}
<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search Items</label>
            <livewire:components.item-search-dropdown 
                context="purchase"
                placeholder="Search items by name, SKU, or barcode..."
                name="purchase_item_search"
            />
        </div>
    </div>

    {{-- JavaScript to handle item selection --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('item-selected', (event) => {
                const { item, context } = event;
                
                if (context === 'purchase') {
                    // Add item to purchase form
                    addItemToPurchase({
                        id: item.id,
                        name: item.name,
                        sku: item.sku,
                        cost_price: item.cost_price,
                        unit_quantity: item.unit_quantity
                    });
                }
            });
        });

        function addItemToPurchase(item) {
            // Your purchase form logic here
            console.log('Adding item to purchase:', item);
        }
    </script>
</div>