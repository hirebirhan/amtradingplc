<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'warehouse_id',
        'item_id',
        'quantity_before',
        'quantity_after',
        'quantity_change',
        'reference_type',
        'reference_id',
        'description',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'warehouse_id' => 'integer',
        'item_id' => 'integer',
        'quantity_before' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'quantity_change' => 'decimal:2',
        'reference_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Get the warehouse that owns the stock history.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the item that owns the stock history.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the user who made the change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the owning reference model.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
