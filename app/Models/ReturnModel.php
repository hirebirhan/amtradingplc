<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'returns';

    protected $fillable = [
        'reference_no',
        'return_type',
        'sale_id',
        'purchase_id',
        'customer_id',
        'supplier_id',
        'warehouse_id',
        'user_id',
        'status',
        'total_amount',
        'refunded_amount',
        'return_date',
        'reason',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
    ];

    /**
     * Get the sale that owns the return.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the purchase that owns the return.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the customer that owns the return.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the supplier that owns the return.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the warehouse that owns the return.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user that created the return.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the return.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    /**
     * Process the return: add items back to warehouse inventory.
     */
    public function process(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        // Start a transaction
        \DB::beginTransaction();

        try {
            foreach ($this->items as $returnItem) {
                $item = $returnItem->item;
                $quantity = $returnItem->quantity;

                // Add back to warehouse inventory
                $stock = Stock::firstOrCreate(
                    [
                        'warehouse_id' => $this->warehouse_id,
                        'item_id' => $item->id,
                    ],
                    [
                        'quantity' => 0,
                        'reorder_level' => $item->reorder_level,
                    ]
                );

                $stock->quantity += $quantity;
                $stock->save();

                // Record stock history
                StockHistory::create([
                    'item_id' => $item->id,
                    'warehouse_id' => $this->warehouse_id,
                    'quantity_change' => $quantity,
                    'quantity_before' => $stock->quantity - $quantity,
                    'quantity_after' => $stock->quantity,
                    'reference_type' => 'return_' . $this->return_type,
                    'reference_id' => $this->id,
                    'user_id' => $this->user_id,
                    'description' => ucfirst($this->return_type) . ' return processed',
                ]);
            }

            // Update return status
            $this->status = 'completed';
            $this->save();

            // Update related sale or purchase if applicable
            if ($this->return_type === 'sale' && $this->sale) {
                // Handle customer credits or refunds here
                // This would depend on your business logic
            } elseif ($this->return_type === 'purchase' && $this->purchase) {
                // Handle supplier credits or refunds here
                // This would depend on your business logic
            }

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }
    }
}