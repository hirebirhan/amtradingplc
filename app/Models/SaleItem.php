<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'item_id',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Get the sale that owns the sale item.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the item that is being sold.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Calculate the subtotal for this item.
     */
    public function calculateSubtotal(): void
    {
        // Basic cost
        $subtotal = $this->quantity * $this->unit_price;

        // Apply discount
        $subtotal -= $this->discount;

        // Apply tax
        $taxAmount = $subtotal * ($this->tax_rate / 100);
        $subtotal += $taxAmount;

        $this->subtotal = $subtotal;
        $this->save();
    }
}