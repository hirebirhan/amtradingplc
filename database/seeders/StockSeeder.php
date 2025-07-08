<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\StockHistory;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = Warehouse::all();
        $items = Item::all();

        // Set specific stock levels for each item in each warehouse
        foreach ($warehouses as $warehouse) {
            foreach ($items as $item) {
                // Generate different stock levels based on item
                $quantity = $this->generateStockLevel($item);

                    Stock::create([
                        'warehouse_id' => $warehouse->id,
                        'item_id' => $item->id,
                        'quantity' => $quantity,
                    ]);

                // Create a history record for initial stock
                    if ($quantity > 0) {
                    StockHistory::create([
                        'item_id' => $item->id,
                            'warehouse_id' => $warehouse->id,
                            'quantity_before' => 0,
                            'quantity_after' => $quantity,
                            'quantity_change' => $quantity,
                            'reference_type' => 'Initial',
                            'reference_id' => null,
                            'description' => 'Initial inventory',
                            'user_id' => null,
                        ]);
                    }
                }
            }
        }

    /**
     * Generate appropriate stock level for an item.
     * We'll make some items have low stock to test reordering functionality.
     */
    private function generateStockLevel(Item $item): int
    {
        $sku = $item->sku;

        // Low stock items (below reorder level)
        if (str_contains($sku, 'LAP-APPLE-MBP-M2')) {
            // MacBooks are popular and low in stock
            return max(0, $item->reorder_level - 1);
        }

        // Normal stock items
        if (str_contains($sku, 'LAP-DELL-XPS13')) {
            return rand($item->reorder_level + 2, $item->reorder_level + 10);
        }

        if (str_contains($sku, 'LAP-LEN-TPX1C')) {
            return rand($item->reorder_level, $item->reorder_level + 7);
        }

        if (str_contains($sku, 'DSK-HP-PVLNGM')) {
            return rand($item->reorder_level + 1, $item->reorder_level + 5);
        }

        if (str_contains($sku, 'DSK-DELL-OPT7090')) {
            // Business desktops are well-stocked
            return rand($item->reorder_level + 5, $item->reorder_level + 15);
        }

        // Default case for any other items
        return rand($item->reorder_level, $item->reorder_level * 3);
    }
}
