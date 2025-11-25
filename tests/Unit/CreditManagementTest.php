<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\User;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreditManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_with_advance_creates_proper_credit_record()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        
        $sale = Sale::create([
            'reference_no' => 'SALE-TEST-001',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'total_amount' => 1000.00,
            'paid_amount' => 400.00,
            'advance_amount' => 400.00, // Advance payment
            'due_amount' => 600.00,
            'payment_status' => PaymentStatus::PARTIAL->value,
            'payment_method' => 'cash',
            'sale_date' => now(),
            'status' => 'pending'
        ]);

        // Act - Process the sale to create credit
        $sale->processSale();

        // Assert credit record
        $credit = Credit::where('reference_type', 'sale')
            ->where('reference_id', $sale->id)
            ->first();

        $this->assertNotNull($credit);
        $this->assertEquals(1000.00, $credit->amount);
        $this->assertEquals(400.00, $credit->paid_amount);
        $this->assertEquals(600.00, $credit->balance);
        $this->assertEquals('partial', $credit->status);

        // Assert advance payment record
        $advancePayment = $credit->payments()->where('kind', 'advance')->first();
        $this->assertNotNull($advancePayment);
        $this->assertEquals(400.00, $advancePayment->amount);
        $this->assertEquals('advance', $advancePayment->kind);
    }

    public function test_full_credit_creates_proper_credit_record()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        
        $sale = Sale::create([
            'reference_no' => 'SALE-TEST-002',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'total_amount' => 800.00,
            'paid_amount' => 0.00,
            'due_amount' => 800.00,
            'payment_status' => PaymentStatus::DUE->value,
            'payment_method' => 'credit_full',
            'sale_date' => now(),
            'status' => 'pending'
        ]);

        // Act
        $sale->processSale();

        // Assert
        $credit = Credit::where('reference_type', 'sale')
            ->where('reference_id', $sale->id)
            ->first();

        $this->assertNotNull($credit);
        $this->assertEquals(800.00, $credit->amount);
        $this->assertEquals(0.00, $credit->paid_amount);
        $this->assertEquals(800.00, $credit->balance);
        $this->assertEquals('pending', $credit->status); // No advance payment
    }

    public function test_credit_payment_updates_both_sale_and_credit()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        
        $sale = Sale::create([
            'reference_no' => 'SALE-TEST-003',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'total_amount' => 1000.00,
            'paid_amount' => 300.00,
            'due_amount' => 700.00,
            'payment_status' => PaymentStatus::PARTIAL->value,
            'payment_method' => 'credit_advance',
            'sale_date' => now(),
            'status' => 'completed'
        ]);

        // Create credit record
        $credit = Credit::create([
            'customer_id' => $customer->id,
            'amount' => 1000.00,
            'paid_amount' => 300.00,
            'balance' => 700.00,
            'reference_no' => $sale->reference_no,
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'credit_type' => 'receivable',
            'status' => 'partial',
            'credit_date' => now(),
            'due_date' => now()->addDays(30),
            'user_id' => $user->id,
        ]);

        // Act - Make a payment
        $sale->addPayment(200.00, 'cash');

        // Assert - Check sale updates
        $sale->refresh();
        $this->assertEquals(500.00, $sale->paid_amount); // 300 + 200
        $this->assertEquals(500.00, $sale->due_amount); // 1000 - 500
        $this->assertEquals(PaymentStatus::PARTIAL->value, $sale->payment_status);

        // Assert - Check credit updates
        $credit->refresh();
        $this->assertEquals(500.00, $credit->paid_amount);
        $this->assertEquals(500.00, $credit->balance);
        $this->assertEquals('partial', $credit->status);
    }

    public function test_full_payment_marks_credit_as_paid()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        
        $sale = Sale::create([
            'reference_no' => 'SALE-TEST-004',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'total_amount' => 500.00,
            'paid_amount' => 300.00,
            'due_amount' => 200.00,
            'payment_status' => PaymentStatus::PARTIAL->value,
            'payment_method' => 'credit_advance',
            'sale_date' => now(),
            'status' => 'completed'
        ]);

        $credit = Credit::create([
            'customer_id' => $customer->id,
            'amount' => 500.00,
            'paid_amount' => 300.00,
            'balance' => 200.00,
            'reference_no' => $sale->reference_no,
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'credit_type' => 'receivable',
            'status' => 'partial',
            'credit_date' => now(),
            'due_date' => now()->addDays(30),
            'user_id' => $user->id,
        ]);

        // Act - Pay remaining balance
        $sale->addPayment(200.00, 'cash');

        // Assert
        $sale->refresh();
        $credit->refresh();
        
        $this->assertEquals(500.00, $sale->paid_amount);
        $this->assertEquals(0.00, $sale->due_amount);
        $this->assertEquals(PaymentStatus::PAID->value, $sale->payment_status);
        
        $this->assertEquals(500.00, $credit->paid_amount);
        $this->assertEquals(0.00, $credit->balance);
        $this->assertEquals('paid', $credit->status);
    }

    public function test_advance_amount_validation()
    {
        // Arrange
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        
        $sale = new Sale([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'total_amount' => 1000.00,
            'sale_date' => now(),
        ]);

        // Test negative advance amount
        $errors = $sale->validateAdvanceAmount(-100);
        $this->assertContains('Advance amount must be greater than or equal to 0.', $errors);

        // Test advance amount exceeding total
        $errors = $sale->validateAdvanceAmount(1200);
        $this->assertContains('Advance amount cannot exceed total amount.', $errors);

        // Test valid advance amount
        $errors = $sale->validateAdvanceAmount(500);
        $this->assertEmpty($errors);

        // Test full payment detection
        $sale->advance_amount = 1000;
        $this->assertTrue($sale->isAdvanceFullPayment());
        
        $sale->advance_amount = 500;
        $this->assertFalse($sale->isAdvanceFullPayment());
    }

    public function test_purchase_credit_with_advance_creates_proper_credit_record()
    {
        // Arrange
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        
        $purchase = Purchase::create([
            'reference_no' => 'PO-TEST-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'total_amount' => 2000.00,
            'paid_amount' => 800.00,
            'advance_amount' => 800.00, // Advance payment
            'due_amount' => 1200.00,
            'payment_status' => PaymentStatus::PARTIAL->value,
            'payment_method' => 'bank_transfer',
            'purchase_date' => now(),
            'status' => 'pending'
        ]);

        // Act - Process the purchase to create credit
        $purchase->processPurchase();

        // Assert credit record
        $credit = Credit::where('reference_type', 'purchase')
            ->where('reference_id', $purchase->id)
            ->first();

        $this->assertNotNull($credit);
        $this->assertEquals(2000.00, $credit->amount);
        $this->assertEquals(800.00, $credit->paid_amount);
        $this->assertEquals(1200.00, $credit->balance);
        $this->assertEquals('partial', $credit->status);
        $this->assertEquals('payable', $credit->credit_type);

        // Assert advance payment record
        $advancePayment = $credit->payments()->where('kind', 'advance')->first();
        $this->assertNotNull($advancePayment);
        $this->assertEquals(800.00, $advancePayment->amount);
        $this->assertEquals('advance', $advancePayment->kind);
    }

    public function test_purchase_advance_amount_validation()
    {
        // Arrange
        $supplier = Supplier::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $user = User::factory()->create();
        
        $purchase = new Purchase([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'total_amount' => 1500.00,
            'purchase_date' => now(),
        ]);

        // Test negative advance amount
        $errors = $purchase->validateAdvanceAmount(-200);
        $this->assertContains('Advance amount must be greater than or equal to 0.', $errors);

        // Test advance amount exceeding total
        $errors = $purchase->validateAdvanceAmount(2000);
        $this->assertContains('Advance amount cannot exceed total amount.', $errors);

        // Test valid advance amount
        $errors = $purchase->validateAdvanceAmount(750);
        $this->assertEmpty($errors);

        // Test full payment detection
        $purchase->advance_amount = 1500;
        $this->assertTrue($purchase->isAdvanceFullPayment());
        
        $purchase->advance_amount = 750;
        $this->assertFalse($purchase->isAdvanceFullPayment());
    }
}