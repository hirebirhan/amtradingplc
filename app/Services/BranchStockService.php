<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BranchStockService
{
    /**
     * Get stock for an item in a specific branch
     */
    public function getBranchStock(Item $item, int $branchId): float
    {
        return $item->stocks()
            ->whereHas('warehouse.branches', function($query) use ($branchId) {
                $query->where('branches.id', $branchId);
            })
            ->sum('piece_count');
    }

    /**
     * Get available stock for user's context (branch/warehouse specific)
     */
    public function getAvailableStock(Item $item, ?User $user = null): float
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return $item->getTotalStockAttribute();
        }

        // SuperAdmin/GeneralManager see all stock
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return $item->getTotalStockAttribute();
        }

        // Warehouse user sees only their warehouse stock
        if ($user->warehouse_id) {
            return $item->getStockInWarehouse($user->warehouse_id);
        }

        // Branch user sees branch stock
        if ($user->branch_id) {
            return $this->getBranchStock($item, $user->branch_id);
        }

        return 0;
    }

    /**
     * Create or update stock for an item in a specific warehouse
     */
    public function createOrUpdateStock(Item $item, int $warehouseId, int $pieces, float $unitCapacity): Stock
    {
        // Get warehouse's branch
        $warehouse = Warehouse::with('branches')->findOrFail($warehouseId);
        $branchId = $warehouse->branches->first()?->id;

        return Stock::updateOrCreate(
            [
                'item_id' => $item->id,
                'warehouse_id' => $warehouseId,
            ],
            [
                'branch_id' => $branchId,
                'piece_count' => $pieces,
                'total_units' => $pieces * $unitCapacity,
                'quantity' => $pieces,
                'current_piece_units' => $unitCapacity,
                'updated_by' => auth()->id(),
            ]
        );
    }

    /**
     * Get items with stock filtered by user's branch access
     */
    public function getItemsWithBranchStock(?User $user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }

        $query = Item::with(['category', 'stocks']);

        // Filter stocks based on user access
        if ($user && !$user->isSuperAdmin() && !$user->isGeneralManager()) {
            if ($user->warehouse_id) {
                // Warehouse user - only their warehouse
                $query->with(['stocks' => function($q) use ($user) {
                    $q->where('warehouse_id', $user->warehouse_id);
                }]);
            } elseif ($user->branch_id) {
                // Branch user - only their branch warehouses
                $query->with(['stocks' => function($q) use ($user) {
                    $q->whereHas('warehouse.branches', function($bq) use ($user) {
                        $bq->where('branches.id', $user->branch_id);
                    });
                }]);
            }
        }

        return $query;
    }

    /**
     * Check if item has sufficient stock in user's accessible warehouses
     */
    public function hasSufficientStock(Item $item, float $requiredQuantity, ?User $user = null): bool
    {
        $availableStock = $this->getAvailableStock($item, $user);
        return $availableStock >= $requiredQuantity;
    }

    /**
     * Get stock breakdown by branch for an item
     */
    public function getStockBreakdownByBranch(Item $item): array
    {
        return $item->stocks()
            ->with('warehouse.branches')
            ->get()
            ->groupBy(function($stock) {
                return $stock->warehouse->branches->first()?->name ?? 'Unassigned';
            })
            ->map(function($stocks) {
                return [
                    'pieces' => $stocks->sum('piece_count'),
                    'units' => $stocks->sum('total_units'),
                    'warehouses' => $stocks->count()
                ];
            })
            ->toArray();
    }

    /**
     * Transfer stock between warehouses (within or across branches)
     */
    public function transferStock(
        Item $item, 
        int $fromWarehouseId, 
        int $toWarehouseId, 
        int $pieces, 
        float $unitCapacity,
        string $referenceType = 'transfer',
        ?int $referenceId = null
    ): bool {
        return DB::transaction(function() use ($item, $fromWarehouseId, $toWarehouseId, $pieces, $unitCapacity, $referenceType, $referenceId) {
            // Get source stock
            $sourceStock = Stock::where('item_id', $item->id)
                ->where('warehouse_id', $fromWarehouseId)
                ->first();

            if (!$sourceStock || $sourceStock->piece_count < $pieces) {
                throw new \Exception('Insufficient stock in source warehouse');
            }

            // Deduct from source
            $sourceStock->sellByPiece($pieces, $unitCapacity, $referenceType, $referenceId, 'Stock transfer out');

            // Add to destination
            $this->createOrUpdateStock($item, $toWarehouseId, $pieces, $unitCapacity);

            return true;
        });
    }
}