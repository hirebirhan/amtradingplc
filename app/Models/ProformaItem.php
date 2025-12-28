<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'proforma_id',
        'item_id',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // Relationships
    public function proforma(): BelongsTo
    {
        return $this->belongsTo(Proforma::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    // Accessors
    public function getFormattedQuantityAttribute(): string
    {
        return number_format($this->quantity, $this->quantity == floor($this->quantity) ? 0 : 2);
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return 'ETB ' . number_format($this->unit_price, 2);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return 'ETB ' . number_format($this->subtotal, 2);
    }
}