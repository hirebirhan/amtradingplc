# Item Search Dropdown Component Usage

## Overview

The new `ItemSearchDropdown` component provides high-performance, branch-isolated item searching for both Purchase and Sale workflows. It includes real-time search, stock validation, and keyboard navigation.

## Key Features

- **Branch Isolation**: Items are filtered by user's branch automatically
- **Real-time Search**: Debounced search with minimum 2 characters
- **Stock Awareness**: Shows available stock for sales context
- **Keyboard Navigation**: Arrow keys, Enter, and Escape support
- **Performance Optimized**: Limits results and uses efficient queries
- **Reusable**: Works in both Purchase and Sale contexts

## Usage Examples

### 1. In Purchase Create Form

```php
// In your Purchase Create Livewire component
public function render()
{
    return view('livewire.purchases.create', [
        'suppliers' => $this->getSuppliers(),
        'warehouses' => $this->getWarehouses(),
    ]);
}

protected $listeners = ['itemSelected' => 'addItemToPurchase'];

public function addItemToPurchase($itemData)
{
    // Add the selected item to purchase
    $this->purchaseItems[] = [
        'item_id' => $itemData['id'],
        'name' => $itemData['name'],
        'sku' => $itemData['sku'],
        'cost_price' => $itemData['cost_price'],
        'quantity' => 1,
        'subtotal' => $itemData['cost_price'],
    ];
    
    // Clear the search dropdown
    $this->dispatch('clearSelection');
}
```

```blade
{{-- In purchases/create.blade.php --}}
<div class="row">
    <div class="col-md-8">
        <label class="form-label">Search Items</label>
        <livewire:components.item-search-dropdown 
            :context="'purchase'"
            :show-available-stock="false"
            placeholder="Search items to add to purchase..."
            item-selected-event="itemSelected"
        />
    </div>
    <div class="col-md-4">
        <button type="button" class="btn btn-primary mt-4" onclick="window.location.href='{{ route('admin.items.create', ['from' => 'purchase']) }}'">
            <i class="fas fa-plus"></i> Create New Item
        </button>
    </div>
</div>
```

### 2. In Sale Create Form

```php
// In your Sale Create Livewire component
public $selectedWarehouseId = null;

public function render()
{
    return view('livewire.sales.create', [
        'customers' => $this->getCustomers(),
        'warehouses' => $this->getUserWarehouses(),
    ]);
}

protected $listeners = ['itemSelected' => 'addItemToSale'];

public function addItemToSale($itemData)
{
    // Validate stock availability
    if ($itemData['available_stock'] <= 0) {
        $this->dispatch('toast', [
            'type' => 'error',
            'message' => 'This item is out of stock.'
        ]);
        return;
    }
    
    // Add item to sale
    $this->saleItems[] = [
        'item_id' => $itemData['id'],
        'name' => $itemData['name'],
        'sku' => $itemData['sku'],
        'selling_price' => $itemData['selling_price'],
        'available_stock' => $itemData['available_stock'],
        'quantity' => 1,
        'subtotal' => $itemData['selling_price'],
    ];
    
    $this->dispatch('clearSelection');
}
```

```blade
{{-- In sales/create.blade.php --}}
<div class="row">
    <div class="col-md-6">
        <label class="form-label">Warehouse *</label>
        <select wire:model.live="selectedWarehouseId" class="form-select" required>
            <option value="">Select Warehouse</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
            @endforeach
        </select>
    </div>
</div>

@if($selectedWarehouseId)
<div class="row mt-3">
    <div class="col-md-8">
        <label class="form-label">Search Items</label>
        <livewire:components.item-search-dropdown 
            :warehouse-id="$selectedWarehouseId"
            :context="'sale'"
            :show-available-stock="true"
            placeholder="Search items with available stock..."
            item-selected-event="itemSelected"
        />
    </div>
    <div class="col-md-4">
        <button type="button" class="btn btn-primary mt-4" onclick="window.location.href='{{ route('admin.items.create', ['from' => 'sale']) }}'">
            <i class="fas fa-plus"></i> Create New Item
        </button>
    </div>
</div>
@endif
```

### 3. In Transfer Create Form

```php
// In Transfer Create Livewire component
protected $listeners = ['itemSelected' => 'addItemToTransfer'];

public function addItemToTransfer($itemData)
{
    // Check if item already exists in transfer
    $existingIndex = collect($this->transferItems)->search(function ($item) use ($itemData) {
        return $item['item_id'] == $itemData['id'];
    });
    
    if ($existingIndex !== false) {
        $this->dispatch('toast', [
            'type' => 'warning',
            'message' => 'This item is already added to the transfer.'
        ]);
        return;
    }
    
    $this->transferItems[] = [
        'item_id' => $itemData['id'],
        'name' => $itemData['name'],
        'sku' => $itemData['sku'],
        'available_stock' => $itemData['available_stock'],
        'quantity' => 1,
    ];
    
    $this->dispatch('clearSelection');
}
```

## Component Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `warehouseId` | int\|null | null | Warehouse ID for stock filtering |
| `context` | string | 'purchase' | Context: 'purchase', 'sale', or 'transfer' |
| `placeholder` | string | 'Search items...' | Input placeholder text |
| `showAvailableStock` | bool | true | Show stock information |
| `minSearchLength` | int | 2 | Minimum characters to trigger search |
| `maxResults` | int | 15 | Maximum search results |
| `itemSelectedEvent` | string | 'itemSelected' | Event name when item is selected |

## Events

### Listening for Item Selection

```php
protected $listeners = ['itemSelected' => 'handleItemSelection'];

public function handleItemSelection($itemData)
{
    // $itemData contains:
    // - id, name, sku, barcode
    // - cost_price, selling_price, cost_price_per_unit, selling_price_per_unit
    // - unit_quantity, item_unit
    // - available_stock (if warehouse provided)
    // - is_low_stock (boolean)
}
```

### Clearing Selection

```php
// Clear the dropdown selection
$this->dispatch('clearSelection');
```

## Branch Isolation Logic

The component automatically applies branch-level filtering:

1. **SuperAdmin/GeneralManager**: See all items across all branches
2. **Branch Users**: See only items from their branch + global items (branch_id = null)
3. **Warehouse Users**: See items based on their branch assignment

## Database Changes Required

Run this migration to enable branch-level item isolation:

```bash
# Through Docker
docker compose exec app php artisan migrate

# Or directly if PHP is available
php artisan migrate
```

The migration changes the unique constraint from global to branch-scoped:
- **Before**: `UNIQUE(name)` - Global uniqueness
- **After**: `UNIQUE(name, branch_id)` - Branch-scoped uniqueness

## Performance Optimizations

1. **Debounced Search**: 300ms delay to reduce API calls
2. **Limited Results**: Maximum 15 results per search
3. **Efficient Queries**: Only loads necessary columns
4. **Indexed Searches**: Uses database indexes on name, SKU, barcode
5. **Conditional Loading**: Stock data loaded only when needed

## Error Handling

The component handles various error scenarios:

- **No Results**: Shows appropriate message based on context
- **No Stock**: Filters out items without stock in sales context
- **Branch Isolation**: Automatically applies user permissions
- **Validation**: Prevents duplicate item selection

## Keyboard Navigation

- **Arrow Down/Up**: Navigate through results
- **Enter**: Select highlighted item
- **Escape**: Close dropdown
- **Tab**: Move to next form field

This component provides a robust, scalable solution for item selection across the entire application while maintaining proper branch isolation and performance.