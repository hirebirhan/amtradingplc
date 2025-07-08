<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'purchase_id',
        'item_id',
        'quantity',
        'unit_cost',
        'closing_unit_price',
        'total_closing_cost',
        'profit_loss_per_item',
        'tax_rate',
        'discount',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'closing_unit_price' => 'decimal:2',
        'total_closing_cost' => 'decimal:2',
        'profit_loss_per_item' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Get the purchase that owns the purchase item.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the item that is being purchased.
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
        $subtotal = $this->quantity * $this->unit_cost;

        // Apply discount
        $subtotal -= $this->discount;

        // Apply tax
        $taxAmount = $subtotal * ($this->tax_rate / 100);
        $subtotal += $taxAmount;

        $this->subtotal = $subtotal;
        $this->save();
    }
}