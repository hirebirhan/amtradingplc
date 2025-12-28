<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // Create proforma permissions
        $proformaPermissions = [
            'proformas.view' => 'View proformas',
            'proformas.create' => 'Create new proformas',
            'proformas.edit' => 'Edit proformas',
            'proformas.delete' => 'Delete proformas',
        ];

        // Create permissions
        foreach ($proformaPermissions as $name => $description) {
            Permission::firstOrCreate([
                'name' => $name,
            ]);
        }

        // Give permissions to roles
        $superAdmin = Role::findByName('SuperAdmin');
        $branchManager = Role::findByName('BranchManager');

        if ($superAdmin) {
            $superAdmin->givePermissionTo(array_keys($proformaPermissions));
        }

        if ($branchManager) {
            $branchManager->givePermissionTo(array_keys($proformaPermissions));
        }
    }

    public function down(): void
    {
        $permissions = ['proformas.view', 'proformas.create', 'proformas.edit', 'proformas.delete'];
        
        foreach ($permissions as $permission) {
            $perm = Permission::findByName($permission);
            if ($perm) {
                $perm->delete();
            }
        }
    }
};