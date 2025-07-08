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
        // Create activities permission
        $permission = Permission::create([
            'name' => 'activities.view',
            'description' => 'View activity logs'
        ]);

        // Assign to SuperAdmin (gets all permissions anyway)
        $superAdmin = Role::findByName('SuperAdmin');
        if ($superAdmin) {
            $superAdmin->givePermissionTo('activities.view');
        }

        // Assign to BranchManager (they already had access through reports.view)
        $branchManager = Role::findByName('BranchManager');
        if ($branchManager) {
            $branchManager->givePermissionTo('activities.view');
        }

        // Assign to Sales role (this is the main purpose - sales users can see their own activities)
        $salesRole = Role::findByName('Sales');
        if ($salesRole) {
            $salesRole->givePermissionTo('activities.view');
        }

        // Optionally assign to other roles that should see activities
        $warehouseUser = Role::findByName('WarehouseUser');
        if ($warehouseUser) {
            $warehouseUser->givePermissionTo('activities.view');
        }

        $clerk = Role::findByName('Clerk');
        if ($clerk) {
            $clerk->givePermissionTo('activities.view');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the permission from all roles
        $roles = ['SuperAdmin', 'BranchManager', 'Sales', 'WarehouseUser', 'Clerk'];
        foreach ($roles as $roleName) {
            $role = Role::findByName($roleName);
            if ($role) {
                $role->revokePermissionTo('activities.view');
            }
        }

        // Delete the permission
        Permission::where('name', 'activities.view')->delete();
    }
}; 