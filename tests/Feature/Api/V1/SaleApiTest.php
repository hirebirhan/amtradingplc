<?php

namespace Tests\Feature\Api\V1;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaleApiTest extends TestCase
{
    use RefreshDatabase;

    private function salesUser(Branch $branch, Warehouse $warehouse): User
    {
        Permission::findOrCreate('sales.create', 'web');
        Permission::findOrCreate('sales.view', 'web');
        $user = User::factory()->create([
            'is_active'    => true,
            'branch_id'    => $branch->id,
            'warehouse_id' => $warehouse->id,
        ]);
        $role = Role::findOrCreate('Sales', 'web');
        $user->assignRole($role);
        $user->givePermissionTo(['sales.create', 'sales.view']);
        return $user;
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        Role::findOrCreate('SuperAdmin', 'web');
        $user->assignRole('SuperAdmin');
        return $user;
    }

    private function setupBranchWithStock(): array
    {
        $branch    = Branch::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $branch->warehouses()->attach($warehouse);
        $category  = Category::factory()->create();
        $item      = Item::factory()->create(['category_id' => $category->id]);

        return compact('branch', 'warehouse', 'category', 'item');
    }

    public function test_sales_user_can_create_cash_sale(): void
    {
        [
            'branch'    => $branch,
            'warehouse' => $warehouse,
            'item'      => $item,
        ] = $this->setupBranchWithStock();

        $customer = Customer::factory()->create(['branch_id' => $branch->id]);
        $user     = $this->salesUser($branch, $warehouse);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/sales', [
            'customer_id'    => $customer->id,
            'branch_id'      => $branch->id,
            'warehouse_id'   => $warehouse->id,
            'payment_method' => 'cash',
            'items'          => [
                ['item_id' => $item->id, 'quantity' => 2, 'unit_price' => 50],
            ],
        ]);

        $response->assertCreated()
                 ->assertJsonPath('data.payment_method', 'cash')
                 ->assertJsonPath('data.amounts.total', 100);
    }

    public function test_walking_customer_sale_requires_no_customer_id(): void
    {
        [
            'branch'    => $branch,
            'warehouse' => $warehouse,
            'item'      => $item,
        ] = $this->setupBranchWithStock();

        $user = $this->salesUser($branch, $warehouse);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/sales', [
            'is_walking_customer' => true,
            'branch_id'           => $branch->id,
            'warehouse_id'        => $warehouse->id,
            'payment_method'      => 'cash',
            'items'               => [
                ['item_id' => $item->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('sales', ['is_walking_customer' => 1]);
    }

    public function test_full_credit_sale_creates_credit_record(): void
    {
        [
            'branch'    => $branch,
            'warehouse' => $warehouse,
            'item'      => $item,
        ] = $this->setupBranchWithStock();

        $customer = Customer::factory()->create(['branch_id' => $branch->id]);
        Sanctum::actingAs($this->superAdmin());

        $response = $this->postJson('/api/v1/sales', [
            'customer_id'    => $customer->id,
            'branch_id'      => $branch->id,
            'warehouse_id'   => $warehouse->id,
            'payment_method' => 'full_credit',
            'items'          => [
                ['item_id' => $item->id, 'quantity' => 3, 'unit_price' => 100],
            ],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('credits', [
            'customer_id'    => $customer->id,
            'credit_type'    => 'receivable',
            'reference_type' => 'sale',
        ]);
    }

    public function test_sales_user_cannot_create_sale_for_other_branch(): void
    {
        [
            'branch'    => $ownBranch,
            'warehouse' => $warehouse,
            'item'      => $item,
        ] = $this->setupBranchWithStock();
        $otherBranch = Branch::factory()->create();

        $user = $this->salesUser($ownBranch, $warehouse);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/sales', [
            'is_walking_customer' => true,
            'branch_id'           => $otherBranch->id,
            'warehouse_id'        => $warehouse->id,
            'payment_method'      => 'cash',
            'items'               => [['item_id' => $item->id, 'quantity' => 1, 'unit_price' => 50]],
        ])->assertForbidden();
    }

    public function test_sale_requires_items(): void
    {
        $branch   = Branch::factory()->create();
        $customer = Customer::factory()->create();
        Sanctum::actingAs($this->superAdmin());

        $this->postJson('/api/v1/sales', [
            'customer_id'    => $customer->id,
            'branch_id'      => $branch->id,
            'payment_method' => 'cash',
            'items'          => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['items']);
    }

    public function test_can_list_sales(): void
    {
        Sanctum::actingAs($this->superAdmin());
        $this->getJson('/api/v1/sales')->assertOk()->assertJsonStructure(['data', 'meta']);
    }
}
