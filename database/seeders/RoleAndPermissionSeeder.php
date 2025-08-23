<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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

        // Create roles using the enum
        $superAdminRole = Role::create(['name' => UserRole::SUPER_ADMIN->value]);
        $superAdminRole->givePermissionTo(Permission::all());

        // BranchManager role with specific permissions
        $branchManagerRole = Role::create(['name' => UserRole::BRANCH_MANAGER->value]);
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

        // WarehouseManager role
        $warehouseManagerRole = Role::create(['name' => UserRole::WAREHOUSE_MANAGER->value]);
        $warehouseUserPermissions = [
            'items.view', 'items.edit',
            'categories.view',
            'warehouses.view',
            'stock.view', 'stock.adjust', 'stock.history',
            'stock-card.view', 'stock-card.create',
            'transfers.create',
        ];
        $warehouseManagerRole->givePermissionTo($warehouseUserPermissions);


        // Accountant role
        $accountantRole = Role::create(['name' => UserRole::ACCOUNTANT->value]);
        $accountantPermissions = [
            'items.view',
            'categories.view',
            'stock.view',
            'stock-card.view',
            'purchases.create',
            'reports.view',
            'reports.export',
        ];
        $accountantRole->givePermissionTo($accountantPermissions);

        // CustomerService role
        $customerServiceRole = Role::create(['name' => UserRole::CUSTOMER_SERVICE->value]);
        $customerServicePermissions = [
            'customers.view', 'customers.create', 'customers.edit',
            'sales.create',
        ];
        $customerServiceRole->givePermissionTo($customerServicePermissions);

        // Sales role
        $salesRole = Role::create(['name' => UserRole::SALES->value]);
        $salesPermissions = [
            'items.view',
            'categories.view',
            'stock.view',
            'sales.create',
            'customers.view', 'customers.create', 'customers.edit',
        ];
        $salesRole->givePermissionTo($salesPermissions);

        // PurchaseOfficer role
        $purchaseOfficerRole = Role::create(['name' => UserRole::PURCHASE_OFFICER->value]);
        $purchaseOfficerPermissions = [
            'items.view', 'items.create', 'items.edit',
            'categories.view',
            'stock.view',
            'purchases.create',
        ];
        $purchaseOfficerRole->givePermissionTo($purchaseOfficerPermissions);

        // Moved inventory clerk permissions to Warehouse Manager
    }
}
