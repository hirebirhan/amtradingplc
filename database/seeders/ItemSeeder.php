<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find our categories
        $seedsCategory = Category::where('code', 'CAT-SEEDS')->first();
        $oilsCategory = Category::where('code', 'CAT-OILS')->first();
        $flourCategory = Category::where('code', 'CAT-FLOUR')->first();
        $spicesCategory = Category::where('code', 'CAT-SPICES')->first();
        $bulkCategory = Category::where('code', 'CAT-BULK')->first();

        // Create items relevant for seeds, oils, and flour distribution business
        $items = [
            [
                'name' => 'Teff Seeds (White) - Premium Grade',
                'sku' => 'TEFF-WHT-001',
                'barcode' => '2510001234567',
                'category_id' => $seedsCategory->id,
                'cost_price' => 85.00,
                'selling_price' => 120.00,
                'reorder_level' => 50,
                'unit' => 'KG',
                'brand' => 'Ethiopian Premium',
                'description' => 'High quality white teff seeds, premium grade for flour production and direct consumption. Sourced from Arsi highlands.',
                'is_active' => true,
            ],
            [
                'name' => 'Sunflower Cooking Oil - 1L',
                'sku' => 'SUNF-1L-001',
                'barcode' => '2510002345678',
                'category_id' => $oilsCategory->id,
                'cost_price' => 165.00,
                'selling_price' => 195.00,
                'reorder_level' => 100,
                'unit' => 'PCS',
                'brand' => 'Golden Sun',
                'description' => 'Pure sunflower cooking oil in 1-liter bottles. High quality refined oil suitable for cooking and frying.',
                'is_active' => true,
            ],
            [
                'name' => 'Wheat Flour (All Purpose) - 25KG',
                'sku' => 'WHEAT-25KG-001',
                'barcode' => '2510003456789',
                'category_id' => $flourCategory->id,
                'cost_price' => 1350.00,
                'selling_price' => 1520.00,
                'reorder_level' => 20,
                'unit' => 'BAG',
                'brand' => 'Habesha Mills',
                'description' => '25kg bag of all-purpose wheat flour. Perfect for bread making, injera, and general baking purposes.',
                'is_active' => true,
            ],
            [
                'name' => 'Ethiopian Berbere Spice Mix - 500G',
                'sku' => 'BERB-500G-001',
                'barcode' => '2510004567890',
                'category_id' => $spicesCategory->id,
                'cost_price' => 280.00,
                'selling_price' => 350.00,
                'reorder_level' => 30,
                'unit' => 'PCS',
                'brand' => 'Sheba Spices',
                'description' => 'Authentic Ethiopian berbere spice blend in 500g package. Premium quality blend of chilies and traditional spices.',
                'is_active' => true,
            ],
            [
                'name' => 'Red Kidney Beans - 50KG Bulk',
                'sku' => 'BEAN-RK-50KG',
                'barcode' => '2510005678901',
                'category_id' => $bulkCategory->id,
                'cost_price' => 4200.00,
                'selling_price' => 4800.00,
                'reorder_level' => 10,
                'unit' => 'BAG',
                'brand' => 'Ethiopian Commodities',
                'description' => '50kg bulk bag of premium red kidney beans. Perfect for wholesale distribution and food processing.',
                'is_active' => true,
            ],
        ];

        // Create the items
        foreach ($items as $item) {
            Item::create($item);
        }
    }
}
