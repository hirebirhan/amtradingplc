<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\PriceHistory;
use App\Models\User;
use App\Enums\PaymentStatus;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
'reference_no',
        'supplier_id',
        'branch_id',
        'warehouse_id',
        'user_id',
        'status',
        'payment_status',
        'payment_method',
        'bank_account_id',
        'transaction_number',
        'total_amount',
        'paid_amount',
        'due_amount',
        'discount',
        'tax',
        'shipping',
        'purchase_date',
        'notes',
        'update_cost_price',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'update_cost_price' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Create a unique reference number when creating a new purchase, but only if not already set
        static::creating(function ($purchase) {
            // Only generate a reference number if one isn't already provided
            if (empty($purchase->reference_no)) {
                $purchase->reference_no = 'PO-' . date('Ymd') . '-' . Str::padLeft(Purchase::count() + 1, 5, '0');
            }
            
            // Set purchase_date to current date if not set
            if (empty($purchase->purchase_date)) {
                $purchase->purchase_date = now()->format('Y-m-d');
            }
        });

        // Add a 'deleting' event listener to handle cascading deletes
        static::deleting(function ($purchase) {
            // Start a database transaction to ensure atomicity
            DB::transaction(function () use ($purchase) {
                // 1. Reverse stock for each purchase item
                foreach ($purchase->items as $item) {
                    $stock = Stock::where('warehouse_id', $purchase->warehouse_id)
                                  ->where('item_id', $item->item_id)
                                  ->first();

                    if ($stock) {
                        // Create a stock history record for the reversal
                        StockHistory::create([
                            'item_id' => $item->item_id,
                            'warehouse_id' => $purchase->warehouse_id,
                            'quantity_change' => -$item->quantity, // Negative quantity
                            'quantity_before' => $stock->quantity,
                            'quantity_after' => $stock->quantity - $item->quantity,
                            'reference_type' => 'purchase_deleted',
                            'reference_id' => $purchase->id,
                            'user_id' => auth()->id() ?? $purchase->user_id,
                            'description' => 'Purchase Deleted: ' . $purchase->reference_no,
                        ]);

                        // Decrease the stock quantity
                        $stock->quantity -= $item->quantity;
                        $stock->save();
                    }
                }

                // 2. Delete associated credit and its payments
                if ($purchase->credit) {
                    // Manually delete payments associated with the credit first
                    $purchase->credit->payments()->delete();
                    // Now delete the credit itself
                    $purchase->credit->delete();
                }

                // 3. Delete direct purchase payments (if any)
                $purchase->payments()->delete();

                // 4. Delete purchase items
                $purchase->items()->delete();
            });
        });
    }

    /**
     * Get the supplier that owns the purchase.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the warehouse that owns the purchase.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the branch that owns the purchase.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user that created the purchase.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the purchase.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Get the items for the purchase (alias for items).
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Get the payments for the purchase.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    /**
     * Get the bank account used for this purchase.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Get the credit associated with this purchase.
     */
    public function credit(): MorphOne
    {
        return $this->morphOne(Credit::class, 'reference');
    }

    /**
     * Process the purchase and update stock.
     */
    public function processPurchase(): bool
    {
        if ($this->status === 'received') {
            return false; // Already processed
        }

        // Start a transaction
        DB::beginTransaction();

        try {
            // Update stock for each item
            foreach ($this->items as $purchaseItem) {
                $item = $purchaseItem->item;
                $quantity = $purchaseItem->quantity;

                // Find or create the stock in the selected warehouse
                $stock = Stock::firstOrCreate(
                    [
                        'warehouse_id' => $this->warehouse_id,
                        'item_id' => $item->id,
                    ],
                    [
                        'quantity' => 0,
                    ]
                );

                // Update stock quantity
                $stock->quantity += $quantity;
                $stock->save();

                // Update item's cost_price and record price history if needed
                if ($this->update_cost_price && $item->cost_price != $purchaseItem->unit_cost) {
                    $oldCost = $item->cost_price;
                    $item->cost_price = $purchaseItem->unit_cost;
                    $item->save();

                    // Record price history
                    PriceHistory::create([
                        'item_id' => $item->id,
                        'old_price' => $item->selling_price, // No change in selling price
                        'new_price' => $item->selling_price, // No change in selling price
                        'old_cost' => $oldCost,
                        'new_cost' => $purchaseItem->unit_cost,
                        'change_type' => 'purchase',
                        'reference_id' => $this->id,
                        'reference_type' => get_class($this),
                        'user_id' => $this->user_id,
                        'notes' => 'Cost updated from purchase #' . $this->reference_no,
                    ]);
                }

                // Record stock history
                StockHistory::create([
                    'item_id' => $item->id,
                    'warehouse_id' => $this->warehouse_id,
                    'quantity_change' => $quantity,
                    'quantity_before' => $stock->quantity - $quantity,
                    'quantity_after' => $stock->quantity,
                    'reference_type' => 'purchase',
                    'reference_id' => $this->id,
                    'user_id' => $this->user_id,
                    'description' => 'Item received - Purchase #' . $this->reference_no,
                ]);
            }

            // Update purchase status
            $this->status = 'received';
            $this->save();

            // If the purchase is not fully paid, create a credit record
            if (in_array($this->payment_status, [PaymentStatus::DUE->value, PaymentStatus::PARTIAL->value], true)) {
                $this->createCreditRecord();
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a credit record for unpaid amount.
     */
    protected function createCreditRecord(): void
    {
        // Only create if there's an outstanding amount
        if ($this->due_amount <= 0) {
            return;
        }

        // Create credit record
        Credit::create([
            'supplier_id' => $this->supplier_id,
            'amount' => $this->due_amount,
            'paid_amount' => 0,
            'balance' => $this->due_amount,
            'reference_no' => $this->reference_no,
            'reference_type' => 'purchase',
            'reference_id' => $this->id,
            'credit_type' => 'payable',
            'description' => 'Credit for purchase #' . $this->reference_no,
            'credit_date' => $this->purchase_date,
            'due_date' => now()->addDays(30), // Default 30-day term
            'status' => 'active',
            'user_id' => $this->user_id,
            'branch_id' => $this->branch_id,
            'warehouse_id' => $this->warehouse_id,
        ]);
    }

    /**
     * Record a payment for this purchase.
     */
    public function addPayment(float $amount, string $paymentMethod, ?string $reference = null, ?string $notes = null, ?string $paymentDate = null, ?string $referenceField = null, ?string $receiverBankName = null, ?string $receiverAccountHolder = null, ?string $receiverAccountNumber = null): PurchasePayment
    {
        $payment = new PurchasePayment([
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'reference_no' => $reference,
            'payment_date' => $paymentDate ? date('Y-m-d', strtotime($paymentDate)) : now(),
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);

        $this->payments()->save($payment);

        // Log before update
        \Log::info('Purchase payment before update', [
            'purchase_id' => $this->id,
            'before_paid_amount' => $this->paid_amount,
            'before_due_amount' => $this->due_amount,
            'before_status' => $this->payment_status,
            'payment_amount' => $amount,
        ]);

        // Update the paid and due amounts
        $this->paid_amount += $amount;
        $this->due_amount = $this->total_amount - $this->paid_amount;

        // Update payment status
        $this->updatePaymentStatus();
        
        // Make sure we have fresh data
        $this->refresh();

        // Log after update
        \Log::info('Purchase payment after update', [
            'purchase_id' => $this->id,
            'after_paid_amount' => $this->paid_amount,
            'after_due_amount' => $this->due_amount,
            'after_status' => $this->payment_status,
        ]);

        // Note: Credit payments are handled separately to avoid circular payment calls
        // The credit payment component will handle updating both credit and purchase/sale

        return $payment;
    }

    /**
     * Calculate due amount based on total and paid amounts.
     */
    public function calculateDueAmount(): void
    {
        $this->due_amount = $this->total_amount - $this->paid_amount;
        $this->save();
    }

    /**
     * Update payment status based on payment.
     */
    public function updatePaymentStatus(): void
    {
        if ($this->due_amount <= 0) {
            $this->payment_status = PaymentStatus::PAID->value;
        } elseif ($this->paid_amount > 0) {
            $this->payment_status = PaymentStatus::PARTIAL->value;
        } else {
            $this->payment_status = PaymentStatus::DUE->value;
        }
        $this->save();
    }

    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this record.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}