<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Enums\UserRole;
use App\Enums\BranchCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get branches only (no warehouses in this setup)
        $branches = Branch::all();
        $mainBranch = $branches->first();

        // Create General Manager (with full access similar to SuperAdmin)
        $generalManager = User::updateOrCreate(
            ['email' => 'gm@amtradingplc.com'],
            [
                'name' => 'General Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'position' => 'General Manager',
                'is_active' => true,
            ]
        );

        // Create a new role for General Manager with all permissions
        $generalManagerRole = Role::firstOrCreate(['name' => UserRole::GENERAL_MANAGER->value]);

        // If it's a new role, give it all permissions like SuperAdmin
        if ($generalManagerRole->wasRecentlyCreated) {
            $superAdminRole = Role::findByName(UserRole::SUPER_ADMIN->value);
            $generalManagerRole->syncPermissions($superAdminRole->permissions);
        }

        $generalManager->assignRole(UserRole::GENERAL_MANAGER->value);

        // Create Branch Managers using enum mapping
        foreach ($branches as $branch) {
            $branchCode = BranchCode::tryFrom($branch->code);
            if ($branchCode) {
                $branchManager = User::updateOrCreate(
                    ['email' => $branchCode->getManagerEmail()],
                    [
                        'name' => $branchCode->getName() . ' Manager',
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                        'branch_id' => $branch->id,
                        'position' => 'Branch Manager',
                        'phone' => '123-456-' . rand(1000, 9999),
                        'is_active' => true,
                    ]
                );
                $branchManager->assignRole(UserRole::BRANCH_MANAGER->value);
            }
    }
}
