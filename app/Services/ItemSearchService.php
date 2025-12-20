<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Database\Eloquent\Collection;

class ItemSearchService
{
    private int $resultLimit = 15;

    public function search(string $query, string $context = 'purchase', ?int $warehouseId = null): Collection
    {
        $searchTerm = trim($query);
        
        if (strlen($searchTerm) < 2) {
            return collect();
        }

        $baseQuery = Item::select([
            'id', 'name', 'sku', 'barcode', 'status',
            'cost_price', 'selling_price', 'unit_quantity'
        ])
        ->where(function ($q) use ($searchTerm) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhereRaw('LOWER(sku) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhereRaw('LOWER(barcode) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
        })
        ->where('status', 'active')
        ->orderBy('name')
        ->limit($this->resultLimit);

        $items = $baseQuery->get();

        // Load stock info for sales context
        if ($context === 'sale' && $warehouseId) {
            $items->load(['stocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }]);
        }

        return $items;
    }

    public function getItemWithStock(int $itemId, ?int $warehouseId = null): ?Item
    {
        $query = Item::where('id', $itemId);
        
        if ($warehouseId) {
            $query->with(['stocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }]);
        }
        
        return $query->first();
    }
}