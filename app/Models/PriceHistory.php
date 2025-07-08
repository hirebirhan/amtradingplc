<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PriceHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'price_history';

    protected $fillable = [
        'item_id',
        'old_price',
        'new_price',
        'old_cost',
        'new_cost',
        'change_type',
        'reference_id',
        'reference_type',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'old_cost' => 'decimal:2',
        'new_cost' => 'decimal:2',
    ];

    /**
     * Get the item that owns the price history.
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
     * Get the reference model (purchase or sale).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Record a price change.
     */
    public static function recordChange(
        Item $item,
        float $oldPrice,
        float $newPrice,
        ?float $oldCost,
        ?float $newCost,
        string $changeType,
        ?Model $reference = null,
        ?string $notes = null
    ): self {
        return self::create([
            'item_id' => $item->id,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'old_cost' => $oldCost,
            'new_cost' => $newCost,
            'change_type' => $changeType,
            'reference_id' => $reference?->id,
            'reference_type' => $reference ? get_class($reference) : null,
            'user_id' => auth()->id(),
            'notes' => $notes,
        ]);
    }
}