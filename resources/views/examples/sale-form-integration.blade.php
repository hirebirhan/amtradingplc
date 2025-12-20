{{-- Sale Form Integration Example --}}
<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Warehouse</label>
            <select wire:model.live="selectedWarehouse" class="w-full px-4 py-2 border rounded-lg">
                <option value="">Select Warehouse</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search Items</label>
            <livewire:components.item-search-dropdown 
                context="sale"
                :warehouse-id="$selectedWarehouse"
                placeholder="Search items with stock..."
                name="sale_item_search"
            />
        </div>
    </div>

    {{-- JavaScript to handle item selection --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('item-selected', (event) => {
                const { item, context, stock } = event;
                
                if (context === 'sale') {
                    // Add item to sale form
                    addItemToSale({
                        id: item.id,
                        name: item.name,
                        sku: item.sku,
                        selling_price: item.selling_price,
                        available_stock: stock
                    });
                }
            });

            Livewire.on('item-out-of-stock', (event) => {
                alert(`${event.item} is out of stock!`);
            });
        });

        function addItemToSale(item) {
            // Your sale form logic here
            console.log('Adding item to sale:', item);
        }
    </script>
</div>