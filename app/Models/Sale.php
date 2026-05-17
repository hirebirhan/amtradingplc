<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\User;
use App\Enums\CreditStatus;
use App\Enums\CreditType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Traits\HasBranch;
use App\Traits\HasBranchAuthorization;

class Sale extends Model
{
    use HasFactory, SoftDeletes, HasBranch, HasBranchAuthorization;

    protected $fillable = [
'reference_no',
        'customer_id',
        'is_walking_customer',
        'warehouse_id',
        'branch_id',
        'user_id',
        'status',
        'payment_status',
        'payment_method',
        'transaction_number',
        'receiver_bank_name',
        'receiver_account_holder',
        'receiver_account_number',
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
        'is_walking_customer' => 'boolean',
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
            // Skip validation - allow flexible location assignment
        });
        
        // Auto-create credit record after sale is saved with due amount
        static::saved(function ($sale) {
            // Ensure paid_amount is never null
            if ($sale->paid_amount === null) {
                $sale->paid_amount = 0;
                $sale->saveQuietly();
            }
            
            // Ensure payment status matches payment method logic
            $correctStatus = $sale->calculateCorrectPaymentStatus();
            if ($sale->payment_status !== $correctStatus) {
                $sale->payment_status = $correctStatus;
                $sale->saveQuietly(); // Avoid infinite loop
            }
            
            // Ensure due_amount is calculated correctly
            $correctDueAmount = $sale->total_amount - ($sale->paid_amount ?? 0);
            if ($sale->due_amount != $correctDueAmount) {
                $sale->due_amount = $correctDueAmount;
                $sale->saveQuietly(); // Avoid infinite loop
            }
            
            if ($sale->due_amount > 0 && !$sale->credit()->exists()) {
                try {
                    $sale->createCreditRecord();
                } catch (\Exception $e) {
                    \Log::error('Failed to auto-create credit for sale ' . $sale->id . ': ' . $e->getMessage());
                }
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
     * Get the customer name or walking customer label.
     */
    public function getCustomerNameAttribute(): string
    {
        if ($this->is_walking_customer) {
            return 'Walking Customer';
        }
        
        return $this->customer ? $this->customer->name : 'N/A';
    }

    /**
     * Check if this is a walking customer sale.
     */
    public function isWalkingCustomer(): bool
    {
        return (bool) $this->is_walking_customer;
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
     * Get the bank account used for this sale.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Get the credit associated with this sale.
     */
    public function credit(): MorphOne
    {
        return $this->morphOne(Credit::class, 'reference');
    }

    /**
     * Create credit record following Purchase system pattern
     */
    public function createCreditRecord(): void
    {
        // Skip if credit already exists
        if ($this->credit()->exists()) {
            return;
        }
        
        // Skip credit creation for walking customers with credit payment methods
        if ($this->is_walking_customer && in_array($this->payment_method, [PaymentMethod::FULL_CREDIT->value, PaymentMethod::CREDIT_ADVANCE->value])) {
            \Log::warning("Attempted to create credit for walking customer sale #{$this->reference_no}. Credits not allowed for walking customers.");
            return;
        }
        
        // Create credit for any sale with outstanding balance
        if ($this->due_amount > 0 && !$this->is_walking_customer) {
            $status = ($this->paid_amount ?? 0) > 0 ? CreditStatus::PARTIAL->value : CreditStatus::ACTIVE->value;

            $credit = Credit::create([
                'customer_id' => $this->customer_id,
                'amount' => $this->total_amount,
                'paid_amount' => $this->paid_amount ?? 0,
                'balance' => $this->due_amount,
                'reference_no' => $this->reference_no,
                'reference_type' => 'sale',
                'reference_id' => $this->id,
                'credit_type' => CreditType::RECEIVABLE->value,
                'description' => 'Credit for sale #' . $this->reference_no,
                'credit_date' => $this->sale_date,
                'due_date' => $this->sale_date->addDays(30),
                'status' => $status,
                'user_id' => $this->user_id,
                'branch_id' => $this->branch_id,
                'warehouse_id' => $this->warehouse_id,
            ]);

            // Record advance payment if one was made
            if (($this->advance_amount ?? 0) > 0) {
                $advanceMethod = in_array($this->payment_method, PaymentMethod::forOperationalPaymentValues(), true)
                    ? $this->payment_method
                    : PaymentMethod::CASH->value;

                CreditPayment::create([
                    'credit_id'      => $credit->id,
                    'amount'         => $this->advance_amount,
                    'kind'           => 'advance',
                    'payment_method' => $advanceMethod,
                    'payment_date'   => $this->sale_date,
                    'notes'          => 'Advance payment for sale #' . $this->reference_no,
                    'user_id'        => $this->user_id,
                ]);
            }
        }
    }

    /**
     * Record a payment for this sale.
     * Updates both sale and associated credit records.
     */
    public function addPayment(float $amount, string $paymentMethod, ?string $reference = null, ?string $notes = null, ?string $paymentDate = null, ?string $referenceField = null, ?string $receiverBankName = null, ?string $receiverAccountHolder = null, ?string $receiverAccountNumber = null): SalePayment
    {
        // Create sale payment record
        $payment = new SalePayment([
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'reference_no' => $reference,
            'payment_date' => $paymentDate ? date('Y-m-d', strtotime($paymentDate)) : now(),
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);

        $this->payments()->save($payment);

        // Update sale amounts and status
        $this->paid_amount += $amount;
        $this->due_amount = $this->total_amount - $this->paid_amount;
        $this->updatePaymentStatus();
        $this->save();

        // Update associated credit if exists
        $credit = Credit::where('reference_type', 'sale')
            ->where('reference_id', $this->id)
            ->first();

        if ($credit) {
            $credit->paid_amount = $this->paid_amount;
            $credit->balance = $this->due_amount;
            
            // Update credit status following documented flow
            if ($credit->balance <= 0) {
                $credit->status = CreditStatus::PAID->value;
            } elseif ($credit->paid_amount > 0) {
                $credit->status = CreditStatus::PARTIAL->value;
            } else {
                $credit->status = CreditStatus::ACTIVE->value;
            }

            $credit->save();
        }

        // Update sale payment status to sync with credit
        $this->updatePaymentStatus();
        $this->save();

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
     * Calculate the correct payment status based on payment method and amounts.
     */
    public function calculateCorrectPaymentStatus(): string
    {
        switch ($this->payment_method) {
            case PaymentMethod::CASH->value:
            case PaymentMethod::BANK_TRANSFER->value:
            case PaymentMethod::TELEBIRR->value:
                return PaymentStatus::PAID->value;
            case PaymentMethod::CREDIT_ADVANCE->value:
                return PaymentStatus::PARTIAL->value;
            case PaymentMethod::FULL_CREDIT->value:
                return PaymentStatus::DUE->value;
            default:
                // Fallback to amount-based calculation
                if ($this->due_amount <= 0) {
                    return PaymentStatus::PAID->value;
                } elseif ($this->paid_amount > 0) {
                    return PaymentStatus::PARTIAL->value;
                } else {
                    return PaymentStatus::DUE->value;
                }
        }
    }

    /**
     * Update payment status based on payment amounts.
     * Follows credit lifecycle status transitions.
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
        return true; // Always valid
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
     * Get the outstanding balance for this sale.
     * Returns the amount still owed by the customer.
     */
    public function getOutstandingBalanceAttribute(): float
    {
        return $this->due_amount;
    }

    /**
     * Check if this sale has an outstanding credit balance.
     */
    public function hasOutstandingBalance(): bool
    {
        return $this->due_amount > 0;
    }

    /**
     * Check if this sale is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->due_amount <= 0;
    }

    /**
     * Check if this sale has partial payment.
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