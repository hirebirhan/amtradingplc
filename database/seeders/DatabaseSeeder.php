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
            WarehouseSeeder::class,     // 2. Create warehouses (depends on branches)
            CategorySeeder::class,      // 3. Create categories
            ItemSeeder::class,          // 4. Create items (depends on categories)

            // Create roles, permissions, and users
            // Note: We're using the migrations to create roles and permissions for better version control
            // and to ensure the schema is properly set up in production
            SuperAdminSeeder::class,    // 5. Create SuperAdmin user
            UserSeeder::class,          // 6. Create other users

            // Create stock data
            StockSeeder::class,         // 7. Create stock (depends on items and warehouses)
            PriceHistorySeeder::class,   // 8. Create price history (depends on items)
            CustomerSeeder::class,      // 9. Create customers (with different types and credit limits)
        ]);
    }
}
