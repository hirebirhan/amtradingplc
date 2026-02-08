# Branch Stock Isolation Implementation

## Overview
This implementation ensures proper branch isolation where:
- **Items are GLOBAL** - All branches can see and use the same items
- **Stock is BRANCH-SPECIFIC** - Each branch maintains independent stock levels for items

## Key Changes Made

### 1. Database Migration (`2026_02_05_000000_implement_proper_branch_stock_isolation.php`)
- Ensures all stock records have proper `branch_id` based on warehouse relationships
- Adds unique constraint `stocks_warehouse_item_unique` to prevent duplicate stock records
- Updates stock history records with proper branch tracking

### 2. BranchStockService (`app/Services/BranchStockService.php`)
- `getBranchStock()` - Get stock for item in specific branch
- `getAvailableStock()` - Get stock based on user's access level
- `createOrUpdateStock()` - Create/update stock with proper branch assignment
- `getItemsWithBranchStock()` - Filter items with branch-specific stock
- `hasSufficientStock()` - Check stock availability for user's context
- `getStockBreakdownByBranch()` - Get stock breakdown across branches
- `transferStock()` - Handle inter-branch stock transfers

### 3. Item Model Updates (`app/Models/Item.php`)
- Updated `getRoleBasedStock()` to handle global items with branch-isolated stock
- Modified `getStockInBranch()` to use direct `branch_id` instead of warehouse relationships
- Updated `getPiecesInBranch()` and `getUnitsInBranch()` for direct branch access

### 4. Stock Model Updates (`app/Models/Stock.php`)
- Added `boot()` method to auto-assign `branch_id` based on warehouse relationships
- Ensures proper audit trail with `created_by` and `updated_by` fields

### 5. Items Index Livewire Updates (`app/Livewire/Items/Index.php`)
- Removed item-level branch filtering (items are now global)
- Updated stock relationship filtering to use direct `branch_id`
- Modified all stock calculation methods to use branch-specific filtering
- Updated branch filter logic for admin users

## Business Logic

### Item Visibility
```php
// All branches see all items
$items = Item::all(); // Global access

// But stock is branch-specific
$branchStock = $item->getStockInBranch($branchId);
```

### Stock Isolation
```php
// Branch A creates stock for Item X
Stock::create([
    'item_id' => $itemX->id,
    'warehouse_id' => $warehouseA->id,
    'branch_id' => $branchA->id,
    'piece_count' => 100
]);

// Branch B creates separate stock for same Item X
Stock::create([
    'item_id' => $itemX->id,
    'warehouse_id' => $warehouseB->id,
    'branch_id' => $branchB->id,
    'piece_count' => 50
]);

// Result: Item X exists globally but has different stock levels per branch
```

### User Access Control
```php
// SuperAdmin/GeneralManager
$stock = $item->getTotalStockAttribute(); // All branches

// Branch Manager
$stock = $item->getStockInBranch($user->branch_id); // Only their branch

// Warehouse User
$stock = $item->getStockInWarehouse($user->warehouse_id); // Only their warehouse
```

## Database Schema

### Items Table
```sql
-- Items are global (branch_id = NULL for all)
items:
  id, name, sku, category_id, branch_id (NULL), cost_price, selling_price, ...
```

### Stocks Table
```sql
-- Stock is branch-specific
stocks:
  id, item_id, warehouse_id, branch_id (NOT NULL), piece_count, total_units, ...
  
-- Unique constraint prevents duplicate stock records
UNIQUE KEY stocks_warehouse_item_unique (warehouse_id, item_id)
```

## Migration Steps

1. **Run Migration**:
   ```bash
   php artisan migrate --path=database/migrations/2026_02_05_000000_implement_proper_branch_stock_isolation.php
   ```

2. **Verify Data**:
   ```sql
   -- Check that all stocks have branch_id
   SELECT COUNT(*) FROM stocks WHERE branch_id IS NULL;
   
   -- Check items are global
   SELECT COUNT(*) FROM items WHERE branch_id IS NOT NULL;
   ```

## Benefits

1. **Simplified Item Management**: Items created once, available to all branches
2. **Branch Independence**: Each branch maintains separate inventory levels
3. **Accurate Reporting**: Branch-specific stock and financial reports
4. **Scalability**: Easy to add new branches without item duplication
5. **Data Integrity**: Unique constraints prevent stock record conflicts

## Usage Examples

### Creating Items (Global)
```php
// SuperAdmin creates item - available to all branches
$item = Item::create([
    'name' => 'Ethiopian Coffee Beans',
    'sku' => 'COFFEE-001',
    'branch_id' => null, // Global item
]);
```

### Managing Stock (Branch-Specific)
```php
// Branch A receives stock
$stockService = new BranchStockService();
$stockService->createOrUpdateStock($item, $warehouseA->id, 100, 1.0);

// Branch B receives different quantity
$stockService->createOrUpdateStock($item, $warehouseB->id, 50, 1.0);
```

### Viewing Stock (Role-Based)
```php
// Branch manager sees only their branch stock
$availableStock = $stockService->getAvailableStock($item, $branchManager);

// SuperAdmin sees all stock across branches
$totalStock = $stockService->getAvailableStock($item, $superAdmin);
```

This implementation ensures proper branch isolation while maintaining the global nature of items, following business best practices for multi-branch inventory management.