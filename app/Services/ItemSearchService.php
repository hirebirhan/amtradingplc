<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ItemSearchService
{
    private int $resultLimit = 15;

    public function search(string $query, string $context = 'purchase', ?int $warehouseId = null, ?User $user = null): Collection
    {
        $searchTerm = trim($query);
        
        if (strlen($searchTerm) < 2) {
            return collect();
        }

        if (!$user) {
            $user = Auth::user();
        }

        $baseQuery = Item::select([
            'id', 'name', 'sku', 'barcode', 'branch_id', 'is_active',
            'cost_price', 'selling_price', 'cost_price_per_unit', 
            'selling_price_per_unit', 'unit_quantity', 'item_unit', 'reorder_level'
        ])
        ->where(function ($q) use ($searchTerm) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhereRaw('LOWER(sku) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhereRaw('LOWER(barcode) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
        })
        ->where('is_active', true);

        // Apply branch-level filtering
        $baseQuery = $this->applyBranchFiltering($baseQuery, $user);

        // For sales context, only show items with available stock
        if ($context === 'sale' && $warehouseId) {
            $baseQuery->whereHas('stocks', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                  ->where('piece_count', '>', 0);
            });
        }

        $baseQuery->orderBy('name')
                  ->limit($this->resultLimit);

        $items = $baseQuery->get();

        // Load stock info for context that needs it
        if (($context === 'sale' || $context === 'transfer') && $warehouseId) {
            $items->load(['stocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }]);
        }

        return $items;
    }

    /**
     * Apply branch-level filtering based on user permissions
     */
    private function applyBranchFiltering($query, ?User $user)
    {
        if (!$user) {
            return $query;
        }

        // SuperAdmin and GeneralManager see all items
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return $query;
        }

        // Branch users see only their branch items + global items (null branch_id)
        if ($user->branch_id) {
            return $query->where(function($q) use ($user) {
                $q->where('branch_id', $user->branch_id)
                  ->orWhereNull('branch_id');
            });
        }

        return $query;
    }

    /**
     * Get items with available stock for a specific warehouse
     */
    public function getItemsWithStock(?int $warehouseId = null, ?User $user = null): Collection
    {
        if (!$user) {
            $user = Auth::user();
        }

        $query = Item::select([
            'id', 'name', 'sku', 'branch_id', 'is_active',
            'cost_price', 'selling_price', 'unit_quantity', 'item_unit'
        ])
        ->where('is_active', true);

        // Apply branch filtering
        $query = $this->applyBranchFiltering($query, $user);

        if ($warehouseId) {
            $query->whereHas('stocks', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                  ->where('piece_count', '>', 0);
            });
        }

        $items = $query->orderBy('name')->get();

        if ($warehouseId) {
            $items->load(['stocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }]);
        }

        return $items;
    }

    public function getItemWithStock(int $itemId, ?int $warehouseId = null): ?Item
    {
        $query = Item::where('id', $itemId)->where('is_active', true);
        
        if ($warehouseId) {
            $query->with(['stocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }]);
        }
        
        return $query->first();
    }

    /**
     * Check if item name exists within user's branch scope
     */
    public function checkItemNameExists(string $name, ?User $user = null, ?int $excludeId = null): bool
    {
        if (!$user) {
            $user = Auth::user();
        }

        $branchId = $user && !$user->isSuperAdmin() ? $user->branch_id : null;
        
        $query = Item::where('name', $name)
            ->where('branch_id', $branchId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}