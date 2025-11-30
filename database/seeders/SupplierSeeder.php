<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Eibrahim Trading',
                'email' => 'eibrahim@supplier.com',
                'phone' => '+251-911-123456',
                'address' => 'Addis Ababa, Ethiopia',
                'is_active' => true,
            ],
            [
                'name' => 'Merkato Wholesale',
                'email' => 'info@merkato.com',
                'phone' => '+251-911-234567',
                'address' => 'Merkato, Addis Ababa',
                'is_active' => true,
            ],
            [
                'name' => 'Ethiopian Spice Co.',
                'email' => 'sales@ethspice.com',
                'phone' => '+251-911-345678',
                'address' => 'Bole, Addis Ababa',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['name' => $supplier['name']],
                $supplier
            );
        }
    }
}