<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all branches to associate suppliers with branches
        $branches = Branch::all();

        // If no branches exist, create suppliers without branch association
        $hasBranches = $branches->count() > 0;

        // Create specific suppliers for manufacturing sectors
        $companyTypes = [
            'Manufacturing',
            'Distribution',
            'Wholesale',
            'Import/Export',
            'Local Producer',
            'Direct Factory',
        ];

        $industries = [
            'Electronics',
            'Food & Beverage',
            'Textiles',
            'Construction',
            'Automotive',
            'Plastics',
            'Metal Works',
            'Chemicals',
            'Pharmaceuticals',
            'Office Supplies'
        ];

        // Create 30 suppliers
        for ($i = 0; $i < 30; $i++) {
            $industry = $faker->randomElement($industries);
            $companyType = $faker->randomElement($companyTypes);
            $companyName = $faker->company . ' ' . $companyType;

            // 80% of the time, make the company name sound like a supplier
            if ($faker->boolean(80)) {
                $companyName = $industry . ' ' . $companyName;
            }

            Supplier::create([
                'name' => $companyName,
                'email' => $faker->companyEmail(),
                'phone' => $faker->phoneNumber(),
                'address' => $faker->streetAddress(),
                'city' => $faker->city(),
                'state' => $faker->state(),
                'postal_code' => $faker->postcode(),
                'country' => $faker->country(),
                'tax_number' => $faker->bothify('TX-#####-??-##'),
                'branch_id' => $hasBranches ? $branches->random()->id : null,
                'is_active' => $faker->boolean(90), // 90% of suppliers are active
                'notes' => $faker->boolean(70) ? $faker->paragraph(2) : null, // 70% have notes
            ]);
        }

        // Create a few suppliers with specific product categories for your application
        $specificSuppliers = [
            [
                'name' => 'TechSource Electronics',
                'category' => 'Electronics',
                'notes' => 'Our primary supplier for all computer hardware and accessories.'
            ],
            [
                'name' => 'Global Office Solutions',
                'category' => 'Office Supplies',
                'notes' => 'Main supplier for office furniture and stationery items.'
            ],
            [
                'name' => 'Fresh Harvest Distributors',
                'category' => 'Food & Beverage',
                'notes' => 'Supplies fresh produce and packaged foods with reliable cold chain logistics.'
            ],
            [
                'name' => 'Industrial Hardware Co.',
                'category' => 'Construction',
                'notes' => 'Specialized in construction materials and industrial tools.'
            ],
            [
                'name' => 'Textile World Imports',
                'category' => 'Textiles',
                'notes' => 'Premium textile supplier with wide range of fabrics and materials.'
            ]
        ];

        foreach ($specificSuppliers as $supplierData) {
            Supplier::create([
                'name' => $supplierData['name'],
                'email' => $faker->companyEmail(),
                'phone' => $faker->phoneNumber(),
                'address' => $faker->streetAddress(),
                'city' => $faker->city(),
                'state' => $faker->state(),
                'postal_code' => $faker->postcode(),
                'country' => $faker->country(),
                'tax_number' => $faker->bothify('TX-#####-??-##'),
                'branch_id' => $hasBranches ? $branches->random()->id : null,
                'is_active' => true,
                'notes' => $supplierData['notes'],
            ]);
        }
    }
}
