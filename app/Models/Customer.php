<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasBranch;
use App\Models\User;

class Customer extends Model
{
    use HasFactory, SoftDeletes, HasBranch;

    protected $fillable = [
'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'credit_limit',
        'balance',
        'customer_type',
        'branch_id',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Mutator for credit_limit to handle empty values
     */
    public function setCreditLimitAttribute($value)
    {
        $this->attributes['credit_limit'] = $this->sanitizeDecimal($value);
    }

    /**
     * Mutator for balance to handle empty values
     */
    public function setBalanceAttribute($value)
    {
        $this->attributes['balance'] = $this->sanitizeDecimal($value);
    }

    /**
     * Sanitize decimal values
     */
    private function sanitizeDecimal($value)
    {
        // Handle null, empty string, or boolean false
        if ($value === null || $value === '' || $value === false) {
            return '0.00';
        }
        
        // If it's already a valid numeric value, return it
        if (is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }
        
        // Remove any non-numeric characters except decimal point and minus sign
        $sanitized = preg_replace('/[^0-9.-]/', '', (string) $value);
        
        // Convert to float and ensure it's a valid number
        $float = is_numeric($sanitized) ? (float) $sanitized : 0.00;
        
        // Ensure it's not negative (for these fields)
        $float = max(0.00, $float);
        
        return number_format($float, 2, '.', '');
    }

    /**
     * Get the branch that the customer belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the sales for the customer.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the returns for the customer.
     */
    public function returns(): HasMany
    {
        return $this->hasMany(ReturnModel::class);
    }

    /**
     * Check if the customer has exceeded their credit limit.
     */
    public function hasExceededCreditLimit(float $amount): bool
    {
        return $this->credit_limit > 0 && ($this->balance + $amount) > $this->credit_limit;
    }

    /**
     * Update the customer's balance.
     */
    public function updateBalance(float $amount): void
    {
        $this->balance += $amount;
        $this->save();
    }

    /**
     * Scope a query to only include active customers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include retail customers.
     */
    public function scopeRetail($query)
    {
        return $query->where('customer_type', 'retail');
    }

    /**
     * Scope a query to only include wholesale customers.
     */
    public function scopeWholesale($query)
    {
        return $query->where('customer_type', 'wholesale');
    }

    /**
     * Scope a query to only include distributor customers.
     */
    public function scopeDistributor($query)
    {
        return $query->where('customer_type', 'distributor');
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