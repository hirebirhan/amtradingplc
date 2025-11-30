<?php

namespace Database\Seeders;

use App\Models\{Item, Category, Stock, Warehouse};
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();
        $warehouses = Warehouse::all();
        
        if ($categories->isEmpty() || $warehouses->isEmpty()) {
            return;
        }

        $items = [
            [
                'name' => 'Ethiopian Coffee Beans',
                'sku' => 'COFFEE-001',
                'category_id' => $categories->first()->id,
                'cost_price' => 150.00,
                'selling_price' => 200.00,
                'unit_quantity' => 1,
                'item_unit' => 'kg',
                'stock_qty' => 100
            ],
            [
                'name' => 'Berbere Spice Mix',
                'sku' => 'SPICE-001', 
                'category_id' => $categories->first()->id,
                'cost_price' => 80.00,
                'selling_price' => 120.00,
                'unit_quantity' => 1,
                'item_unit' => 'kg',
                'stock_qty' => 50
            ],
            [
                'name' => 'Teff Flour',
                'sku' => 'FLOUR-001',
                'category_id' => $categories->first()->id,
                'cost_price' => 45.00,
                'selling_price' => 65.00,
                'unit_quantity' => 1,
                'item_unit' => 'kg',
                'stock_qty' => 200
            ]
        ];

        foreach ($items as $itemData) {
            $stockQty = $itemData['stock_qty'];
            unset($itemData['stock_qty']);
            
            $item = Item::firstOrCreate(
                ['sku' => $itemData['sku']], 
                $itemData
            );

            // Create stock for each warehouse
            foreach ($warehouses as $warehouse) {
                Stock::firstOrCreate([
                    'warehouse_id' => $warehouse->id,
                    'item_id' => $item->id,
                ], [
                    'quantity' => $stockQty,
                    'piece_count' => $stockQty,
                    'total_units' => $stockQty,
                ]);
            }
        }
    }
}