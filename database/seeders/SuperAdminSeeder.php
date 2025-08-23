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
        // Create SuperAdmin user
        $superAdmin = User::create([
            'name' => 'System Administrator',
            'email' => 'superadmin@amtradingplc.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'position' => 'System Administrator',
            'is_active' => true,
        ]);

        // Assign SuperAdmin role
        $superAdmin->assignRole('SuperAdmin');
    }
}