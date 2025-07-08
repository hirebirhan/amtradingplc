<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create roles
        $superAdmin = Role::create(['name' => 'SuperAdmin']);
        $branchManager = Role::create(['name' => 'BranchManager']);
        $warehouseUser = Role::create(['name' => 'WarehouseUser']);
        $clerk = Role::create(['name' => 'Clerk']);

        // Create permissions for items
        $itemPermissions = [
            'items.view' => 'View inventory items',
            'items.create' => 'Create new items',
            'items.edit' => 'Edit items',
            'items.delete' => 'Delete items',
        ];

        // Create permissions for categories
        $categoryPermissions = [
            'categories.view' => 'View categories',
            'categories.create' => 'Create new categories',
            'categories.edit' => 'Edit categories',
            'categories.delete' => 'Delete categories',
        ];

        // Create permissions for branches
        $branchPermissions = [
            'branches.view' => 'View branches',
            'branches.create' => 'Create new branches',
            'branches.edit' => 'Edit branches',
            'branches.delete' => 'Delete branches',
        ];

        // Create permissions for warehouses
        $warehousePermissions = [
            'warehouses.view' => 'View warehouses',
            'warehouses.create' => 'Create new warehouses',
            'warehouses.edit' => 'Edit warehouses',
            'warehouses.delete' => 'Delete warehouses',
        ];

        // Create permissions for users
        $userPermissions = [
            'users.view' => 'View users',
            'users.create' => 'Create new users',
            'users.edit' => 'Edit users',
            'users.delete' => 'Delete users',
        ];

        // Create all permissions
        $allPermissions = array_merge(
            $itemPermissions,
            $categoryPermissions,
            $branchPermissions,
            $warehousePermissions,
            $userPermissions
        );

        foreach ($allPermissions as $name => $description) {
            Permission::create([
                'name' => $name,
                'description' => $description,
            ]);
        }

        // SuperAdmin gets all permissions
        $superAdmin->givePermissionTo(array_keys($allPermissions));

        // BranchManager permissions
        $branchManager->givePermissionTo([
            'items.view', 'items.create', 'items.edit',
            'categories.view', 'categories.create', 'categories.edit',
            'warehouses.view',
            'users.view',
            'branches.view',
        ]);

        // WarehouseUser permissions
        $warehouseUser->givePermissionTo([
            'items.view', 'items.edit',
            'categories.view',
            'warehouses.view',
        ]);

        // Clerk permissions
        $clerk->givePermissionTo([
            'items.view',
            'categories.view',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete all roles and permissions
        $roles = ['SuperAdmin', 'BranchManager', 'WarehouseUser', 'Clerk'];
        foreach ($roles as $role) {
            $role = Role::findByName($role);
            if ($role) {
                $role->delete();
            }
        }

        // Permissions are automatically deleted due to cascade delete
    }
};