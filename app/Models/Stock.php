<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Branch;

class Stock extends Model
{
    use HasFactory;

        /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'warehouse_id',
        'branch_id',
        'item_id',
        'quantity',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'warehouse_id' => 'integer',
        'branch_id' => 'integer',
        'item_id' => 'integer',
        'quantity' => 'decimal:2',
    ];

    /**
     * Get the warehouse that owns the stock.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the item that owns the stock.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the branch that owns the stock.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Update stock level and create a history record.
     *
     * @param float $quantity
     * @param string $referenceType
     * @param int|null $referenceId
     * @param string|null $description
     * @param int|null $userId
     * @return void
     */
    public function updateStock(
        float $quantity,
        string $referenceType,
        ?int $referenceId = null,
        ?string $description = null,
        ?int $userId = null
    ): void {
        $quantityBefore = $this->quantity;
        $this->quantity += $quantity;
        $this->save();

        // Create stock history record
        StockHistory::create([
            'warehouse_id' => $this->warehouse_id,
            'item_id' => $this->item_id,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->quantity,
            'quantity_change' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'user_id' => $userId,
        ]);
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
