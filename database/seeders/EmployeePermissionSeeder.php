<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class EmployeePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add Employee Permissions
        $employeePermissions = [
            'employees.view',
            'employees.create',
            'employees.edit',
            'employees.delete',
        ];

        foreach ($employeePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        // SuperAdmin role
        $superAdminRole = Role::where('name', 'SuperAdmin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($employeePermissions);
        }

        // BranchManager role
        $branchManagerRole = Role::where('name', 'BranchManager')->first();
        if ($branchManagerRole) {
            $branchManagerRole->givePermissionTo($employeePermissions);
        }

        // Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($employeePermissions);
        }

        $this->command->info('Employee permissions added successfully!');
    }
} 