<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_no',
        'category',
        'expense_type_id',
        'amount',
        'note',
        'expense_date',
        'payment_method',
        'branch_id',
        'user_id',
        'attachment',
        'is_recurring',
        'recurring_frequency',
        'next_recurrence_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'is_recurring' => 'boolean',
        'next_recurrence_date' => 'date',
    ];

    /**
     * Get the user who created the expense.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch that the expense belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the expense type that the expense belongs to.
     */
    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }
}
