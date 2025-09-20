<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\StockReservation;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Exceptions\TransferException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockMovementService
{
    public function ensureBranchWarehouse(int $branchId): Warehouse
    {
        $branch = Branch::with('warehouses')->find($branchId);
        if (!$branch) {
            throw new TransferException('Branch not found.');
        }
        $warehouse = $branch->warehouses->first();
        if ($warehouse) {
            return $warehouse;
        }
        // Create a default internal warehouse for this branch
        $code = 'WH-BR-' . $branch->id;
        $name = 'Default Warehouse - ' . ($branch->name ?? ('Branch ' . $branch->id));
        $warehouse = Warehouse::firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'address' => $branch->address ?? null,
                'manager_name' => null,
                'phone' => null,
            ]
        );
        // Attach to branch via pivot
        $warehouse->branches()->syncWithoutDetaching([$branch->id]);
        return $warehouse;
    }
    /**
     * Reserve stock for a transfer
     */
    public function reserveStock(array $items, string $sourceType, int $sourceId, int $transferId, int $userId): void
    {
        DB::transaction(function () use ($items, $sourceType, $sourceId, $transferId, $userId) {
            foreach ($items as $item) {
                $this->reserveItemStock(
                    $item['item_id'],
                    $item['quantity'],
                    $sourceType,
                    $sourceId,
                    'transfer',
                    $transferId,
                    $userId
                );
            }
        });
    }

    /**
     * Release reserved stock (when transfer is cancelled/rejected)
     */
    public function releaseReservedStock(int $transferId): void
    {
        DB::transaction(function () use ($transferId) {
            StockReservation::where('reference_type', 'transfer')
                ->where('reference_id', $transferId)
                ->delete();
        });
    }

    /**
     * Execute actual stock movement for transfer
     */
    public function executeTransferMovement(
        array $items,
        string $sourceType,
        int $sourceId,
        string $destinationType,
        int $destinationId,
        int $transferId,
        int $userId
    ): void {
        DB::transaction(function () use ($items, $sourceType, $sourceId, $destinationType, $destinationId, $transferId, $userId) {
            foreach ($items as $item) {
                $this->moveStock(
                    $item['item_id'],
                    $item['quantity'],
                    $sourceType,
                    $sourceId,
                    $destinationType,
                    $destinationId,
                    'transfer',
                    $transferId,
                    $userId
                );
            }

            // Release reservations after successful movement
            $this->releaseReservedStock($transferId);
        });
    }

    /**
     * Reserve stock for a specific item
     */
    private function reserveItemStock(
        int $itemId,
        float $quantity,
        string $locationType,
        int $locationId,
        string $referenceType,
        int $referenceId,
        int $userId
    ): void {
        $availableStock = $this->getAvailableStock($itemId, $locationType, $locationId);
        $reservedStock = $this->getReservedStock($itemId, $locationType, $locationId);
        $actualAvailable = $availableStock - $reservedStock;

        if ($actualAvailable < $quantity) {
            $item = Item::find($itemId);
            throw new TransferException(
                "Insufficient available stock for {$item->name}. " .
                "Available: {$actualAvailable}, Required: {$quantity}, Reserved: {$reservedStock}"
            );
        }

        // Create reservation record
        StockReservation::create([
            'item_id' => $itemId,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'expires_at' => now()->addHours(24), // Auto-expire reservations
            'created_by' => $userId,
        ]);
    }

    /**
     * Move stock between locations with atomic operations
     */
    private function moveStock(
        int $itemId,
        float $quantity,
        string $sourceType,
        int $sourceId,
        string $destinationType,
        int $destinationId,
        string $referenceType,
        int $referenceId,
        int $userId
    ): void {
        // Remove from source with locking
        $sourceMovements = $this->removeStockFromLocation($itemId, $quantity, $sourceType, $sourceId);
        
        // Add to destination
        $destinationMovements = $this->addStockToLocation($itemId, $quantity, $destinationType, $destinationId);

        // Record history for all movements
        foreach ($sourceMovements as $movement) {
            $this->recordStockHistory(
                $movement['warehouse_id'],
                $itemId,
                $movement['quantity_before'],
                $movement['quantity_after'],
                -$movement['quantity_moved'],
                $referenceType,
                $referenceId,
                "Transfer out - ID: {$referenceId}",
                $userId
            );
        }

        foreach ($destinationMovements as $movement) {
            $this->recordStockHistory(
                $movement['warehouse_id'],
                $itemId,
                $movement['quantity_before'],
                $movement['quantity_after'],
                $movement['quantity_moved'],
                $referenceType,
                $referenceId,
                "Transfer in - ID: {$referenceId}",
                $userId
            );
        }
    }

    /**
     * Remove stock from location with proper locking and validation
     */
    private function removeStockFromLocation(int $itemId, float $quantity, string $locationType, int $locationId): array
    {
        if ($locationType === 'warehouse') {
            return $this->removeStockFromWarehouse($itemId, $quantity, $locationId);
        } else {
            return $this->removeStockFromBranch($itemId, $quantity, $locationId);
        }
    }

    /**
     * Add stock to location
     */
    private function addStockToLocation(int $itemId, float $quantity, string $locationType, int $locationId): array
    {
        if ($locationType === 'warehouse') {
            return $this->addStockToWarehouse($itemId, $quantity, $locationId);
        } else {
            return $this->addStockToBranch($itemId, $quantity, $locationId);
        }
    }

    /**
     * Remove stock from warehouse with pessimistic locking
     */
    private function removeStockFromWarehouse(int $itemId, float $quantity, int $warehouseId): array
    {
        $stock = Stock::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->lockForUpdate() // Pessimistic lock
            ->first();

        if (!$stock || $stock->quantity < $quantity) {
            $item = Item::find($itemId);
            $warehouse = Warehouse::find($warehouseId);
            throw new TransferException(
                "Insufficient stock of {$item->name} in {$warehouse->name}. " .
                "Available: " . ($stock->quantity ?? 0) . ", Required: {$quantity}"
            );
        }

        $quantityBefore = $stock->quantity;
        $stock->quantity -= $quantity;
        $stock->updated_at = now();
        $stock->save();

        return [[
            'warehouse_id' => $warehouseId,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $stock->quantity,
            'quantity_moved' => $quantity,
        ]];
    }

    /**
     * Add stock to warehouse
     */
    private function addStockToWarehouse(int $itemId, float $quantity, int $warehouseId): array
    {
        $item = Item::find($itemId);
        
        $stock = Stock::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
            ],
            [
                'quantity' => 0,
                'reorder_level' => $item->reorder_level ?? 0,
                'created_at' => now(),
            ]
        );

        // Lock the record for update
        $stock = Stock::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->lockForUpdate()
            ->first();

        $quantityBefore = $stock->quantity;
        $stock->quantity += $quantity;
        $stock->updated_at = now();
        $stock->save();

        return [[
            'warehouse_id' => $warehouseId,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $stock->quantity,
            'quantity_moved' => $quantity,
        ]];
    }

    /**
     * Remove stock from branch (optimized)
     */
    private function removeStockFromBranch(int $itemId, float $quantity, int $branchId): array
    {
        $branch = Branch::with('warehouses')->find($branchId);
        if (!$branch || $branch->warehouses->isEmpty()) {
            $wh = $this->ensureBranchWarehouse($branchId);
            // refresh relation
            $branch = Branch::with('warehouses')->find($branchId);
        }

        // Get all stocks in branch with locking
        $stocks = Stock::whereIn('warehouse_id', $branch->warehouses->pluck('id'))
            ->where('item_id', $itemId)
            ->where('quantity', '>', 0)
            ->lockForUpdate()
            ->orderBy('quantity', 'desc') // Take from warehouses with most stock first
            ->get();

        $totalAvailable = $stocks->sum('quantity');
        
        if ($totalAvailable < $quantity) {
            $item = Item::find($itemId);
            throw new TransferException(
                "Insufficient stock of {$item->name} in branch. " .
                "Available: {$totalAvailable}, Required: {$quantity}"
            );
        }

        $movements = [];
        $remainingQuantity = $quantity;

        foreach ($stocks as $stock) {
            if ($remainingQuantity <= 0) break;

            $takeQuantity = min($stock->quantity, $remainingQuantity);
            $quantityBefore = $stock->quantity;
            
            $stock->quantity -= $takeQuantity;
            $stock->updated_at = now();
            $stock->save();

            $movements[] = [
                'warehouse_id' => $stock->warehouse_id,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $stock->quantity,
                'quantity_moved' => $takeQuantity,
            ];

            $remainingQuantity -= $takeQuantity;
        }

        return $movements;
    }

    /**
     * Add stock to branch (to primary warehouse)
     */
    private function addStockToBranch(int $itemId, float $quantity, int $branchId): array
    {
        $branch = Branch::with('warehouses')->find($branchId);
        if (!$branch || $branch->warehouses->isEmpty()) {
            $wh = $this->ensureBranchWarehouse($branchId);
            $branch = Branch::with('warehouses')->find($branchId);
        }

        // Add to primary warehouse (first one) or warehouse with existing stock
        $warehouse = $branch->warehouses
            ->sortByDesc(function ($wh) use ($itemId) {
                return Stock::where('warehouse_id', $wh->id)
                    ->where('item_id', $itemId)
                    ->value('quantity') ?? 0;
            })
            ->first();

        return $this->addStockToWarehouse($itemId, $quantity, $warehouse->id);
    }

    /**
     * Get available stock (total - reserved)
     */
    public function getAvailableStock(int $itemId, string $locationType, int $locationId): float
    {
        if ($locationType === 'warehouse') {
            return Stock::where('warehouse_id', $locationId)
                ->where('item_id', $itemId)
                ->value('quantity') ?? 0;
        }

        // For branch, sum all warehouses
        $branch = Branch::with('warehouses')->find($locationId);
        if (!$branch) return 0;
        if ($branch->warehouses->isEmpty()) {
            $this->ensureBranchWarehouse($locationId);
            $branch = Branch::with('warehouses')->find($locationId);
            if (!$branch) return 0;
        }

        return Stock::whereIn('warehouse_id', $branch->warehouses->pluck('id'))
            ->where('item_id', $itemId)
            ->sum('quantity');
    }

    /**
     * Get reserved stock
     */
    public function getReservedStock(int $itemId, string $locationType, int $locationId): float
    {
        return StockReservation::where('item_id', $itemId)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('expires_at', '>', now())
            ->sum('quantity');
    }

    /**
     * Record stock history with all details
     */
    private function recordStockHistory(
        int $warehouseId,
        int $itemId,
        float $quantityBefore,
        float $quantityAfter,
        float $quantityChange,
        string $referenceType,
        int $referenceId,
        string $description,
        int $userId
    ): void {
        StockHistory::create([
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'quantity_change' => $quantityChange,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * Clean up expired reservations
     */
    public function cleanupExpiredReservations(): int
    {
        return StockReservation::where('expires_at', '<', now())->delete();
    }

    /**
     * Get stock movements for reporting
     */
    public function getStockMovements(int $itemId, ?int $warehouseId = null, ?string $dateFrom = null, ?string $dateTo = null): \Illuminate\Support\Collection
    {
        $query = StockHistory::where('item_id', $itemId)
            ->with(['warehouse', 'item', 'user']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
} 
