<?php

namespace App\Services;

use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Item;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\User;
use App\Services\StockMovementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\TransferException;

class TransferService
{
    private StockMovementService $stockMovementService;

    public function __construct()
    {
        $this->stockMovementService = new StockMovementService();
    }

    /**
     * Create a new transfer with validation and stock reservation
     */
    public function createTransfer(array $transferData, array $items, User $user): Transfer
    {
        $this->validateTransferData($transferData, $items, $user);
        
        return DB::transaction(function () use ($transferData, $items, $user) {
            // Create transfer
            $transfer = Transfer::create([
                ...$transferData,
                'user_id' => $user->id,
                'status' => 'pending',
                'reference_code' => $this->generateReferenceCode(),
                'date_initiated' => now(),
            ]);

            // Create transfer items
            $this->createTransferItems($transfer, $items);

            // Reserve stock to prevent overselling
            try {
                $this->stockMovementService->reserveStock(
                    $items,
                    $transferData['source_type'],
                    $transferData['source_id'],
                    $transfer->id,
                    $user->id
                );
            } catch (TransferException $e) {
                // If stock reservation fails, clean up and re-throw
                $transfer->delete();
                throw $e;
            }

            return $transfer;
        });
    }

    /**
     * Process transfer through approval workflow
     */
    public function processTransferWorkflow(Transfer $transfer, User $user, string $action): Transfer
    {
        return DB::transaction(function () use ($transfer, $user, $action) {
            switch ($action) {
                case 'approve':
                    $this->approveTransfer($transfer, $user);
                    break;
                case 'reject':
                    $this->rejectTransfer($transfer, $user);
                    break;
                case 'mark_in_transit':
                    $this->markInTransit($transfer, $user);
                    break;
                case 'complete':
                    $this->completeTransfer($transfer, $user);
                    break;
                case 'cancel':
                    $this->cancelTransfer($transfer, $user);
                    break;
                default:
                    throw new TransferException("Invalid action: {$action}");
            }

            return $transfer;
        });
    }

    /**
     * Validate stock availability for all items
     */
    public function validateStockAvailability(array $items, string $sourceType, int $sourceId): array
    {
        $stockIssues = [];

        foreach ($items as $item) {
            $availableStock = $this->stockMovementService->getAvailableStock(
                $item['item_id'],
                $sourceType,
                $sourceId
            );
            
            $reservedStock = $this->stockMovementService->getReservedStock(
                $item['item_id'],
                $sourceType,
                $sourceId
            );
            
            $actuallyAvailable = $availableStock - $reservedStock;
            
            if ($item['quantity'] > $actuallyAvailable) {
                $stockIssues[] = [
                    'item_name' => $item['item_name'] ?? "Item ID: {$item['item_id']}",
                    'requested' => $item['quantity'],
                    'available' => $actuallyAvailable,
                    'total_stock' => $availableStock,
                    'reserved' => $reservedStock,
                ];
            }
        }

        return $stockIssues;
    }

    /**
     * Get available stock for an item at a location (delegates to StockMovementService)
     */
    public function getAvailableStock(int $itemId, string $locationType, int $locationId): float
    {
        return $this->stockMovementService->getAvailableStock($itemId, $locationType, $locationId);
    }

    /**
     * Generate unique reference code
     */
    private function generateReferenceCode(): string
    {
        do {
            $code = 'TRF-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Transfer::where('reference_code', $code)->exists());

        return $code;
    }

    /**
     * Validate transfer data before creation
     */
    private function validateTransferData(array $transferData, array $items, User $user): void
    {
        // Branch-only mode
        if (($transferData['source_type'] ?? 'branch') !== 'branch' || ($transferData['destination_type'] ?? 'branch') !== 'branch') {
            throw new TransferException('Only branch-to-branch transfers are supported in this setup.');
        }

        // Validate different locations
        if ($transferData['source_type'] === $transferData['destination_type'] && 
            $transferData['source_id'] === $transferData['destination_id']) {
            throw new TransferException('Source and destination locations must be different.');
        }

        // Validate user permissions
        $transfer = new Transfer();
        if (!$transfer->canUserCreateFrom($user, $transferData['source_type'], $transferData['source_id'])) {
            throw new TransferException('You do not have permission to create transfers from this location.');
        }

        // Validate stock availability
        $stockIssues = $this->validateStockAvailability($items, $transferData['source_type'], $transferData['source_id']);
        
        if (!empty($stockIssues)) {
            $errorMessage = "Insufficient stock for:\n";
            foreach ($stockIssues as $issue) {
                $errorMessage .= "- {$issue['item_name']}: Requested {$issue['requested']}, Available {$issue['available']}";
                if ($issue['reserved'] > 0) {
                    $errorMessage .= " (Total: {$issue['total_stock']}, Reserved: {$issue['reserved']})";
                }
                $errorMessage .= "\n";
            }
            throw new TransferException($errorMessage);
        }
    }

    /**
     * Create transfer items
     */
    private function createTransferItems(Transfer $transfer, array $items): void
    {
        $itemIds = collect($items)->pluck('item_id');
        $itemModels = Item::whereIn('id', $itemIds)->pluck('cost_price', 'id');
        
        foreach ($items as $item) {
            $unitCost = $itemModels->get($item['item_id'], 0);
            
            TransferItem::create([
                'transfer_id' => $transfer->id,
                'item_id' => (int)$item['item_id'],
                'quantity' => (float)$item['quantity'],
                'unit_cost' => (float)$unitCost,
            ]);
        }
    }

    /**
     * Approve transfer
     */
    private function approveTransfer(Transfer $transfer, User $user): void
    {
        if ($transfer->status !== 'pending') {
            throw new TransferException('Only pending transfers can be approved.');
        }

        $transfer->approve($user);
    }

    /**
     * Complete transfer (move stock)
     */
    private function completeTransfer(Transfer $transfer, User $user): void
    {
        if (!in_array($transfer->status, ['approved', 'in_transit'])) {
            throw new TransferException('Transfer must be approved or in transit before completion.');
        }

        // Prepare items data for stock movement
        $items = $transfer->items->map(function ($item) {
            return [
                'item_id' => $item->item_id,
                'quantity' => $item->quantity,
            ];
        })->toArray();

        // Execute stock movement
        $this->stockMovementService->executeTransferMovement(
            $items,
            $transfer->source_type,
            $transfer->source_id,
            $transfer->destination_type,
            $transfer->destination_id,
            $transfer->id,
            $user->id
        );

        // Update transfer status
        $transfer->update(['status' => 'completed']);
    }

    /**
     * Reject transfer
     */
    private function rejectTransfer(Transfer $transfer, User $user): void
    {
        $transfer->reject($user);
        
        // Release reserved stock
        $this->stockMovementService->releaseReservedStock($transfer->id);
    }

    /**
     * Mark transfer in transit
     */
    private function markInTransit(Transfer $transfer, User $user): void
    {
        if ($transfer->status !== 'approved') {
            throw new TransferException('Only approved transfers can be marked in transit.');
        }

        $transfer->update(['status' => 'in_transit']);
    }

    /**
     * Cancel transfer
     */
    private function cancelTransfer(Transfer $transfer, User $user): void
    {
        $transfer->cancel($user);
        
        // Release reserved stock
        $this->stockMovementService->releaseReservedStock($transfer->id);
    }
} 
