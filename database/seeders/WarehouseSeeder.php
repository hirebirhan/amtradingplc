<?php

namespace Database\Seeders;

use App\Models\{Warehouse, Branch};
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();
        
        if ($branches->isEmpty()) {
            return;
        }

        $warehouses = [
            [
                'name' => 'Main Warehouse',
                'code' => 'WH-001',
                'address' => 'Merkato, Addis Ababa',
            ],
            [
                'name' => 'Branch Warehouse A',
                'code' => 'WH-002', 
                'address' => 'Bole, Addis Ababa',
            ]
        ];

        foreach ($warehouses as $warehouseData) {
            $warehouse = Warehouse::firstOrCreate(
                ['code' => $warehouseData['code']], 
                $warehouseData
            );
            
            // Attach to first branch
            $warehouse->branches()->syncWithoutDetaching($branches->first()->id);
        }
    }
}