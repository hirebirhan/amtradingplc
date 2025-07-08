<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'location_type',
        'location_id',
        'quantity',
        'reference_type',
        'reference_id',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'quantity' => 'decimal:2',
    ];

    /**
     * Get the item that is reserved
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the user who created the reservation
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the warehouse (if location_type is warehouse)
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'location_id');
    }

    /**
     * Get the branch (if location_type is branch)
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'location_id');
    }

    /**
     * Scope for active (non-expired) reservations
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope for expired reservations
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope for specific item
     */
    public function scopeForItem($query, int $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    /**
     * Scope for specific location
     */
    public function scopeForLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
                    ->where('location_id', $locationId);
    }

    /**
     * Check if reservation is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    /**
     * Get location name for display
     */
    public function getLocationNameAttribute(): string
    {
        if ($this->location_type === 'warehouse') {
            return $this->warehouse?->name ?? 'Unknown Warehouse';
        }
        return $this->branch?->name ?? 'Unknown Branch';
    }
} 