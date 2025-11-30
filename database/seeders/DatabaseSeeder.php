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

            // Create roles, permissions, and users
            // Note: We're using the migrations to create roles and permissions for better version control
            // and to ensure the schema is properly set up in production
            SuperAdminSeeder::class,    // 5. Create SuperAdmin user
            UserSeeder::class,          // 6. Create other users
            CustomerSeeder::class,      // 7. Create customers (with different types and credit limits)
            ItemSeeder::class,          // 8. Create items with stock
        ]);
    }
}
