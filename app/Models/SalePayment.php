<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_id',
        'amount',
        'payment_method',
        'reference_no',
        'payment_date',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Get the sale this payment is for.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the user who recorded this payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
