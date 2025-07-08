<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'credit_id',
        'amount',
        'payment_method',
        'reference_no',
        'receiver_bank_name',
        'receiver_account_holder',
        'receiver_account_number',
        'reference',
        'payment_date',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Get the credit this payment is for.
     */
    public function credit(): BelongsTo
    {
        return $this->belongsTo(Credit::class);
    }

    /**
     * Get the user who recorded this payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
