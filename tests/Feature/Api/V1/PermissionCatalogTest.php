<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Support\Access\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_catalog_seeds_every_declared_permission(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        foreach (PermissionCatalog::permissions() as $permission) {
            $this->assertTrue(
                Permission::where('name', $permission)->exists(),
                "Missing seeded permission [{$permission}].",
            );
        }
    }

    public function test_core_roles_receive_catalog_permissions(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $superAdmin = Role::findByName(UserRole::SUPER_ADMIN->value);
        $branchManager = Role::findByName(UserRole::BRANCH_MANAGER->value);

        $this->assertTrue($superAdmin->hasPermissionTo('reports.view'));
        $this->assertTrue($superAdmin->hasPermissionTo('bank-accounts.view'));
        $this->assertTrue($superAdmin->hasPermissionTo('stock.view'));

        $this->assertTrue($branchManager->hasPermissionTo('customers.view'));
        $this->assertTrue($branchManager->hasPermissionTo('transfers.view'));
        $this->assertFalse($branchManager->hasPermissionTo('roles.delete'));
    }
}
