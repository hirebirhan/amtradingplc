<?php

namespace Tests\Feature\Api\V1;

use App\Models\Branch;
use App\Models\Item;
use App\Models\Stock;
use App\Models\StockReservation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        Role::findOrCreate('SuperAdmin', 'web');
        $user->assignRole('SuperAdmin');

        return $user;
    }

    private function actingAsBranchManager(Branch $branch): User
    {
        Permission::findOrCreate('stock.view', 'web');
        Permission::findOrCreate('transfers.view', 'web');
        Permission::findOrCreate('transfers.edit', 'web');
        $user = User::factory()->create(['is_active' => true, 'branch_id' => $branch->id]);
        $role = Role::findOrCreate('BranchManager', 'web');
        $user->assignRole($role);
        $user->givePermissionTo(['stock.view', 'transfers.view', 'transfers.edit']);

        return $user;
    }

    public function test_super_admin_can_list_all_stocks(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create();
        Stock::factory()->create(['warehouse_id' => $warehouse->id, 'item_id' => $item->id]);

        Sanctum::actingAs($this->actingAsAdmin());
        $this->getJson('/api/v1/stocks')->assertOk()->assertJsonStructure(['data']);
    }

    public function test_branch_manager_only_sees_own_branch_stock(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();

        $ownWarehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();

        $ownBranch->warehouses()->attach($ownWarehouse);
        $otherBranch->warehouses()->attach($otherWarehouse);

        $item = Item::factory()->create();
        $ownStock = Stock::factory()->create(['warehouse_id' => $ownWarehouse->id,   'branch_id' => $ownBranch->id,   'item_id' => $item->id]);
        $otherStock = Stock::factory()->create(['warehouse_id' => $otherWarehouse->id, 'branch_id' => $otherBranch->id, 'item_id' => $item->id]);

        $user = $this->actingAsBranchManager($ownBranch);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/stocks')->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains($ownStock->id, $ids->toArray());
        $this->assertNotContains($otherStock->id, $ids->toArray());
    }

    public function test_stock_can_be_filtered_by_item(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item1 = Item::factory()->create(['name' => 'Item Alpha']);
        $item2 = Item::factory()->create(['name' => 'Item Beta']);
        Stock::factory()->create(['warehouse_id' => $warehouse->id, 'item_id' => $item1->id]);
        Stock::factory()->create(['warehouse_id' => $warehouse->id, 'item_id' => $item2->id]);

        Sanctum::actingAs($this->actingAsAdmin());
        $response = $this->getJson("/api/v1/stocks?filter[item_id]={$item1->id}")->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_stock_api_requires_auth(): void
    {
        $this->getJson('/api/v1/stocks')->assertUnauthorized();
    }

    public function test_can_show_single_stock_record(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create();
        $stock = Stock::factory()->create(['warehouse_id' => $warehouse->id, 'item_id' => $item->id]);

        Sanctum::actingAs($this->actingAsAdmin());
        $this->getJson("/api/v1/stocks/{$stock->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $stock->id);
    }

    public function test_branch_manager_cannot_show_other_branch_stock(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();

        $ownWarehouse = Warehouse::factory()->create();
        $otherWarehouse = Warehouse::factory()->create();

        $ownBranch->warehouses()->attach($ownWarehouse);
        $otherBranch->warehouses()->attach($otherWarehouse);

        $item = Item::factory()->create();
        $ownStock = Stock::factory()->create([
            'warehouse_id' => $ownWarehouse->id,
            'branch_id' => $ownBranch->id,
            'item_id' => $item->id,
        ]);
        $otherStock = Stock::factory()->create([
            'warehouse_id' => $otherWarehouse->id,
            'branch_id' => $otherBranch->id,
            'item_id' => $item->id,
        ]);

        Sanctum::actingAs($this->actingAsBranchManager($ownBranch));

        $this->getJson("/api/v1/stocks/{$ownStock->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $ownStock->id);

        $this->getJson("/api/v1/stocks/{$otherStock->id}")
            ->assertForbidden();
    }

    // ── Stock Reservations ───────────────────────────────────────────────────

    public function test_can_list_stock_reservations(): void
    {
        $user = $this->actingAsAdmin();
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create();

        StockReservation::factory()->create([
            'item_id' => $item->id,
            'location_type' => 'warehouse',
            'location_id' => $warehouse->id,
            'quantity' => 5,
            'expires_at' => now()->addHours(24),
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/stock-reservations')->assertOk()->assertJsonStructure(['data']);
    }

    public function test_can_release_stock_reservation(): void
    {
        $user = $this->actingAsAdmin();
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create();

        $reservation = StockReservation::factory()->create([
            'item_id' => $item->id,
            'location_type' => 'warehouse',
            'location_id' => $warehouse->id,
            'quantity' => 3,
            'expires_at' => now()->addHours(24),
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/stock-reservations/{$reservation->id}/release")
            ->assertOk();

        $this->assertDatabaseMissing('stock_reservations', ['id' => $reservation->id]);
    }
}
