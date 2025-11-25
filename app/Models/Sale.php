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
use App\Models\User;
use App\Enums\PaymentStatus;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
'reference_no',
        'customer_id',
        'warehouse_id',
        'branch_id',
        'user_id',
        'status',
        'payment_status',
        'payment_method',
        'transaction_number',
        'bank_account_id',
        'receipt_url',
        'receipt_image',
        'advance_amount',
        'total_amount',
        'paid_amount',
        'discount',
        'tax',
        'shipping',
        'due_amount',
        'sale_date',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Create a unique reference number when creating a new sale, only if not already set
        static::creating(function ($sale) {
            if (empty($sale->reference_no)) {
                $sale->reference_no = 'SALE-' . date('Ymd') . '-' . Str::padLeft(Sale::count() + 1, 5, '0');
            }
        });

        // Validate location before creating or updating
        static::saving(function ($sale) {
            if (!$sale->validateLocation()) {
                throw new \InvalidArgumentException('Sale must have either branch_id OR warehouse_id, not both or neither.');
            }
        });
    }

    /**
     * Get the customer that owns the sale.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the warehouse that owns the sale.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the branch that owns the sale.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user that created the sale.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the sale.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get the items for the sale (alias for items).
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get the payments for the sale.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    /**
     * Get the returns for the sale.
     */
    public function returns(): HasMany
    {
        return $this->hasMany(ReturnModel::class);
    }

    /**
     * Get the credit associated with this sale.
     */
    public function credit(): MorphOne
    {
        return $this->morphOne(Credit::class, 'reference');
    }

    /**
     * Process the sale and update stock.
     */
    public function processSale(): bool
    {
        if ($this->status === 'completed') {
            return false; // Already processed
        }

        // Start a transaction
        DB::beginTransaction();

        try {
            // Update stock for each item based on sale type
            foreach ($this->items as $saleItem) {
                $item = $saleItem->item;

                if ($this->isWarehouseSale()) {
                    // Warehouse sale - deduct from specific warehouse
                    $this->processWarehouseSaleItem($item, $saleItem);
                } elseif ($this->isBranchSale()) {
                    // Branch sale - deduct from warehouses serving the branch
                    $this->processBranchSaleItem($item, $saleItem);
                } else {
                    throw new \Exception('Invalid sale location configuration.');
                }
            }

            // Update sale status
            $this->status = 'completed';
            $this->save();

            // If the sale is not fully paid, create a credit record
            if (in_array($this->payment_status, [PaymentStatus::DUE->value, PaymentStatus::PARTIAL->value, 'credit'], true)) {
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
     * Process stock deduction for warehouse sale.
     */
    private function processWarehouseSaleItem($item, $saleItem): void
    {
        $stock = Stock::where('warehouse_id', $this->warehouse_id)
            ->where('item_id', $item->id)
            ->first();

        if (!$stock) {
            throw new \Exception('No stock found for item: ' . $item->name . ' in warehouse');
        }

        $quantity = $saleItem->quantity;
        $unitCapacity = $item->unit_quantity ?? 1;
        
        // Process based on sale method
        if ($saleItem->isSoldByPiece()) {
            $stock->sellByPiece(
                (int)$quantity, 
                $unitCapacity, 
                'sale', 
                $this->id, 
                'Warehouse sale by piece - Sale #' . $this->reference_no, 
                $this->user_id
            );
        } else {
            $stock->sellByUnit(
                $quantity, 
                $unitCapacity, 
                'sale', 
                $this->id, 
                'Warehouse sale by unit - Sale #' . $this->reference_no, 
                $this->user_id
            );
        }
    }

    /**
     * Process stock deduction for branch sale.
     */
    private function processBranchSaleItem($item, $saleItem): void
    {
        // Get warehouses serving this branch
        $warehouseIds = DB::table('branch_warehouse')
            ->where('branch_id', $this->branch_id)
            ->pluck('warehouse_id')
            ->toArray();

        if (empty($warehouseIds)) {
            throw new \Exception('No warehouses found for branch: ' . $this->branch->name);
        }

        $quantity = $saleItem->quantity;
        $unitCapacity = $item->unit_quantity ?? 1;
        $remainingQuantity = $quantity;

        foreach ($warehouseIds as $warehouseId) {
            if ($remainingQuantity <= 0) break;

            $stock = Stock::where('warehouse_id', $warehouseId)
                ->where('item_id', $item->id)
                ->first();

            if (!$stock) continue;

            if ($saleItem->isSoldByPiece()) {
                $availablePieces = $stock->piece_count;
                if ($availablePieces <= 0) continue;
                
                $deductPieces = min($remainingQuantity, $availablePieces);
                
                $stock->sellByPiece(
                    (int)$deductPieces, 
                    $unitCapacity, 
                    'sale', 
                    $this->id, 
                    'Branch sale by piece - Sale #' . $this->reference_no, 
                    $this->user_id
                );
                
                $remainingQuantity -= $deductPieces;
            } else {
                $availableUnits = $stock->total_units;
                if ($availableUnits <= 0) continue;
                
                $deductUnits = min($remainingQuantity, $availableUnits);
                
                $stock->sellByUnit(
                    $deductUnits, 
                    $unitCapacity, 
                    'sale', 
                    $this->id, 
                    'Branch sale by unit - Sale #' . $this->reference_no, 
                    $this->user_id
                );
                
                $remainingQuantity -= $deductUnits;
            }
        }

        if ($remainingQuantity > 0) {
            $method = $saleItem->isSoldByPiece() ? 'pieces' : 'units';
            throw new \Exception("Insufficient stock for item '{$item->name}'. Could not fulfill {$remainingQuantity} {$method} from branch warehouses.");
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
            'customer_id' => $this->customer_id,
            'amount' => $this->due_amount,
            'paid_amount' => 0,
            'balance' => $this->due_amount,
            'reference_no' => $this->reference_no,
            'reference_type' => 'sale',
            'reference_id' => $this->id,
            'credit_type' => 'receivable',
            'description' => 'Credit for sale #' . $this->reference_no,
            'credit_date' => $this->sale_date,
            'due_date' => now()->addDays(30), // Default 30-day term
            'status' => 'active',
            'user_id' => $this->user_id,
            'branch_id' => $this->branch_id,
            'warehouse_id' => $this->warehouse_id,
        ]);
    }

    /**
     * Record a payment for this sale.
     */
    public function addPayment(float $amount, string $paymentMethod, ?string $reference = null, ?string $notes = null, ?string $paymentDate = null, ?string $referenceField = null, ?string $receiverBankName = null, ?string $receiverAccountHolder = null, ?string $receiverAccountNumber = null): SalePayment
    {
        $payment = new SalePayment([
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'reference_no' => $reference,
            'payment_date' => $paymentDate ? date('Y-m-d', strtotime($paymentDate)) : now(),
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);

        $this->payments()->save($payment);

        // Log before update
        \Log::info('Sale payment before update', [
            'sale_id' => $this->id,
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
        \Log::info('Sale payment after update', [
            'sale_id' => $this->id,
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
        } elseif ($this->payment_status !== 'credit') {
            $this->payment_status = PaymentStatus::DUE->value;
        }
        $this->save();
    }

    /**
     * Get the total attribute.
     * This serves as an alias for total_amount
     */
    public function getTotalAttribute()
    {
        return $this->total_amount;
    }

    /**
     * Get the selling location (either branch or warehouse).
     * Returns the location name with type identifier.
     */
    public function getSellingLocationAttribute(): string
    {
        if ($this->branch_id && $this->branch) {
            return $this->branch->name . ' (Branch)';
        }
        
        if ($this->warehouse_id && $this->warehouse) {
            return $this->warehouse->name . ' (Warehouse)';
        }
        
        return 'Unknown Location';
    }

    /**
     * Get the selling location type.
     */
    public function getSellingLocationTypeAttribute(): string
    {
        if ($this->branch_id) {
            return 'branch';
        }
        
        if ($this->warehouse_id) {
            return 'warehouse';
        }
        
        return 'unknown';
    }

    /**
     * Check if this is a branch sale.
     */
    public function isBranchSale(): bool
    {
        return !empty($this->branch_id) && empty($this->warehouse_id);
    }

    /**
     * Check if this is a warehouse sale.
     */
    public function isWarehouseSale(): bool
    {
        return !empty($this->warehouse_id) && empty($this->branch_id);
    }

    /**
     * Validate that the sale has either branch_id OR warehouse_id, not both.
     */
    public function validateLocation(): bool
    {
        // Must have either branch_id OR warehouse_id, but not both or neither
        $hasBranch = !empty($this->branch_id);
        $hasWarehouse = !empty($this->warehouse_id);
        
        return ($hasBranch && !$hasWarehouse) || (!$hasBranch && $hasWarehouse);
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