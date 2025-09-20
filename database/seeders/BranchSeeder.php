<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Idempotent: create or update fixed branches
        Branch::updateOrCreate(
            ['code' => 'BR-BFOK'],
            [
                'name' => 'Bicha Fok Branch',
                'address' => 'Addis Ababa, Ethiopia',
                'phone' => '+251-11-123-4567',
                'email' => 'main@amtradingplc.com',
                'is_active' => true,
            ]
        );

        Branch::updateOrCreate(
            ['code' => 'BR-MERC'],
            [
                'name' => 'Mercato Branch',
                'address' => 'Mercato, Addis Ababa, Ethiopia',
                'phone' => '+251-11-234-5678',
                'email' => 'mercato@amtradingplc.com',
                'is_active' => true,
            ]
        );

        Branch::updateOrCreate(
            ['code' => 'BR-FURI'],
            [
                'name' => 'Furi Branch',
                'address' => 'Furi, Addis Ababa, Ethiopia',
                'phone' => '+251-11-345-6789',
                'email' => 'furi@amtradingplc.com',
                'is_active' => true,
            ]
        );

        // Optional demo branches are not auto-created to avoid duplicates
    }
}
