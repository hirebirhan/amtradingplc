<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainBranch = Branch::where('code', 'BR-MAIN')->first();

        if ($mainBranch) {
            // Create a main warehouse
            $mainWarehouse = Warehouse::create([
                'name' => 'Main Warehouse',
                'code' => 'WH-MAIN',
                'address' => 'Addis Ababa, Ethiopia',
                'manager_name' => 'Warehouse Manager',
                'phone' => '+251-11-123-4568',
            ]);

            // Attach to main branch using the many-to-many relationship
            $mainWarehouse->branches()->attach($mainBranch->id);

            // Create another warehouse for main branch
            $secondaryWarehouse = Warehouse::create([
                'name' => 'Secondary Warehouse',
                'code' => 'WH-SEC',
                'address' => 'Addis Ababa, Ethiopia',
                'manager_name' => 'Secondary Manager',
                'phone' => '+251-11-123-4569',
            ]);

            // Attach to main branch using the many-to-many relationship
            $secondaryWarehouse->branches()->attach($mainBranch->id);
        }

        // Create warehouses for other branches
        $otherBranches = Branch::where('code', '!=', 'BR-MAIN')->get();
        foreach ($otherBranches as $branch) {
            $warehouse = Warehouse::factory()->create();
            // Attach the warehouse to the branch
            $warehouse->branches()->attach($branch->id);
        }
    }
}
