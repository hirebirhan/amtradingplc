<?php

namespace App\Services\Sales;

use App\Models\Item;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class CartService
{
    private ItemSelectionService $itemService;

    public function __construct()
    {
        $this->itemService = new ItemSelectionService();
    }

    public function addItemToCart(array $newItem, array &$items, ?int $editingIndex = null): array
    {
        $item = Item::find($newItem['item_id']);
        if (!$item) {
            throw new \Exception('Item not found');
        }

        $itemData = $this->itemService->processItemData($item, $newItem);

        if ($editingIndex !== null) {
            $items[$editingIndex] = $itemData;
            return ['action' => 'updated', 'item' => $itemData];
        } else {
            $items[] = $itemData;
            return ['action' => 'added', 'item' => $itemData];
        }
    }

    public function removeItemFromCart(int $index, array &$items): array
    {
        if (!isset($items[$index])) {
            throw new \Exception('Item not found in cart');
        }

        $removedItem = $items[$index];
        unset($items[$index]);
        $items = array_values($items);
        
        return $removedItem;
    }

    public function clearCart(array &$items): void
    {
        $items = [];
    }

    public function getItemForEdit(int $index, array $items): ?array
    {
        if (!isset($items[$index])) {
            return null;
        }

        $item = $items[$index];
        
        return [
            'item_id' => $item['item_id'],
            'quantity' => $item['quantity'],
            'sale_method' => $item['sale_method'] ?? 'piece',
            'unit_price' => $item['unit_quantity'] ? $item['price'] / $item['unit_quantity'] : $item['price'],
            'price' => $item['price'],
            'notes' => $item['notes'] ?? '',
        ];
    }

    public function getAvailableStockForEdit(int $itemId, int $originalQuantity, ?int $warehouseId, ?int $branchId): float
    {
        if ($warehouseId) {
            $stock = Stock::where('item_id', $itemId)
                ->where('warehouse_id', $warehouseId)
                ->first();
            return $stock ? $stock->quantity + $originalQuantity : $originalQuantity;
        }

        if ($branchId) {
            $warehouseIds = DB::table('branch_warehouse')
                ->where('branch_id', $branchId)
                ->pluck('warehouse_id')
                ->toArray();

            if (empty($warehouseIds)) {
                return $originalQuantity;
            }

            $totalStock = Stock::where('item_id', $itemId)
                ->whereIn('warehouse_id', $warehouseIds)
                ->sum('quantity');

            return $totalStock + $originalQuantity;
        }

        return $originalQuantity;
    }
}