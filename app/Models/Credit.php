<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\User;

class Credit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
'customer_id',
        'supplier_id',
        'amount',
        'paid_amount',
        'balance',
        'reference_no',
        'reference_type', // 'sale', 'purchase', 'manual'
        'reference_id',
        'credit_type', // 'receivable', 'payable'
        'description',
        'credit_date',
        'due_date',
        'status', // 'active', 'partially_paid', 'paid', 'overdue', 'cancelled'
        'user_id',
        'branch_id',
        'warehouse_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'credit_date' => 'date',
        'due_date' => 'date',
    ];

    /**
     * Get the customer associated with the credit (for receivables).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the supplier associated with the credit (for payables).
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the sale associated with the credit.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'reference_id');
    }

    /**
     * Get the purchase associated with the credit.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'reference_id');
    }

    /**
     * Polymorphic relationship for the reference.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Get the URL for the reference.
     */
    public function getReferenceUrlAttribute(): ?string
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }
        
        return match($this->reference_type) {
            'purchase' => route('admin.purchases.show', $this->reference_id),
            'sale' => route('admin.sales.show', $this->reference_id),
            default => null,
        };
    }

    /**
     * Get the user who created the credit.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch associated with the credit.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the warehouse associated with the credit.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the payments made against this credit.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    /**
     * Record a payment against this credit.
     */
    public function addPayment(float $amount, string $paymentMethod, ?string $reference = null, ?string $notes = null, ?string $paymentDate = null, ?string $referenceField = null, ?string $receiverBankName = null, ?string $receiverAccountHolder = null, ?string $receiverAccountNumber = null): CreditPayment
    {
        // Start timing for performance monitoring
        $startTime = microtime(true);
        
        // Log before making any changes
        \Log::info('Credit payment initiated', [
            'credit_id' => $this->id,
            'reference_no' => $this->reference_no,
            'amount' => $amount,
            'method' => $paymentMethod,
            'current_balance' => $this->balance,
            'current_status' => $this->status
        ]);

        $payment = new CreditPayment([
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'reference_no' => $reference,
            'payment_date' => $paymentDate ? date('Y-m-d', strtotime($paymentDate)) : now(),
            'notes' => $notes,
            'reference' => $referenceField,
            'receiver_bank_name' => $receiverBankName,
            'receiver_account_holder' => $receiverAccountHolder,
            'receiver_account_number' => $receiverAccountNumber,
            'user_id' => auth()->id(),
        ]);

        $this->payments()->save($payment);

        // Recalculate paid amount and balance
        $paidTotal = $this->paid_amount + $amount;
        $newBalance = $this->amount - $paidTotal;
        
        // Update credit
        $this->paid_amount = $paidTotal;
        $this->balance = max(0, $newBalance); // Ensure balance doesn't go below zero
        
        // Update status
        if ($this->balance <= 0) {
            $this->status = 'paid';
        } else {
            $this->status = 'partially_paid';
        }
        
        // Save with monitoring
        \Log::info('Credit before save', [
            'credit_id' => $this->id,
            'status' => $this->status, 
            'paid_amount' => $this->paid_amount,
            'balance' => $this->balance
        ]);
        
        $saveResult = $this->save();
        
        // Log any issues if save failed
        if (!$saveResult) {
            \Log::error('Credit payment save failed', [
                'credit_id' => $this->id,
                'status' => $this->status,
                'paid_amount' => $this->paid_amount, 
                'balance' => $this->balance
            ]);
        }
        
        // Force refresh model from DB to ensure data is consistent
        $this->refresh();
        
        // Log final state to verify proper update
        \Log::info('Credit after payment', [
            'credit_id' => $this->id,
            'new_status' => $this->status,
            'new_paid_amount' => $this->paid_amount,
            'new_balance' => $this->balance,
            'execution_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
        ]);

        return $payment;
    }

    /**
     * Scope a query to only include active credits.
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereIn('status', ['active', 'partially_paid', 'overdue'])
              ->where('balance', '>', 0);
        });
    }

    /**
     * Scope a query to only include fully paid credits.
     */
    public function scopePaid($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'paid')
              ->orWhere('balance', '<=', 0);
        });
    }

    /**
     * Scope a query to only include receivables.
     */
    public function scopeReceivables($query)
    {
        return $query->where('credit_type', 'receivable');
    }

    /**
     * Scope a query to only include payables.
     */
    public function scopePayables($query)
    {
        return $query->where('credit_type', 'payable');
    }

    /**
     * Scope a query to only include overdue credits.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())->whereIn('status', ['active', 'partially_paid']);
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
