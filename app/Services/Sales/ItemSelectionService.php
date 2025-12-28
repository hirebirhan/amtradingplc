<?php

namespace App\Services\Sales;

use App\Models\Item;
use App\Models\Stock;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ItemSelectionService
{
    public function getFilteredItems(string $searchTerm, ?int $warehouseId = null): array
    {
        if (strlen(trim($searchTerm)) < 2) {
            return [];
        }
        
        try {
            return Item::forUser(Auth::user())
                ->where('is_active', true)
                ->where(function ($query) use ($searchTerm) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                          ->orWhereRaw('LOWER(sku) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                          ->orWhereRaw('LOWER(barcode) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
                })
                ->limit(15)
                ->get()
                ->map(function ($item) use ($warehouseId) {
                    $stockValue = $warehouseId ? $this->getItemStock($item, $warehouseId) : 0;
                    
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'sku' => $item->sku,
                        'quantity' => $stockValue,
                        'total_units' => $stockValue * ($item->unit_quantity ?? 1),
                        'selling_price_per_unit' => $item->selling_price_per_unit ?? 0,
                        'unit_quantity' => $item->unit_quantity ?? 1,
                        'item_unit' => $item->item_unit ?? 'piece',
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('Error in getFilteredItems: ' . $e->getMessage());
            return [];
        }
    }

    public function getItemStock(Item $item, ?int $warehouseId): float
    {
        $stock = Stock::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'item_id' => $item->id
            ],
            [
                'quantity' => 0,
                'piece_count' => 0,
                'total_units' => 0,
                'current_piece_units' => $item->unit_quantity ?? 1
            ]
        );
        
        $stockValue = max($stock->piece_count ?? 0, $stock->quantity ?? 0);
        
        if ($stockValue <= 0) {
            $totalPurchased = DB::table('purchase_items')
                ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                ->where('purchase_items.item_id', $item->id)
                ->where('purchases.warehouse_id', $warehouseId)
                ->sum('purchase_items.quantity');
            
            if ($totalPurchased > 0) {
                $stock->update([
                    'quantity' => $totalPurchased,
                    'piece_count' => $totalPurchased
                ]);
                $stockValue = $totalPurchased;
            }
        }
        
        return $stockValue;
    }

    public function validateItemPrice(Item $item, float $sellingPrice, string $saleMethod): ?string
    {
        // Skip validation if cost price is not set or is zero
        if ($saleMethod === 'piece') {
            $costPrice = (float)($item->cost_price ?? 0);
            if ($costPrice > 0 && $sellingPrice < $costPrice) {
                return 'Selling price (' . number_format($sellingPrice, 2) . ') cannot be below cost price (' . number_format($costPrice, 2) . ')';
            }
        } else {
            // For unit sales, compare with cost price per unit
            $unitQuantity = $item->unit_quantity ?? 1;
            $costPricePerUnit = $unitQuantity > 0 ? (float)($item->cost_price ?? 0) / $unitQuantity : 0;
            
            if ($costPricePerUnit > 0 && $sellingPrice < $costPricePerUnit) {
                return 'Unit selling price (' . number_format($sellingPrice, 2) . ') cannot be below cost price per unit (' . number_format($costPricePerUnit, 2) . ')';
            }
        }
        
        return null;
    }

    public function processItemData(Item $item, array $newItem): array
    {
        $quantity = floatval($newItem['quantity']);
        $unitPrice = floatval($newItem['unit_price']);
        $subtotal = $quantity * $unitPrice;

        return [
            'item_id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'unit' => $item->unit ?? '',
            'unit_quantity' => $item->unit_quantity ?? 1,
            'item_unit' => $item->item_unit ?? 'piece',
            'quantity' => $quantity,
            'sale_method' => $newItem['sale_method'],
            'price' => $unitPrice,
            'subtotal' => $subtotal,
            'notes' => $newItem['notes'] ?? null,
        ];
    }
}