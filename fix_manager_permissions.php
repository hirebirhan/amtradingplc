<?php

/**
 * Script to fix manager permissions for delete operations
 * Run this script to add missing delete permissions to manager roles
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Bootstrap Laravel
$app = new Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 Fixing Manager Delete Permissions...\n\n";

try {
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

    echo "📋 Ensuring all delete permissions exist...\n";
    
    // Ensure all delete permissions exist
    foreach ($deletePermissions as $name => $description) {
        $permission = Permission::firstOrCreate([
            'name' => $name
        ], [
            'description' => $description,
        ]);
        
        if ($permission->wasRecentlyCreated) {
            echo "  ✅ Created permission: {$name}\n";
        } else {
            echo "  ℹ️  Permission exists: {$name}\n";
        }
    }

    echo "\n👥 Adding permissions to manager roles...\n";

    // Get all manager roles that should have delete access
    $managerRoles = [
        'GeneralManager',
        'BranchManager',
        'WarehouseManager',
        'Manager',
    ];

    $addedCount = 0;
    $skippedCount = 0;

    // Add delete permissions to manager roles
    foreach ($managerRoles as $roleName) {
        $role = Role::where('name', $roleName)->first();
        
        if (!$role) {
            echo "  ⚠️  Role not found: {$roleName}\n";
            continue;
        }
        
        echo "  🔍 Processing role: {$roleName}\n";
        
        foreach (array_keys($deletePermissions) as $permission) {
            if (!$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
                echo "    ✅ Added permission: {$permission}\n";
                $addedCount++;
            } else {
                echo "    ℹ️  Already has permission: {$permission}\n";
                $skippedCount++;
            }
        }
    }

    echo "\n🎉 Permission fix completed successfully!\n";
    echo "📊 Summary:\n";
    echo "  • Permissions added: {$addedCount}\n";
    echo "  • Permissions skipped (already existed): {$skippedCount}\n";
    echo "  • Manager roles processed: " . count(array_filter($managerRoles, function($roleName) {
        return Role::where('name', $roleName)->exists();
    })) . "\n\n";
    
    echo "✨ All managers now have equal delete access!\n";
    echo "🔄 Please refresh your browser to see the changes.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}