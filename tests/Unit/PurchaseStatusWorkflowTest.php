<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Item;
use App\Models\PurchaseItem;
use App\Enums\PurchaseStatus;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseStatusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_can_only_be_processed_when_confirmed()
    {
        $purchase = Purchase::factory()->create([
            'status' => PurchaseStatus::DRAFT->value
        ]);

        $this->assertFalse($purchase->canBeProcessed());
        
        $errors = $purchase->getProcessingValidationErrors();
        $this->assertContains("Purchase status must be 'confirmed' to process. Current status: draft", $errors);
    }

    public function test_purchase_cannot_be_processed_without_items()
    {
        $purchase = Purchase::factory()->create([
            'status' => PurchaseStatus::CONFIRMED->value
        ]);

        $this->assertFalse($purchase->canBeProcessed());
        
        $errors = $purchase->getProcessingValidationErrors();
        $this->assertContains('Purchase must have at least one item to process', $errors);
    }

    public function test_purchase_processing_throws_exception_for_invalid_status()
    {
        $purchase = Purchase::factory()->create([
            'status' => PurchaseStatus::DRAFT->value
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot process purchase with status 'draft'. Only confirmed purchases can be processed.");
        
        $purchase->processPurchase();
    }

    public function test_confirmed_purchase_with_items_can_be_processed()
    {
        $purchase = Purchase::factory()->create([
            'status' => PurchaseStatus::CONFIRMED->value
        ]);
        
        // Add an item to the purchase
        $item = Item::factory()->create();
        PurchaseItem::factory()->create([
            'purchase_id' => $purchase->id,
            'item_id' => $item->id,
            'quantity' => 10,
            'unit_cost' => 100
        ]);

        $purchase->load('items');
        
        $this->assertTrue($purchase->canBeProcessed());
        $this->assertEmpty($purchase->getProcessingValidationErrors());
    }
}