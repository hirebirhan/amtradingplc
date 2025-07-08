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
        // Get the Sales role
        $salesRole = Role::where('name', 'Sales')->first();
        
        if ($salesRole) {
            // Check if purchases.view permission exists
            $purchasesViewPermission = Permission::where('name', 'purchases.view')->first();
            
            if ($purchasesViewPermission) {
                // Give Sales role the ability to view purchases (they'll see only their branch's purchases due to filtering)
                $salesRole->givePermissionTo('purchases.view');
                
                echo "✅ Added purchases.view permission to Sales role\n";
            } else {
                echo "❌ purchases.view permission not found\n";
            }
        } else {
            echo "❌ Sales role not found\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the Sales role
        $salesRole = Role::where('name', 'Sales')->first();
        
        if ($salesRole) {
            // Remove the purchases.view permission from Sales role
            $salesRole->revokePermissionTo('purchases.view');
            
            echo "✅ Removed purchases.view permission from Sales role\n";
        }
    }
}; 