<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Item;
use App\Services\Dashboard\Contracts\InventoryServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService implements InventoryServiceInterface
{
    /**
     * Get inventory data for a user
     */
    public function getInventoryData(User $user): array
    {
        return [
            'low_stock_items' => $this->getLowStockItemsCount($user),
            'low_stock_items_list' => $this->getLowStockItemsList($user),
        ];
    }

    private function getLowStockItemsCount(User $user): int
    {
        return $this->getLowStockItemsQuery($user)->count();
    }

    private function getLowStockItemsList(User $user): Collection
    {
        return $this->getLowStockItemsQuery($user)
                   ->with(['category', 'stocks'])
                   ->limit(10)
                   ->get();
    }

    private function getLowStockItemsQuery($user)
    {
        $query = Item::select('items.*')
                    ->selectRaw('COALESCE(SUM(stocks.quantity), 0) as current_stock')
                    ->leftJoin('stocks', 'items.id', '=', 'stocks.item_id')
                    ->where('items.is_active', true);

        // Apply branch filtering for non-admin users
        if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
            if ($user->branch_id) {
                $query->where('items.branch_id', $user->branch_id);
            }
        }

        return $query->groupBy('items.id', 'items.reorder_level', 'items.name', 'items.sku', 'items.barcode', 
                              'items.description', 'items.category_id', 'items.cost_price', 'items.selling_price', 
                              'items.unit', 'items.brand', 'items.status', 'items.is_active', 'items.created_at', 
                              'items.updated_at')
                     ->havingRaw('COALESCE(SUM(stocks.quantity), 0) <= items.reorder_level')
                     ->havingRaw('COALESCE(SUM(stocks.quantity), 0) >= 0')
                     ->orderBy('current_stock', 'asc');
    }
} 