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
        // Create new permissions for transactions
        $permissions = [
            // Purchase permissions
            'purchases.view' => 'View purchase orders',
            'purchases.create' => 'Create purchase orders',
            'purchases.edit' => 'Edit purchase orders',
            'purchases.delete' => 'Delete purchase orders',
            'purchases.approve' => 'Approve purchase orders',
            'purchases.receive' => 'Receive items from purchase orders',

            // Sales permissions
            'sales.view' => 'View sales',
            'sales.create' => 'Create sales',
            'sales.edit' => 'Edit sales',
            'sales.delete' => 'Delete sales',
            'sales.credit' => 'Create credit sales',

            // Transfer permissions
            'transfers.view' => 'View stock transfers',
            'transfers.create' => 'Create stock transfers',
            'transfers.edit' => 'Edit stock transfers',
            'transfers.delete' => 'Delete stock transfers',
            'transfers.approve' => 'Approve stock transfers',
            'transfers.send' => 'Send stock transfers',
            'transfers.receive' => 'Receive stock transfers',

            // Return permissions
            'returns.view' => 'View returns',
            'returns.create' => 'Create returns',
            'returns.edit' => 'Edit returns',
            'returns.delete' => 'Delete returns',
            'returns.approve' => 'Approve returns',
            'returns.process' => 'Process returns',

            // Customer permissions
            'customers.view' => 'View customers',
            'customers.create' => 'Create customers',
            'customers.edit' => 'Edit customers',
            'customers.delete' => 'Delete customers',

            // Payment permissions
            'payments.view' => 'View payments',
            'payments.create' => 'Create payments',
            'payments.edit' => 'Edit payments',
            'payments.delete' => 'Delete payments',
            'payments.approve' => 'Approve payments',
        ];

        foreach ($permissions as $name => $description) {
            Permission::create([
                'name' => $name,
                'description' => $description,
            ]);
        }

        // Assign permissions to roles
        $superAdmin = Role::findByName('SuperAdmin');
        $superAdmin->givePermissionTo(array_keys($permissions));

        $branchManager = Role::findByName('BranchManager');
        $branchManager->givePermissionTo([
            'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.approve', 'purchases.receive',
            'sales.view', 'sales.create', 'sales.edit',
            'transfers.view', 'transfers.create', 'transfers.edit', 'transfers.approve', 'transfers.send', 'transfers.receive',
            'returns.view', 'returns.create', 'returns.edit', 'returns.approve', 'returns.process',
            'customers.view', 'customers.create', 'customers.edit',
            'payments.view', 'payments.create', 'payments.approve',
        ]);

        $warehouseUser = Role::findByName('WarehouseUser');
        $warehouseUser->givePermissionTo([
            'purchases.view', 'purchases.receive',
            'sales.view',
            'transfers.view', 'transfers.create', 'transfers.send', 'transfers.receive',
            'returns.view', 'returns.create', 'returns.process',
            'customers.view',
            'payments.view',
        ]);

        $clerk = Role::findByName('Clerk');
        $clerk->givePermissionTo([
            'sales.view', 'sales.create',
            'returns.view', 'returns.create',
            'customers.view', 'customers.create',
            'payments.view', 'payments.create',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all permissions
        $permissions = [
            'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.delete', 'purchases.approve', 'purchases.receive',
            'sales.view', 'sales.create', 'sales.edit', 'sales.delete', 'sales.credit',
            'transfers.view', 'transfers.create', 'transfers.edit', 'transfers.delete', 'transfers.approve', 'transfers.send', 'transfers.receive',
            'returns.view', 'returns.create', 'returns.edit', 'returns.delete', 'returns.approve', 'returns.process',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'payments.view', 'payments.create', 'payments.edit', 'payments.delete', 'payments.approve',
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