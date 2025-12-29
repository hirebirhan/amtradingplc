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
use App\Models\Credit;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Traits\HasBranchAuthorization;

class Purchase extends Model
{
    use HasFactory, SoftDeletes, HasBranchAuthorization;

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
        'receiver_bank_name',
        'receiver_account_number',
        'advance_amount',
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
        'advance_amount' => 'decimal:2',
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
            
            // Set default status if not provided
            if (empty($purchase->status)) {
                $purchase->status = PurchaseStatus::CONFIRMED->value;
            }
        });
        
        // Auto-process purchase after creation based on payment method
        static::created(function ($purchase) {
            // Only auto-process cash/immediate payments
            if (in_array($purchase->payment_method, ['cash', 'bank_transfer', 'telebirr'])) {
                $purchase->processPurchase();
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
        if ($this->status === PurchaseStatus::RECEIVED->value) {
            return false; // Already processed
        }

        // Start a transaction
        DB::beginTransaction();

        try {
            // Update stock for each item
            foreach ($this->items as $purchaseItem) {
                $item = $purchaseItem->item;
                $pieces = (int)$purchaseItem->quantity; // Purchases are always in pieces
                $unitCapacity = $item->unit_quantity ?? 1;

                // Find or create the stock in the selected warehouse
                $stock = Stock::firstOrCreate(
                    [
                        'warehouse_id' => $this->warehouse_id,
                        'item_id' => $item->id,
                        'branch_id' => null, // Warehouse stock has no branch assignment
                    ],
                    [
                        'quantity' => 0,
                        'piece_count' => 0,
                        'total_units' => 0,
                    ]
                );

                // Add pieces to stock
                $stock->addPieces(
                    $pieces, 
                    $unitCapacity, 
                    'purchase', 
                    $this->id, 
                    'Item received - Purchase #' . $this->reference_no, 
                    $this->user_id
                );

                // Update item's cost_price and record price history if needed
                if ($this->update_cost_price && $item->cost_price != $purchaseItem->unit_cost) {
                    $oldCostPerPiece = $item->cost_price;
                    $oldCostPerUnit = $item->cost_price_per_unit;
                    
                    // The unit_cost from purchase item is the cost per piece
                    $costPerPiece = $purchaseItem->unit_cost;
                    $unitQuantity = $item->unit_quantity ?? 1;
                    $costPerUnit = $costPerPiece / $unitQuantity;
                    
                    // Update both cost prices
                    $item->cost_price = $costPerPiece; // Cost per piece
                    $item->cost_price_per_unit = $costPerUnit; // Cost per individual unit
                    $item->save();

                    // Record price history
                    PriceHistory::create([
                        'item_id' => $item->id,
                        'old_price' => $item->selling_price,
                        'new_price' => $item->selling_price,
                        'old_cost' => $oldCostPerPiece,
                        'new_cost' => $costPerPiece,
                        'change_type' => 'purchase',
                        'reference_id' => $this->id,
                        'reference_type' => get_class($this),
                        'user_id' => $this->user_id,
                        'notes' => "Cost updated from purchase #{$this->reference_no} - Per piece: {$costPerPiece}, Per unit: {$costPerUnit}",
                    ]);
                }
            }

            // Update purchase status
            $this->status = PurchaseStatus::RECEIVED->value;
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
     * Create credit with advance payment following the documented workflow.
     * Creates Credit record and advance Payment record atomically.
     */
    protected function createCreditRecord(): void
    {
        if ($this->due_amount <= 0) {
            return;
        }

        DB::transaction(function () {
            // Create credit record
            $credit = Credit::create([
                'supplier_id' => $this->supplier_id,
                'amount' => $this->total_amount,
                'paid_amount' => $this->paid_amount,
                'balance' => $this->due_amount,
                'reference_no' => $this->reference_no,
                'reference_type' => 'purchase',
                'reference_id' => $this->id,
                'credit_type' => 'payable',
                'description' => 'Credit for purchase #' . $this->reference_no,
                'credit_date' => $this->purchase_date,
                'due_date' => now()->addDays(30),
                'status' => $this->paid_amount > 0 ? 'partial' : 'active',
                'user_id' => $this->user_id,
                'branch_id' => $this->branch_id,
                'warehouse_id' => $this->warehouse_id,
            ]);

            // Create advance payment record if advance was made
            if ($this->advance_amount > 0) {
                $credit->addPayment(
                    $this->advance_amount,
                    $this->payment_method ?? 'cash',
                    $this->transaction_number,
                    'Advance payment for purchase #' . $this->reference_no,
                    null, // payment_date (use default)
                    'advance' // kind
                );
            }
        });
    }

    /**
     * Record a payment for this purchase.
     * Updates both purchase and associated credit records.
     */
    public function addPayment(float $amount, string $paymentMethod, ?string $reference = null, ?string $notes = null, ?string $paymentDate = null, ?string $referenceField = null, ?string $receiverBankName = null, ?string $receiverAccountHolder = null, ?string $receiverAccountNumber = null): PurchasePayment
    {
        // Create purchase payment record
        $payment = new PurchasePayment([
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'reference_no' => $reference,
            'payment_date' => $paymentDate ? date('Y-m-d', strtotime($paymentDate)) : now(),
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);

        $this->payments()->save($payment);

        // Update purchase amounts and status
        $this->paid_amount += $amount;
        $this->due_amount = $this->total_amount - $this->paid_amount;
        $this->updatePaymentStatus();
        $this->save();

        // Update associated credit if exists
        $credit = Credit::where('reference_type', 'purchase')
            ->where('reference_id', $this->id)
            ->first();

        if ($credit) {
            $credit->paid_amount = $this->paid_amount;
            $credit->balance = $this->due_amount;
            
            // Update credit status following documented flow
            if ($credit->balance <= 0) {
                $credit->status = 'paid';
            } elseif ($credit->paid_amount > 0) {
                $credit->status = 'partial';
            } else {
                $credit->status = 'active';
            }
            
            $credit->save();
        }

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
     * Update payment status based on payment amounts and method.
     * Follows business logic for different payment types.
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

    /**
     * Get the outstanding balance for this purchase.
     * Returns the amount still owed to the supplier.
     */
    public function getOutstandingBalanceAttribute(): float
    {
        return $this->due_amount;
    }

    /**
     * Check if this purchase has an outstanding credit balance.
     */
    public function hasOutstandingBalance(): bool
    {
        return $this->due_amount > 0;
    }

    /**
     * Check if this purchase is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->due_amount <= 0;
    }

    /**
     * Check if this purchase has partial payment.
     */
    public function hasPartialPayment(): bool
    {
        return $this->paid_amount > 0 && $this->due_amount > 0;
    }

    /**
     * Validate advance amount according to business rules.
     */
    public function validateAdvanceAmount(float $advanceAmount): array
    {
        $errors = [];

        if ($advanceAmount < 0) {
            $errors[] = 'Advance amount must be greater than or equal to 0.';
        }

        if ($advanceAmount > $this->total_amount) {
            $errors[] = 'Advance amount cannot exceed total amount.';
        }

        return $errors;
    }

    /**
     * Check if advance amount equals total (full payment).
     */
    public function isAdvanceFullPayment(): bool
    {
        return $this->advance_amount >= $this->total_amount;
    }


}