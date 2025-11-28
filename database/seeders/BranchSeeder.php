<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Enums\BranchCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (BranchCode::cases() as $branchCode) {
            Branch::updateOrCreate(
                ['code' => $branchCode->value],
                [
                    'name' => $branchCode->getName(),
                    'address' => 'Addis Ababa, Ethiopia',
                    'phone' => '+251-11-123-4567',
                    'email' => strtolower(str_replace(' ', '', $branchCode->getName())) . '@amtradingplc.com',
                    'is_active' => true,
                ]
            );
        }
    }
}
