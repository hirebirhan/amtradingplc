<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update SuperAdmin user (idempotent by email)
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@amtradingplc.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'position' => 'System Administrator',
                'is_active' => true,
            ]
        );

        // Assign SuperAdmin role
        $superAdmin->assignRole('SuperAdmin');
    }
}
