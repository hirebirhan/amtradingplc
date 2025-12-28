<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Proforma extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_no',
        'customer_id',
        'warehouse_id',
        'branch_id',
        'user_id',
        'status',
        'total_amount',
        'discount',
        'tax',
        'shipping',
        'proforma_date',
        'valid_until',
        'notes',
        'terms_conditions',
        'contact_person',
        'contact_email',
        'contact_phone',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'proforma_date' => 'date',
        'valid_until' => 'date',
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProformaItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'cancelled');
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now());
    }

    // Business Logic
    public function canBeConverted(): bool
    {
        return in_array($this->status, ['draft', 'sent']) && 
               $this->valid_until >= now() &&
               $this->items()->count() > 0;
    }

    public function convertToSale(): Sale
    {
        if (!$this->canBeConverted()) {
            throw new \Exception('Proforma cannot be converted to sale');
        }

        return DB::transaction(function () {
            $sale = Sale::create([
                'reference_no' => $this->generateSaleReference(),
                'customer_id' => $this->customer_id,
                'warehouse_id' => $this->warehouse_id,
                'branch_id' => $this->branch_id,
                'user_id' => auth()->id(),
                'sale_date' => now(),
                'total_amount' => $this->total_amount,
                'payment_status' => 'due',
                'payment_method' => 'credit_full',
                'paid_amount' => 0,
                'due_amount' => $this->total_amount,
                'notes' => "Converted from Proforma: {$this->reference_no}",
            ]);

            foreach ($this->items as $proformaItem) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'item_id' => $proformaItem->item_id,
                    'quantity' => $proformaItem->quantity,
                    'unit_price' => $proformaItem->unit_price,
                    'subtotal' => $proformaItem->subtotal,
                ]);
            }

            $this->update(['status' => 'converted']);

            return $sale;
        });
    }

    private function generateSaleReference(): string
    {
        $prefix = 'SALE';
        $date = date('Ymd');
        $suffix = substr((string) \Illuminate\Support\Str::ulid(), -8);
        return "{$prefix}-{$date}-" . strtoupper($suffix);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-secondary',
            'sent' => 'bg-primary',
            'accepted' => 'bg-success',
            'rejected' => 'bg-danger',
            'converted' => 'bg-info',
            'cancelled' => 'bg-dark',
            default => 'bg-secondary'
        };
    }
}