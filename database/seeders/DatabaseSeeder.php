<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,        // 1. Create branches first
            WarehouseSeeder::class,     // 2. Create warehouses
            CategorySeeder::class,      // 3. Create categories
            SupplierSeeder::class,      // 4. Create suppliers

            RoleAndPermissionSeeder::class, // 5. Sync roles and permissions
            SuperAdminSeeder::class,    // 6. Create SuperAdmin user
            UserSeeder::class,          // 7. Create other users
            CustomerSeeder::class,      // 8. Create customers (with different types and credit limits)
            ItemSeeder::class,          // 9. Create items with stock
        ]);
    }
}
