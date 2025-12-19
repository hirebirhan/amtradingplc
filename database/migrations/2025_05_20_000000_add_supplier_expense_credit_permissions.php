<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create new permissions
        $permissions = [
            // Supplier permissions
            'suppliers.view' => 'View suppliers',
            'suppliers.create' => 'Create suppliers',
            'suppliers.edit' => 'Edit suppliers',
            'suppliers.delete' => 'Delete suppliers',

            // Expense permissions
            'expenses.view' => 'View expenses',
            'expenses.create' => 'Create expenses',
            'expenses.edit' => 'Edit expenses',
            'expenses.delete' => 'Delete expenses',
            'expenses.approve' => 'Approve expenses',

            // Credit permissions
            'credits.view' => 'View credits',
            'credits.create' => 'Create credits',
            'credits.edit' => 'Edit credits',
            'credits.delete' => 'Delete credits',
            'credits.approve' => 'Approve credits',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate([
                'name' => $name
            ], [
                'description' => $description,
            ]);
        }

        // Assign permissions to roles
        $superAdmin = Role::findByName('SuperAdmin');
        $superAdmin->givePermissionTo(array_keys($permissions));

        $branchManager = Role::findByName('BranchManager');
        $branchManager->givePermissionTo([
            'suppliers.view', 'suppliers.create', 'suppliers.edit',
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.approve',
            'credits.view', 'credits.create', 'credits.edit', 'credits.approve',
        ]);

        $warehouseUser = Role::findByName('WarehouseUser');
        $warehouseUser->givePermissionTo([
            'suppliers.view',
            'expenses.view',
            'credits.view',
        ]);

        $clerk = Role::findByName('Clerk');
        $clerk->givePermissionTo([
            'suppliers.view',
            'expenses.view',
            'credits.view', 'credits.create',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all permissions
        $permissions = [
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete', 'expenses.approve',
            'credits.view', 'credits.create', 'credits.edit', 'credits.delete', 'credits.approve',
        ];

        // Remove permissions from roles
        $roles = ['SuperAdmin', 'BranchManager', 'WarehouseUser', 'Clerk'];
        foreach ($roles as $roleName) {
            $role = Role::findByName($roleName);
            $role->revokePermissionTo($permissions);
        }

        // Delete permissions
        Permission::whereIn('name', $permissions)->delete();
    }
};