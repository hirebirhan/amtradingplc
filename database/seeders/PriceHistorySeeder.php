<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\PriceHistory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PriceHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = Item::all();

        // For each item, create price history records
        foreach ($items as $item) {
            // Initial price (when the item was first added)
            $initialDate = Carbon::now()->subMonths(rand(6, 12));

            // For each item, determine initial cost and selling prices based on their current values
            $initialCostPrice = $this->getInitialCostPrice($item);
            $initialSellingPrice = $this->getInitialSellingPrice($item);

            // Create the initial price history record
            PriceHistory::create([
                'item_id' => $item->id,
                'old_cost' => null,
                'new_cost' => $initialCostPrice,
                'old_price' => null,
                'new_price' => $initialSellingPrice,
                'change_type' => 'manual',
                'notes' => 'Initial pricing',
                'user_id' => null, // No users in database yet
                'created_at' => $initialDate,
                'updated_at' => $initialDate,
            ]);

            // Add 1-3 price changes over time for each item
            $numberOfChanges = rand(1, 3);
            $lastCostPrice = $initialCostPrice;
            $lastSellingPrice = $initialSellingPrice;
            $lastDate = $initialDate;

            for ($i = 0; $i < $numberOfChanges; $i++) {
                // Set the date for this price change (1-3 months after the previous change)
                $changeDate = Carbon::parse($lastDate)->addMonths(rand(1, 3));

                // Stop if we've reached the present or future
                if ($changeDate >= Carbon::now()) {
                    break;
                }

                // Calculate new prices based on specific scenarios for each item
                [$newCostPrice, $newSellingPrice, $reason] = $this->calculatePriceChange(
                    $item,
                    $lastCostPrice,
                    $lastSellingPrice,
                    $i + 1
                );

                // Create the price history record
                PriceHistory::create([
                    'item_id' => $item->id,
                    'old_cost' => $lastCostPrice,
                    'new_cost' => $newCostPrice,
                    'old_price' => $lastSellingPrice,
                    'new_price' => $newSellingPrice,
                    'change_type' => 'manual',
                    'notes' => $reason,
                    'user_id' => null, // No users in database yet
                    'created_at' => $changeDate,
                    'updated_at' => $changeDate,
                ]);

                // Update the last values for the next iteration
                $lastCostPrice = $newCostPrice;
                $lastSellingPrice = $newSellingPrice;
                $lastDate = $changeDate;
            }

            // Final price history entry to match current prices
            if ($lastCostPrice != $item->cost_price || $lastSellingPrice != $item->selling_price) {
                $finalDate = Carbon::now()->subDays(rand(1, 30));
                PriceHistory::create([
                    'item_id' => $item->id,
                    'old_cost' => $lastCostPrice,
                    'new_cost' => $item->cost_price,
                    'old_price' => $lastSellingPrice,
                    'new_price' => $item->selling_price,
                    'change_type' => 'manual',
                    'notes' => 'Current market adjustment',
                    'user_id' => null, // No users in database yet
                    'created_at' => $finalDate,
                    'updated_at' => $finalDate,
                ]);
            }
        }
    }

    /**
     * Get a realistic initial cost price for an item based on its current value
     */
    private function getInitialCostPrice(Item $item): float
    {
        $sku = $item->sku;
        $currentCost = $item->cost_price;

        // Most items started cheaper than they are now
        $randomFactor = rand(85, 95) / 100; // 85-95% of current price

        // Special cases
        if (str_contains($sku, 'LAP-APPLE-MBP-M2')) {
            // Apple products had higher initial cost (they rarely go down in price)
            return round($currentCost * 1.05, 2);
        }

        return round($currentCost * $randomFactor, 2);
    }

    /**
     * Get a realistic initial selling price for an item based on its current value
     */
    private function getInitialSellingPrice(Item $item): float
    {
        $sku = $item->sku;
        $currentSelling = $item->selling_price;

        // Most items started cheaper than they are now
        $randomFactor = rand(85, 95) / 100; // 85-95% of current price

        // Special cases
        if (str_contains($sku, 'LAP-APPLE-MBP-M2')) {
            // Apple products had higher initial price (they rarely go down in price)
            return round($currentSelling * 1.05, 2);
        }

        return round($currentSelling * $randomFactor, 2);
    }

    /**
     * Calculate price changes for an item based on its characteristics
     */
    private function calculatePriceChange(Item $item, float $lastCost, float $lastSelling, int $changeNumber): array
    {
        $sku = $item->sku;
        $reasons = [
            'Supplier price increase',
            'Market adjustment',
            'Promotional pricing',
            'Seasonal pricing',
            'Inflation adjustment',
            'Competitive pricing',
            'Component cost increase',
            'Foreign exchange impact',
            'Bulk purchase discount',
        ];

        // Default random changes
        $costChangeFactor = rand(-5, 10) / 100; // -5% to +10%
        $sellingChangeFactor = rand(-3, 12) / 100; // -3% to +12%
        $reason = $reasons[array_rand($reasons)];

        // Special case for Apple products
        if (str_contains($sku, 'LAP-APPLE-MBP-M2')) {
            // Apple products usually go up in price
            $costChangeFactor = rand(2, 7) / 100; // +2% to +7%
            $sellingChangeFactor = rand(3, 8) / 100; // +3% to +8%

            if ($changeNumber == 1) {
                $reason = 'New model release';
            } else {
                $reason = 'Component supply constraints';
            }
        }

        // HP Gaming Desktop special case
        if (str_contains($sku, 'DSK-HP-PVLNGM')) {
            if ($changeNumber == 1) {
                // Gaming desktops might have had more volatile prices due to GPU shortages
                $costChangeFactor = rand(5, 15) / 100; // +5% to +15%
                $sellingChangeFactor = rand(8, 18) / 100; // +8% to +18%
                $reason = 'GPU shortage impact';
            }
        }

        // Dell OptiPlex special case
        if (str_contains($sku, 'DSK-DELL-OPT7090') && $changeNumber == 2) {
            // Business desktops might have had price drops during sales
            $costChangeFactor = rand(-7, -2) / 100; // -7% to -2%
            $sellingChangeFactor = rand(-10, -5) / 100; // -10% to -5%
            $reason = 'Business bulk order promotion';
        }

        // Calculate new prices
        $newCostPrice = round($lastCost * (1 + $costChangeFactor), 2);
        $newSellingPrice = round($lastSelling * (1 + $sellingChangeFactor), 2);

        return [$newCostPrice, $newSellingPrice, $reason];
    }
}