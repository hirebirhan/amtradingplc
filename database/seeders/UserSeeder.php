<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\Warehouse;
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
        // Get branches and warehouses
        $branches = Branch::all();
        $mainBranch = $branches->first();
        $warehouses = Warehouse::all();
        $mainWarehouse = $warehouses->first();

        // Create General Manager (with full access similar to SuperAdmin)
        $generalManager = User::create([
            'name' => 'General Manager',
            'email' => 'gm@amtradingplc.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'position' => 'General Manager',
            'is_active' => true,
        ]);

        // Create a new role for General Manager with all permissions
        $generalManagerRole = Role::firstOrCreate(['name' => 'GeneralManager']);

        // If it's a new role, give it all permissions like SuperAdmin
        if ($generalManagerRole->wasRecentlyCreated) {
            $superAdminRole = Role::findByName('SuperAdmin');
            $generalManagerRole->syncPermissions($superAdminRole->permissions);
        }

        $generalManager->assignRole('GeneralManager');

        // Create Branch Managers for each branch
        foreach ($branches as $index => $branch) {
            $branchManager = User::create([
                'name' => "Branch Manager " . ($index + 1),
                'email' => "branch-manager-{$index}@amtradingplc.com",
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
                'branch_id' => $branch->id,
            'position' => 'Branch Manager',
                'phone' => '123-456-' . rand(1000, 9999),
            'is_active' => true,
        ]);
            $branchManager->assignRole('BranchManager');

            // Create a Sales User for each branch
            $salesUser = User::create([
                'name' => "Sales User " . ($index + 1),
                'email' => "sales-{$index}@amtradingplc.com",
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
                'branch_id' => $branch->id,
                'position' => 'Sales Representative',
                'phone' => '123-789-' . rand(1000, 9999),
            'is_active' => true,
        ]);
            $salesUser->assignRole('Sales');
        }

        // Create Warehouse Users for each warehouse
        foreach ($warehouses as $index => $warehouse) {
            $warehouseUser = User::create([
                'name' => "Warehouse User " . ($index + 1),
                'email' => "warehouse-{$index}@amtradingplc.com",
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
                'branch_id' => $warehouse->branch_id,
                'warehouse_id' => $warehouse->id,
                'position' => 'Warehouse Supervisor',
                'phone' => '123-555-' . rand(1000, 9999),
            'is_active' => true,
        ]);
            $warehouseUser->assignRole('WarehouseUser');
        }
    }
}
