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
        // Check if Sales role already exists
        $salesRole = Role::where('name', 'Sales')->first();
        
        if (!$salesRole) {
            // Create Sales role
            $salesRole = Role::create([
                'name' => 'Sales',
                'guard_name' => 'web'
            ]);
        }

        // Define sales-related permissions
        $salesPermissions = [
            // Core sales permissions
            'sales.view',
            'sales.create', 
            'sales.edit',
            'sales.credit',
            
            // Customer management
            'customers.view',
            'customers.create',
            'customers.edit',
            
            // Payment handling
            'payments.view',
            'payments.create',
            
            // Inventory viewing (to see stock levels)
            'items.view',
            'categories.view',
            'stock.view',
            
            // Basic returns handling
            'returns.view',
            'returns.create',
            
            // Basic reporting
            'reports.view',
        ];

        // Ensure all permissions exist before assigning
        $existingPermissions = [];
        foreach ($salesPermissions as $permission) {
            $perm = Permission::where('name', $permission)->first();
            if ($perm) {
                $existingPermissions[] = $permission;
            }
        }

        // Assign permissions to Sales role
        if (!empty($existingPermissions)) {
            $salesRole->syncPermissions($existingPermissions);
        }

        // Also ensure SuperAdmin has all permissions
        $superAdmin = Role::where('name', 'SuperAdmin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($existingPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Sales role
        $salesRole = Role::where('name', 'Sales')->first();
        if ($salesRole) {
            $salesRole->delete();
        }
    }
};
