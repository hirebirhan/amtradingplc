<?php

namespace Tests\Unit\Policies;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ProductPolicyTest extends TestCase
{
    use DatabaseMigrations;

    protected User $superAdmin;
    protected User $branchManager;
    protected User $warehouseUser;
    protected User $clerk;
    protected Product $product;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a branch for testing
        $this->branch = Branch::factory()->create(['name' => 'Test Branch']);

        // Create a manager branch
        $managerBranch = Branch::factory()->create(['name' => 'Manager Branch']);

        // Create test users with different roles
        $this->superAdmin = User::factory()->create(['name' => 'Super Admin']);
        $this->superAdmin->assignRole('SuperAdmin');

        $this->branchManager = User::factory()->create([
            'name' => 'Branch Manager',
            'branch_id' => $managerBranch->id
        ]);
        $this->branchManager->assignRole('BranchManager');

        $this->warehouseUser = User::factory()->create(['name' => 'Warehouse User']);
        $this->warehouseUser->assignRole('WarehouseUser');

        $this->clerk = User::factory()->create(['name' => 'Clerk']);
        $this->clerk->assignRole('Clerk');

        // Create a test product
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'branch_id' => $this->branch->id
        ]);
    }

    /** @test */
    public function super_admin_can_view_any_products()
    {
        $this->assertTrue($this->superAdmin->can('viewAny', Product::class));
    }

    /** @test */
    public function branch_manager_can_view_any_products()
    {
        $this->assertTrue($this->branchManager->can('viewAny', Product::class));
    }

    /** @test */
    public function warehouse_user_can_view_any_products()
    {
        $this->assertTrue($this->warehouseUser->can('viewAny', Product::class));
    }

    /** @test */
    public function clerk_can_view_any_products()
    {
        $this->assertTrue($this->clerk->can('viewAny', Product::class));
    }

    /** @test */
    public function super_admin_can_view_product()
    {
        $this->assertTrue($this->superAdmin->can('view', $this->product));
    }

    /** @test */
    public function branch_manager_can_view_own_branch_product()
    {
        // Create a product for the manager's branch
        $managerBranchProduct = Product::factory()->create([
            'branch_id' => $this->branchManager->branch_id
        ]);

        $this->assertTrue($this->branchManager->can('view', $managerBranchProduct));
    }

    /** @test */
    public function branch_manager_cannot_view_other_branch_product()
    {
        $this->assertFalse($this->branchManager->can('view', $this->product));
    }

    /** @test */
    public function warehouse_user_can_view_product()
    {
        $this->assertTrue($this->warehouseUser->can('view', $this->product));
    }

    /** @test */
    public function clerk_can_view_product()
    {
        $this->assertTrue($this->clerk->can('view', $this->product));
    }

    /** @test */
    public function super_admin_can_create_product()
    {
        $this->assertTrue($this->superAdmin->can('create', Product::class));
    }

    /** @test */
    public function branch_manager_can_create_product()
    {
        $this->assertTrue($this->branchManager->can('create', Product::class));
    }

    /** @test */
    public function warehouse_user_can_create_product()
    {
        $this->assertTrue($this->warehouseUser->can('create', Product::class));
    }

    /** @test */
    public function clerk_cannot_create_product()
    {
        $this->assertFalse($this->clerk->can('create', Product::class));
    }

    /** @test */
    public function super_admin_can_update_product()
    {
        $this->assertTrue($this->superAdmin->can('update', $this->product));
    }

    /** @test */
    public function branch_manager_can_update_own_branch_product()
    {
        // Create a product for the manager's branch
        $managerBranchProduct = Product::factory()->create([
            'branch_id' => $this->branchManager->branch_id
        ]);

        $this->assertTrue($this->branchManager->can('update', $managerBranchProduct));
    }

    /** @test */
    public function branch_manager_cannot_update_other_branch_product()
    {
        $this->assertFalse($this->branchManager->can('update', $this->product));
    }

    /** @test */
    public function warehouse_user_can_update_product()
    {
        $this->assertTrue($this->warehouseUser->can('update', $this->product));
    }

    /** @test */
    public function clerk_cannot_update_product()
    {
        $this->assertFalse($this->clerk->can('update', $this->product));
    }

    /** @test */
    public function super_admin_can_delete_product()
    {
        $this->assertTrue($this->superAdmin->can('delete', $this->product));
    }

    /** @test */
    public function branch_manager_can_delete_own_branch_product()
    {
        // Create a product for the manager's branch
        $managerBranchProduct = Product::factory()->create([
            'branch_id' => $this->branchManager->branch_id
        ]);

        $this->assertTrue($this->branchManager->can('delete', $managerBranchProduct));
    }

    /** @test */
    public function branch_manager_cannot_delete_other_branch_product()
    {
        $this->assertFalse($this->branchManager->can('delete', $this->product));
    }

    /** @test */
    public function warehouse_user_can_delete_product()
    {
        $this->assertTrue($this->warehouseUser->can('delete', $this->product));
    }

    /** @test */
    public function clerk_cannot_delete_product()
    {
        $this->assertFalse($this->clerk->can('delete', $this->product));
    }
}