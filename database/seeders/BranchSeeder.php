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
        // Create a main branch
        Branch::create([
            'name' => 'Main Branch',
            'code' => 'BR-MAIN',
            'address' => 'Addis Ababa, Ethiopia',
            'phone' => '+251-11-123-4567',
            'email' => 'main@stock360.com',
            'is_active' => true,
        ]);

        // Create demo branches if needed
        if (app()->environment('local', 'development', 'testing')) {
            Branch::factory()->count(2)->create();
        }
    }
}
