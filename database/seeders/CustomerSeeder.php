<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific Ethiopian customers
        $customerData = [
            [
                'name' => 'Abebe Kebede',
                'phone' => '0911000001',
                'email' => 'abebe.kebede@example.com',
                'address' => 'Bole Road, Addis Ababa, Ethiopia',
                'customer_type' => 'retail',
                'credit_limit' => 25000,
                'is_active' => true,
            ],
            [
                'name' => 'Mekdes Hailu',
                'phone' => '0922000002',
                'email' => 'mekdes.h@example.com',
                'address' => 'Piazza, Addis Ababa, Ethiopia',
                'customer_type' => 'wholesale',
                'credit_limit' => 100000,
                'is_active' => true,
            ],
            [
                'name' => 'Yonas Tesfaye',
                'phone' => '0933000003',
                'email' => 'yonas.t@example.com',
                'address' => 'Megenagna, Addis Ababa, Ethiopia',
                'customer_type' => 'wholesale',
                'credit_limit' => 150000,
                'is_active' => true,
            ]
        ];

        foreach ($customerData as $data) {
            Customer::firstOrCreate(
                ['email' => $data['email']], 
                $data
            );
        }
    }
}
