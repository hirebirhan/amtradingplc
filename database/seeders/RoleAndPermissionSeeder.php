<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Branch management
            'branches.view',
            'branches.create',
            'branches.edit',
            'branches.delete',

            // Warehouse management
            'warehouses.view',
            'warehouses.create',
            'warehouses.edit',
            'warehouses.delete',

            // Category management
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',

            // Item management
            'items.view',
            'items.create',
            'items.edit',
            'items.delete',

            // Employee management
            'employees.view',
            'employees.create',
            'employees.edit',
            'employees.delete',

            // Role & permission management
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',

            // Stock management
            'stock.view',
            'stock.adjust',
            'stock.history',

            // Stock Card management
            'stock-card.view',
            'stock-card.create',

            // Advanced permissions for transactions
            'purchases.create',
            'purchases.approve',
            'sales.create',
            'returns.approve',
            'transfers.create',
            'transfers.approve',
            'reports.view',
            'reports.export',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // SuperAdmin role - has all permissions
        $superAdminRole = Role::create(['name' => 'SuperAdmin']);
        $superAdminRole->givePermissionTo(Permission::all());

        // BranchManager role
        $branchManagerRole = Role::create(['name' => 'BranchManager']);
        $branchManagerPermissions = [
            'items.view', 'items.create', 'items.edit',
            'categories.view', 'categories.create', 'categories.edit',
            'warehouses.view',
            'users.view',
            'branches.view',
            'employees.view', 'employees.create', 'employees.edit',
            'stock.view', 'stock.history',
            'stock-card.view', 'stock-card.create',
            'purchases.create', 'purchases.approve',
            'sales.create',
            'reports.view',
        ];
        $branchManagerRole->givePermissionTo($branchManagerPermissions);

        // WarehouseUser role
        $warehouseUserRole = Role::create(['name' => 'WarehouseUser']);
        $warehouseUserPermissions = [
            'items.view', 'items.edit',
            'categories.view',
            'warehouses.view',
            'stock.view', 'stock.adjust', 'stock.history',
            'stock-card.view', 'stock-card.create',
            'transfers.create',
        ];
        $warehouseUserRole->givePermissionTo($warehouseUserPermissions);

        // Clerk role
        $clerkRole = Role::create(['name' => 'Clerk']);
        $clerkPermissions = [
            'items.view',
            'categories.view',
            'stock.view',
            'stock-card.view',
            'sales.create',
        ];
        $clerkRole->givePermissionTo($clerkPermissions);
    }
}
