<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create agricultural suppliers focused on seeds and crops
        $supplierData = [
            [
                'name' => 'Ethio Seed Enterprise',
                'email' => 'info@ethioseed.com',
                'phone' => '0911222333',
                'company' => 'Ethio Seed Enterprise PLC',
                'address' => 'Debre Zeit, Oromia, Ethiopia',
                'company_type' => 'Agriculture',
                'industry' => 'Seeds & Crops',
                'is_active' => true,
                'tax_number' => 'TAX00123456',
                'payment_terms' => 'Net 30',
                'notes' => 'Specializes in high-yield seed varieties',
            ],
            [
                'name' => 'Green Valley Agritech',
                'email' => 'sales@greenvalleyagri.com',
                'phone' => '0922333444',
                'company' => 'Green Valley Agritech Solutions',
                'address' => 'Bishoftu, Oromia, Ethiopia',
                'company_type' => 'Agriculture',
                'industry' => 'Seeds & Crops',
                'is_active' => true,
                'tax_number' => 'TAX00765432',
                'payment_terms' => 'Net 45',
                'notes' => 'Supplier of organic seeds and crop protection products',
            ],
            [
                'name' => 'Rift Valley Seeds',
                'email' => 'info@riftvalleyseeds.com',
                'phone' => '0933444555',
                'company' => 'Rift Valley Seeds Co.',
                'address' => 'Ziway, Oromia, Ethiopia',
                'company_type' => 'Agriculture',
                'industry' => 'Seeds & Crops',
                'is_active' => true,
                'tax_number' => 'TAX00567890',
                'payment_terms' => 'Net 30',
                'notes' => 'Specializes in drought-resistant crop varieties',
            ]
        ];

        foreach ($supplierData as $data) {
            Supplier::create($data);
        }
    }
}
