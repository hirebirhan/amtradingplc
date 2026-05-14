<?php

namespace Tests\Feature\Api\V1;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseApiTest extends TestCase
{
    use RefreshDatabase;

    private function setupPurchaseOfficer(Branch $branch): User
    {
        Permission::findOrCreate('purchases.create', 'web');
        Permission::findOrCreate('purchases.view', 'web');
        Permission::findOrCreate('purchases.delete', 'web');
        $user = User::factory()->create(['is_active' => true, 'branch_id' => $branch->id]);
        $role = Role::findOrCreate('PurchaseOfficer', 'web');
        $user->assignRole($role);
        $user->givePermissionTo(['purchases.create', 'purchases.view', 'purchases.delete']);
        return $user;
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        Role::findOrCreate('SuperAdmin', 'web');
        $user->assignRole('SuperAdmin');
        return $user;
    }

    private function makePurchasePayload(Supplier $supplier, Branch $branch, Item $item): array
    {
        return [
            'supplier_id'    => $supplier->id,
            'branch_id'      => $branch->id,
            'purchase_date'  => now()->toDateString(),
            'payment_method' => 'cash',
            'items'          => [
                ['item_id' => $item->id, 'quantity' => 10, 'unit_cost' => 100],
            ],
        ];
    }

    public function test_purchase_officer_can_create_cash_purchase(): void
    {
        $branch    = Branch::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $branch->warehouses()->attach($warehouse);
        $category  = Category::factory()->create();
        $supplier  = Supplier::factory()->create();
        $item      = Item::factory()->create(['category_id' => $category->id]);

        $user = $this->setupPurchaseOfficer($branch);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/purchases', $this->makePurchasePayload($supplier, $branch, $item));

        $response->assertCreated()
                 ->assertJsonPath('data.amounts.total', 1000)
                 ->assertJsonPath('data.payment_method', 'cash');

        $this->assertDatabaseHas('purchases', ['supplier_id' => $supplier->id]);
        $this->assertDatabaseHas('stocks', ['item_id' => $item->id, 'piece_count' => 10]);
    }

    public function test_full_credit_purchase_creates_credit_record(): void
    {
        $branch    = Branch::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $branch->warehouses()->attach($warehouse);
        $category  = Category::factory()->create();
        $supplier  = Supplier::factory()->create();
        $item      = Item::factory()->create(['category_id' => $category->id]);

        Sanctum::actingAs($this->superAdmin());

        $response = $this->postJson('/api/v1/purchases', [
            'supplier_id'    => $supplier->id,
            'branch_id'      => $branch->id,
            'purchase_date'  => now()->toDateString(),
            'payment_method' => 'full_credit',
            'items'          => [['item_id' => $item->id, 'quantity' => 5, 'unit_cost' => 200]],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('credits', [
            'supplier_id'    => $supplier->id,
            'credit_type'    => 'payable',
            'reference_type' => 'purchase',
        ]);
    }

    public function test_branch_manager_cannot_create_purchase_for_other_branch(): void
    {
        $ownBranch   = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $category    = Category::factory()->create();
        $supplier    = Supplier::factory()->create();
        $item        = Item::factory()->create(['category_id' => $category->id]);

        $user = $this->setupPurchaseOfficer($ownBranch);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/purchases', [
            'supplier_id'    => $supplier->id,
            'branch_id'      => $otherBranch->id,
            'purchase_date'  => now()->toDateString(),
            'payment_method' => 'cash',
            'items'          => [['item_id' => $item->id, 'quantity' => 1, 'unit_cost' => 100]],
        ]);

        // PurchasePolicy: user's branch != otherBranch → 403
        $response->assertForbidden();
    }

    public function test_purchase_requires_items(): void
    {
        $branch   = Branch::factory()->create();
        $supplier = Supplier::factory()->create();
        Sanctum::actingAs($this->superAdmin());

        $this->postJson('/api/v1/purchases', [
            'supplier_id'   => $supplier->id,
            'branch_id'     => $branch->id,
            'purchase_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'items'          => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['items']);
    }

    public function test_can_list_purchases(): void
    {
        $branch   = Branch::factory()->create();
        $supplier = Supplier::factory()->create();
        $user     = $this->superAdmin();
        Sanctum::actingAs($user);

        Purchase::factory()->create(['branch_id' => $branch->id, 'supplier_id' => $supplier->id, 'user_id' => $user->id]);

        $this->getJson('/api/v1/purchases')->assertOk()->assertJsonStructure(['data', 'meta']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v1/purchases')->assertUnauthorized();
    }
}
