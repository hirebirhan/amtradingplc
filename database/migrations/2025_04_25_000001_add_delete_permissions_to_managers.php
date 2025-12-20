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
        // Define all delete permissions that managers should have
        $deletePermissions = [
            'customers.delete' => 'Delete customers',
            'items.delete' => 'Delete items',
            'sales.delete' => 'Delete sales',
            'purchases.delete' => 'Delete purchase orders',
            'transfers.delete' => 'Delete stock transfers',
            'returns.delete' => 'Delete returns',
            'payments.delete' => 'Delete payments',
            'suppliers.delete' => 'Delete suppliers',
            'expenses.delete' => 'Delete expenses',
            'credits.delete' => 'Delete credits',
            'categories.delete' => 'Delete categories',
            'warehouses.delete' => 'Delete warehouses',
            'users.delete' => 'Delete users',
            'employees.delete' => 'Delete employees',
        ];

        // Ensure all delete permissions exist
        foreach ($deletePermissions as $name => $description) {
            Permission::firstOrCreate([
                'name' => $name
            ]);
        }

        // Get all manager roles that should have delete access
        $managerRoles = [
            'GeneralManager',
            'BranchManager',
            'WarehouseManager',
            'Manager',
        ];

        // Add delete permissions to manager roles
        foreach ($managerRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                foreach (array_keys($deletePermissions) as $permission) {
                    if (!$role->hasPermissionTo($permission)) {
                        $role->givePermissionTo($permission);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Define all delete permissions to remove
        $deletePermissions = [
            'customers.delete',
            'items.delete',
            'sales.delete',
            'purchases.delete',
            'transfers.delete',
            'returns.delete',
            'payments.delete',
            'suppliers.delete',
            'expenses.delete',
            'credits.delete',
            'categories.delete',
            'warehouses.delete',
            'users.delete',
            'employees.delete',
        ];

        // Remove delete permissions from manager roles (except SuperAdmin)
        $managerRoles = [
            'GeneralManager',
            'BranchManager', 
            'WarehouseManager',
            'Manager',
        ];

        foreach ($managerRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                foreach ($deletePermissions as $permission) {
                    if ($role->hasPermissionTo($permission)) {
                        $role->revokePermissionTo($permission);
                    }
                }
            }
        }
    }
};