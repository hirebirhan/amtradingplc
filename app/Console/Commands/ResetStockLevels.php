<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Console\Command;

class ResetStockLevels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:reset {--keep-history : Keep the stock history records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all stock levels to zero';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting stock level reset...');

        $keepHistory = $this->option('keep-history');

        // Truncate stock table (delete all stock records)
        if ($this->confirm('Are you sure you want to reset all stock levels to zero?', true)) {
            $stockCount = Stock::count();

            if (!$keepHistory) {
                // Delete stock history records
                $historyCount = \App\Models\StockHistory::count();
                \App\Models\StockHistory::truncate();
                $this->info("Deleted {$historyCount} stock history records.");
            }

            // Delete all stock records
            Stock::truncate();
            $this->info("Deleted {$stockCount} stock records.");

            // Create stock entries with 0 quantity for all items in all warehouses
            $items = Item::all();
            $warehouses = Warehouse::all();
            $stockCreated = 0;

            $this->output->progressStart($items->count() * $warehouses->count());

            foreach ($items as $item) {
                foreach ($warehouses as $warehouse) {
                    Stock::create([
                        'item_id' => $item->id,
                        'warehouse_id' => $warehouse->id,
                        'quantity' => 0
                    ]);
                    $stockCreated++;
                    $this->output->progressAdvance();
                }
            }

            $this->output->progressFinish();
            $this->info("Created {$stockCreated} new stock records with zero quantity.");

            $this->info('Stock levels have been reset to zero successfully!');
        } else {
            $this->info('Operation cancelled.');
        }
    }
}
