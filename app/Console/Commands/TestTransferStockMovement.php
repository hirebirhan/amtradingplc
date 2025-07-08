<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transfer;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\Item;
use App\Models\Warehouse;

class TestTransferStockMovement extends Command
{
    protected $signature = 'transfer:test-stock-movement {transfer_id?}';
    protected $description = 'Test and verify that transfers properly move stock between locations';

    public function handle()
    {
        $this->info('🔍 Testing Transfer Stock Movement...');
        
        $transferId = $this->argument('transfer_id');
        
        if ($transferId) {
            $transfer = Transfer::find($transferId);
            if (!$transfer) {
                $this->error("Transfer with ID {$transferId} not found.");
                return 1;
            }
            $this->testSpecificTransfer($transfer);
        } else {
            $this->testLatestTransfers();
        }
        
        return 0;
    }
    
    private function testLatestTransfers()
    {
        $transfers = Transfer::with(['items.item'])
            ->where('status', 'completed')
            ->latest()
            ->take(5)
            ->get();
            
        if ($transfers->isEmpty()) {
            $this->warn('No completed transfers found.');
            return;
        }
        
        $this->info("Found {$transfers->count()} recent completed transfers:");
        $this->line('');
        
        foreach ($transfers as $transfer) {
            $this->testSpecificTransfer($transfer);
            $this->line('');
        }
    }
    
    private function testSpecificTransfer(Transfer $transfer)
    {
        $this->info("📋 Transfer: {$transfer->reference_code}");
        $this->line("   From: {$transfer->source_location_name} → To: {$transfer->destination_location_name}");
        $this->line("   Status: {$transfer->status}");
        $this->line("   Items: {$transfer->items->count()}");
        
        // Get stock movements for this transfer
        $stockMovements = StockHistory::where('reference_type', 'transfer')
            ->where('reference_id', $transfer->id)
            ->with(['item', 'warehouse'])
            ->get();
            
        if ($stockMovements->isEmpty()) {
            $this->error("   ❌ NO STOCK MOVEMENTS FOUND! Stock was NOT moved.");
            return;
        }
        
        $this->info("   ✅ Stock movements found: {$stockMovements->count()}");
        
        // Group movements by item
        $movementsByItem = $stockMovements->groupBy('item_id');
        
        foreach ($transfer->items as $transferItem) {
            $itemMovements = $movementsByItem->get($transferItem->item_id, collect());
            
            $sourceMovement = $itemMovements->where('quantity_change', '<', 0)->first();
            $destMovement = $itemMovements->where('quantity_change', '>', 0)->first();
            
            $this->line("   📦 {$transferItem->item->name} (Qty: {$transferItem->quantity})");
            
            if ($sourceMovement) {
                $this->line("      📤 Removed {$sourceMovement->quantity_change} from {$sourceMovement->warehouse->name}");
                $this->line("         Stock: {$sourceMovement->quantity_before} → {$sourceMovement->quantity_after}");
            } else {
                $this->error("      ❌ No source removal found!");
            }
            
            if ($destMovement) {
                $this->line("      📥 Added +{$destMovement->quantity_change} to {$destMovement->warehouse->name}");
                $this->line("         Stock: {$destMovement->quantity_before} → {$destMovement->quantity_after}");
            } else {
                $this->error("      ❌ No destination addition found!");
            }
            
            // Verify quantities match
            if ($sourceMovement && $destMovement) {
                $sourceQty = abs($sourceMovement->quantity_change);
                $destQty = $destMovement->quantity_change;
                $requestedQty = $transferItem->quantity;
                
                if ($sourceQty == $destQty && $sourceQty == $requestedQty) {
                    $this->info("      ✅ Quantities match perfectly!");
                } else {
                    $this->error("      ❌ Quantity mismatch: Source={$sourceQty}, Dest={$destQty}, Requested={$requestedQty}");
                }
            }
        }
        
        // Summary
        $totalMovements = $stockMovements->count();
        $expectedMovements = $transfer->items->count() * 2; // source + destination for each item
        
        if ($totalMovements >= $expectedMovements) {
            $this->info("   🎉 TRANSFER WORKING CORRECTLY - Stock properly moved!");
        } else {
            $this->error("   ❌ INCOMPLETE TRANSFER - Expected {$expectedMovements} movements, found {$totalMovements}");
        }
    }
} 