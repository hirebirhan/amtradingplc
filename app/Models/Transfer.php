<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\StockHistory;
use App\Models\User;

class Transfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
'reference_code',
        'source_type',
        'source_id',
        'destination_type', 
        'destination_id',
        'date_initiated',
        'note',
        'status',
        'user_id',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date_initiated' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transfer) {
            if (empty($transfer->reference_code)) {
                $transfer->reference_code = $transfer->generateReferenceCode();
            }
            if (empty($transfer->date_initiated)) {
                $transfer->date_initiated = now();
            }
            // Branch-only default types
            if (empty($transfer->source_type)) {
                $transfer->source_type = 'branch';
            }
            if (empty($transfer->destination_type)) {
                $transfer->destination_type = 'branch';
            }
            if (empty($transfer->status)) {
                $transfer->status = 'pending';
            }
        });

        // Removed auto-processing from boot method to handle it manually in the controller
    }

    /**
     * Generate a unique reference code for the transfer.
     */
    private function generateReferenceCode(): string
    {
        do {
            $code = 'TRF-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (self::where('reference_code', $code)->exists());

        return $code;
    }

    /**
     * Get the user that created the transfer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the transfer.
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransferItem::class);
    }

    /**
     * Get the stock reservations for this transfer.
     */
    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class, 'reference_id')
            ->where('reference_type', 'transfer');
    }

    /**
     * Get the source location (warehouse or branch).
     */
    public function sourceLocation(): BelongsTo
    {
        if ($this->source_type === 'warehouse') {
            return $this->belongsTo(Warehouse::class, 'source_id');
        }
        return $this->belongsTo(Branch::class, 'source_id');
    }

    /**
     * Get the destination location (warehouse or branch).
     */
    public function destinationLocation(): BelongsTo
    {
        if ($this->destination_type === 'warehouse') {
            return $this->belongsTo(Warehouse::class, 'destination_id');
        }
        return $this->belongsTo(Branch::class, 'destination_id');
    }

    /**
     * Get the source warehouse (if source is warehouse).
     */
    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_id');
    }

    /**
     * Get the destination warehouse (if destination is warehouse).
     */
    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_id');
    }

    /**
     * Get the source branch (if source is branch).
     */
    public function sourceBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'source_id');
    }

    /**
     * Get the destination branch (if destination is branch).
     */
    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_id');
        }

    /**
     * Get the source location name.
     */
    public function getSourceLocationNameAttribute(): string
    {
        if ($this->source_type === 'warehouse' && $this->sourceWarehouse) {
            return $this->sourceWarehouse->name;
        }
        if ($this->source_type === 'branch' && $this->sourceBranch) {
            return $this->sourceBranch->name;
        }
        return 'Unknown Location';
    }

    /**
     * Get the destination location name.
     */
    public function getDestinationLocationNameAttribute(): string
    {
        if ($this->destination_type === 'warehouse' && $this->destinationWarehouse) {
            return $this->destinationWarehouse->name;
        }
        if ($this->destination_type === 'branch' && $this->destinationBranch) {
            return $this->destinationBranch->name;
        }
        return 'Unknown Location';
    }

    /**
     * Get transfer type display name.
     */
    public function getTransferTypeDisplayAttribute(): string
    {
        return ucfirst($this->source_type) . ' → ' . ucfirst($this->destination_type);
    }

    /**
     * Process the transfer: remove stock from source, add to destination.
     * Note: This method is deprecated. Use StockMovementService instead.
     * 
     * @deprecated Use TransferService::processTransferWorkflow() instead
     */
    public function processTransfer(): bool
    {
        throw new \Exception('This method is deprecated. Use TransferService::processTransferWorkflow() instead.');
    }

    // Note: Stock management methods have been moved to StockMovementService
    // for better separation of concerns and improved reliability.

    /**
     * Scope transfers for a specific user based on their permissions.
     */
    public function scopeForUser($query, User $user)
    {
        return match (\App\Enums\AuthorizationLevel::fromUser($user)) {
            \App\Enums\AuthorizationLevel::FULL_ACCESS => $query,
            \App\Enums\AuthorizationLevel::BRANCH_RESTRICTED => $query->where(function ($q) use ($user) {
                $q->where(function ($sq) use ($user) {
                    $sq->where('source_type', 'branch')->where('source_id', $user->branch_id);
                })->orWhere(function ($sq) use ($user) {
                    $sq->where('destination_type', 'branch')->where('destination_id', $user->branch_id);
                });
            }),
            \App\Enums\AuthorizationLevel::NO_ACCESS => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Check if user can create transfers from specific location.
     */
    public function canUserCreateFrom(User $user, string $locationType, int $locationId): bool
    {
        return match (\App\Enums\AuthorizationLevel::fromUser($user)) {
            \App\Enums\AuthorizationLevel::FULL_ACCESS => true,
            \App\Enums\AuthorizationLevel::BRANCH_RESTRICTED => $locationType === 'branch' && $user->branch_id === $locationId,
            \App\Enums\AuthorizationLevel::NO_ACCESS => false,
        };
    }

    /**
     * Alias for backward compatibility: fromWarehouse (source warehouse)
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->sourceWarehouse();
    }

    /**
     * Alias for backward compatibility: toWarehouse (destination warehouse)
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->destinationWarehouse();
    }

    /**
     * Alias for backward compatibility: fromBranch (source branch)
     */
    public function fromBranch(): BelongsTo
    {
        return $this->sourceBranch();
    }

    /**
     * Alias for backward compatibility: toBranch (destination branch)
     */
    public function toBranch(): BelongsTo
    {
        return $this->destinationBranch();
    }

    /**
     * User that approved this transfer.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Determine if a user can approve / process this transfer.
     * Only managers of destination branch can approve.
     */
    public function canUserApprove(User $user): bool
    {
        return match (\App\Enums\AuthorizationLevel::fromUser($user)) {
            \App\Enums\AuthorizationLevel::FULL_ACCESS => true,
            \App\Enums\AuthorizationLevel::BRANCH_RESTRICTED => $this->destination_type === 'branch' && $user->branch_id === $this->destination_id,
            \App\Enums\AuthorizationLevel::NO_ACCESS => false,
        };
    }

    /**
     * Approve a pending transfer.
     */
    public function approve(User $user): void
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Only pending transfers can be approved.');
        }

        $this->update([
            'status'       => 'approved',
            'approved_by'  => $user->id,
            'approved_at'  => now(),
        ]);
    }

    /**
     * Reject a pending transfer.
     */
    public function reject(User $user): void
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Only pending transfers can be rejected.');
        }
        $this->update([
            'status'       => 'rejected',
            'approved_by'  => $user->id,
            'approved_at'  => now(),
        ]);
    }

    /**
     * Cancel a transfer (by creator or approver).
     */
    public function cancel(User $user): void
    {
        if (!in_array($this->status, ['pending', 'approved'])) {
            throw new \Exception('Only pending or approved transfers can be cancelled.');
        }
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Process an approved / in-transit transfer and move stock.
     * 
     * @deprecated Use TransferService::processTransferWorkflow() instead
     */
    public function process(): void
    {
        throw new \Exception('This method is deprecated. Use TransferService::processTransferWorkflow() instead.');
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
