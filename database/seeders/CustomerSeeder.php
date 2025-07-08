<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific customers
        $customerData = [
            [
                'name' => 'Retail Customer 1',
                'phone' => '0911111111',
                'email' => 'retail1@example.com',
                'address' => 'Addis Ababa, Ethiopia',
                'customer_type' => 'retail',
                'credit_limit' => 5000,
                'is_active' => true,
            ],
            [
                'name' => 'Retail Customer 2',
                'phone' => '0922222222',
                'email' => 'retail2@example.com',
                'address' => 'Bahir Dar, Ethiopia',
                'customer_type' => 'retail',
                'credit_limit' => 3000,
                'is_active' => true,
            ],
            [
                'name' => 'Wholesale Customer 1',
                'phone' => '0933333333',
                'email' => 'wholesale1@example.com',
                'address' => 'Hawassa, Ethiopia',
                'customer_type' => 'wholesale',
                'credit_limit' => 50000,
                'is_active' => true,
            ],
            [
                'name' => 'Wholesale Customer 2',
                'phone' => '0944444444',
                'email' => 'wholesale2@example.com',
                'address' => 'Mekelle, Ethiopia',
                'customer_type' => 'wholesale',
                'credit_limit' => 75000,
                'is_active' => true,
            ],
            [
                'name' => 'Distributor 1',
                'phone' => '0955555555',
                'email' => 'distributor1@example.com',
                'address' => 'Dire Dawa, Ethiopia',
                'customer_type' => 'distributor',
                'credit_limit' => 200000,
                'is_active' => true,
            ],
            [
                'name' => 'Inactive Customer',
                'phone' => '0966666666',
                'email' => 'inactive@example.com',
                'address' => 'Gondar, Ethiopia',
                'customer_type' => 'retail',
                'credit_limit' => 1000,
                'is_active' => false,
            ],
            [
                'name' => 'No Credit Customer',
                'phone' => '0977777777',
                'email' => 'nocredit@example.com',
                'address' => 'Jimma, Ethiopia',
                'customer_type' => 'retail',
                'credit_limit' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'High Credit Distributor',
                'phone' => '0988888888',
                'email' => 'bigdistributor@example.com',
                'address' => 'Adama, Ethiopia',
                'customer_type' => 'distributor',
                'credit_limit' => 500000,
                'is_active' => true,
            ],
        ];

        foreach ($customerData as $data) {
            Customer::create($data);
        }

        // Create additional random customers with different types
        Customer::factory(10)->retail()->create();
        Customer::factory(5)->wholesale()->create();
        Customer::factory(3)->distributor()->create();
        Customer::factory(2)->inactive()->create();
    }
}
