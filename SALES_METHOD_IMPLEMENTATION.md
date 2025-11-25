# Sales Method Implementation - By Piece vs By Unit

## Overview

The sales creation page now supports two mutually exclusive selling methods:

1. **By Piece** - Sells complete pieces, reducing piece count directly
2. **By Unit (Qty)** - Sells individual units, deducting from current piece units first

## Implementation Details

### Frontend (Blade View)

- Radio buttons with `name="sale_method"` ensure mutually exclusive selection
- Switching methods automatically resets quantity to 1
- Dynamic labels show appropriate unit types (pieces vs units)
- Real-time stock availability display based on selected method

### Backend (Livewire Component)

#### Key Methods:

1. **`updatedNewItemSaleMethod($value)`**
   - Handles method switching
   - Resets quantity and updates pricing
   - Ensures mutually exclusive behavior

2. **`getAvailableStockForMethod()`**
   - Returns available stock based on selected method
   - For pieces: returns `piece_count`
   - For units: calculates total available units across all pieces

3. **`updateStockForSaleMethod($item, $warehouseId)`**
   - Handles stock deduction based on sale method
   - For pieces: reduces `piece_count` directly
   - For units: deducts from `current_piece_units`, auto-reduces pieces when needed

### Database Schema

#### New Field: `current_piece_units`
- Tracks remaining units in the current piece
- Allows partial unit sales from pieces
- Automatically managed during sales

#### Stock Deduction Logic:

**By Piece:**
```php
$stock->piece_count -= $quantity;
$stock->current_piece_units = $unitQuantity; // Reset to full
```

**By Unit:**
```php
while ($remainingUnits > 0 && $stock->piece_count > 0) {
    if ($remainingUnits >= $currentPieceUnits) {
        // Use entire current piece
        $remainingUnits -= $currentPieceUnits;
        $stock->piece_count -= 1;
        $currentPieceUnits = $unitQuantity; // Reset for next piece
    } else {
        // Use partial current piece
        $currentPieceUnits -= $remainingUnits;
        $remainingUnits = 0;
    }
}
```

## Usage Examples

### Example 1: Selling by Piece
- Item: Rice Bag (1 bag = 50 kg)
- Stock: 10 bags
- Sale: 3 bags
- Result: 7 bags remaining, each bag still contains 50 kg

### Example 2: Selling by Unit
- Item: Rice Bag (1 bag = 50 kg)
- Stock: 10 bags, current bag has 30 kg remaining
- Sale: 80 kg
- Process:
  1. Use 30 kg from current bag (bag becomes empty, piece_count: 9)
  2. Use 50 kg from next bag (bag becomes empty, piece_count: 8)
  3. Current bag now has 0 kg remaining
- Result: 8 full bags + 0 kg in current bag

## Migration Required

Run the migration to add the `current_piece_units` field:

```bash
php artisan migrate
```

The migration file: `2025_01_20_000000_add_current_piece_units_to_stocks_table.php`

## Testing

Test both methods with various scenarios:

1. **Piece Sales**: Verify piece count reduces correctly
2. **Unit Sales**: Verify units deduct from current piece first
3. **Method Switching**: Ensure radio buttons are mutually exclusive
4. **Stock Display**: Verify correct available stock shows for each method
5. **Edge Cases**: Test when current piece units reach zero

## Benefits

- **Flexibility**: Supports both wholesale (piece) and retail (unit) sales
- **Accuracy**: Precise inventory tracking at unit level
- **User Experience**: Clear, mutually exclusive interface
- **Data Integrity**: Automatic stock calculations prevent errors