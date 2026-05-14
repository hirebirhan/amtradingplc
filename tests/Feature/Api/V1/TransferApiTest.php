<?php

namespace Tests\Feature\Api\V1;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Item;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransferApiTest extends TestCase
{
    use RefreshDatabase;

    private function branchManager(Branch $branch): User
    {
        foreach (['transfers.create', 'transfers.view', 'transfers.approve', 'transfers.edit', 'transfers.receive', 'transfers.delete'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        $user = User::factory()->create(['is_active' => true, 'branch_id' => $branch->id]);
        $role = Role::findOrCreate('BranchManager', 'web');
        $user->assignRole($role);
        $user->givePermissionTo(['transfers.create', 'transfers.view', 'transfers.approve', 'transfers.edit', 'transfers.receive', 'transfers.delete']);
        return $user;
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        Role::findOrCreate('SuperAdmin', 'web');
        $user->assignRole('SuperAdmin');
        return $user;
    }

    private function setupTransferScenario(): array
    {
        $sourceBranch = Branch::factory()->create();
        $destBranch   = Branch::factory()->create();
        $warehouse    = Warehouse::factory()->create();
        $sourceBranch->warehouses()->attach($warehouse);

        $category = Category::factory()->create();
        $item     = Item::factory()->create(['category_id' => $category->id]);
        Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'item_id'      => $item->id,
            'piece_count'  => 50,
            'quantity'     => 50,
            'total_units'  => 50,
        ]);

        return compact('sourceBranch', 'destBranch', 'warehouse', 'item');
    }

    public function test_branch_manager_can_create_transfer_from_own_branch(): void
    {
        [
            'sourceBranch' => $sourceBranch,
            'destBranch'   => $destBranch,
            'item'         => $item,
        ] = $this->setupTransferScenario();

        $user = $this->branchManager($sourceBranch);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/transfers', [
            'source_type'      => 'branch',
            'source_id'        => $sourceBranch->id,
            'destination_type' => 'branch',
            'destination_id'   => $destBranch->id,
            'items'            => [['item_id' => $item->id, 'quantity' => 5]],
        ]);

        $response->assertCreated()
                 ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('transfers', ['source_id' => $sourceBranch->id, 'status' => 'pending']);
    }

    public function test_branch_manager_cannot_create_transfer_from_other_branch(): void
    {
        [
            'sourceBranch' => $sourceBranch,
            'destBranch'   => $destBranch,
            'item'         => $item,
        ] = $this->setupTransferScenario();

        $otherBranch = Branch::factory()->create();
        $user        = $this->branchManager($otherBranch); // Manager of OTHER branch
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/transfers', [
            'source_type'      => 'branch',
            'source_id'        => $sourceBranch->id, // Not their branch
            'destination_type' => 'branch',
            'destination_id'   => $destBranch->id,
            'items'            => [['item_id' => $item->id, 'quantity' => 5]],
        ])->assertForbidden();
    }

    public function test_super_admin_can_approve_transfer(): void
    {
        [
            'sourceBranch' => $sourceBranch,
            'destBranch'   => $destBranch,
            'item'         => $item,
        ] = $this->setupTransferScenario();

        $admin = $this->superAdmin();
        Sanctum::actingAs($admin);

        // Create the transfer first
        $response = $this->postJson('/api/v1/transfers', [
            'source_type'      => 'branch',
            'source_id'        => $sourceBranch->id,
            'destination_type' => 'branch',
            'destination_id'   => $destBranch->id,
            'items'            => [['item_id' => $item->id, 'quantity' => 3]],
        ]);
        $response->assertCreated();
        $transferId = $response->json('data.id');

        // Approve it
        // TransferService auto-completes after approval
        $this->postJson("/api/v1/transfers/{$transferId}/approve")
             ->assertOk()
             ->assertJsonPath('data.status', 'completed');
    }

    public function test_can_cancel_pending_transfer(): void
    {
        [
            'sourceBranch' => $sourceBranch,
            'destBranch'   => $destBranch,
            'item'         => $item,
        ] = $this->setupTransferScenario();

        $admin = $this->superAdmin();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/transfers', [
            'source_type'      => 'branch',
            'source_id'        => $sourceBranch->id,
            'destination_type' => 'branch',
            'destination_id'   => $destBranch->id,
            'items'            => [['item_id' => $item->id, 'quantity' => 2]],
        ]);
        $transferId = $response->json('data.id');

        $this->postJson("/api/v1/transfers/{$transferId}/cancel")
             ->assertOk()
             ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_can_list_transfers(): void
    {
        Sanctum::actingAs($this->superAdmin());
        $this->getJson('/api/v1/transfers')->assertOk()->assertJsonStructure(['data', 'meta']);
    }
}
