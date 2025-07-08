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
                    ->leftJoin('stocks', 'items.id', '=', 'stocks.item_id');

        // Apply role-based filtering
        if ($user->hasRole(['SystemAdmin', 'Manager'])) {
            // No filtering for admins - see all items
        } elseif ($user->hasRole('BranchManager') && $user->branch_id) {
            // Branch Manager sees items from their branch warehouses
            $query->whereExists(function($warehouseQuery) use ($user) {
                $warehouseQuery->select(DB::raw(1))
                              ->from('warehouses')
                              ->join('branch_warehouse', 'warehouses.id', '=', 'branch_warehouse.warehouse_id')
                              ->where('branch_warehouse.branch_id', $user->branch_id)
                              ->whereColumn('warehouses.id', 'stocks.warehouse_id');
            });
        } elseif ($user->hasRole('WarehouseUser') && $user->warehouse_id) {
            // Warehouse User sees only items from their assigned warehouse
            $query->where('stocks.warehouse_id', $user->warehouse_id);
        } elseif ($user->hasRole('Sales')) {
            // Sales users see items from their branch warehouses (if they have a branch)
            if ($user->branch_id) {
                $query->whereExists(function($warehouseQuery) use ($user) {
                    $warehouseQuery->select(DB::raw(1))
                                  ->from('warehouses')
                                  ->join('branch_warehouse', 'warehouses.id', '=', 'branch_warehouse.warehouse_id')
                                  ->where('branch_warehouse.branch_id', $user->branch_id)
                                  ->whereColumn('warehouses.id', 'stocks.warehouse_id');
                });
            } else {
                // Sales user without branch assignment sees no low stock items
                $query->whereRaw('1 = 0');
            }
        } else {
            // No access
            $query->whereRaw('1 = 0');
        }

        return $query->groupBy('items.id', 'items.reorder_level', 'items.name', 'items.sku', 'items.barcode', 
                              'items.description', 'items.category_id', 'items.cost_price', 'items.selling_price', 
                              'items.unit', 'items.brand', 'items.status', 'items.is_active', 'items.created_at', 
                              'items.updated_at', 'items.deleted_at')
                     ->havingRaw('COALESCE(SUM(stocks.quantity), 0) <= items.reorder_level')
                     ->havingRaw('COALESCE(SUM(stocks.quantity), 0) >= 0')
                     ->orderBy('current_stock', 'asc');
    }
} 