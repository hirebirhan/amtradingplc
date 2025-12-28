<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_name',
        'account_number',
        'bank_name',
        'branch_name',
        'swift_code',
        'balance',
        'currency',
        'is_active',
        'is_default',
        'branch_id',
        'warehouse_id',
        'notes',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the branch that the bank account belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    
    /**
     * Get the warehouse that the bank account belongs to.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Update the bank account's balance.
     */
    public function updateBalance(float $amount): void
    {
        $this->balance += $amount;
        $this->save();
    }

    /**
     * Set this account as the default account.
     */
    public function setAsDefault(): void
    {
        // Reset all other accounts
        self::where('is_default', true)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this account as default
        $this->is_default = true;
        $this->save();
    }

    /**
     * Scope a query to only include active bank accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order bank accounts by account name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('account_name');
    }

    /**
     * Scope a query to get the default bank account.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Format the balance with currency symbol.
     */
    public function getFormattedBalanceAttribute(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'ETB' => 'Br',
        ];

        $symbol = $symbols[$this->currency] ?? '';
        return $symbol . number_format($this->balance, 2);
    }
    
    /**
     * Get the location (branch or warehouse) name.
     */
    public function getLocationNameAttribute(): string
    {
        if ($this->branch_id) {
            return $this->branch->name ?? 'Unknown Branch';
        } elseif ($this->warehouse_id) {
            return $this->warehouse->name ?? 'Unknown Warehouse';
        }
        return 'Unassigned';
    }
}
