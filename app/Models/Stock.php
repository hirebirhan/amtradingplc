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
        'piece_count',
        'total_units',
        'current_piece_units',
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
        'piece_count' => 'integer',
        'total_units' => 'decimal:2',
        'current_piece_units' => 'decimal:2',
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
     * Add pieces to stock (Purchase flow)
     */
    public function addPieces(int $pieces, float $unitCapacity, string $referenceType, ?int $referenceId = null, ?string $description = null, ?int $userId = null): void
    {
        $piecesBefore = $this->piece_count;
        $unitsBefore = $this->total_units;
        
        $this->piece_count += $pieces;
        $this->total_units += ($pieces * $unitCapacity);
        $this->quantity = $this->piece_count; // Stock level = piece count
        
        // Initialize current_piece_units if null or set to full capacity
        if ($this->current_piece_units === null || $this->piece_count === $pieces) {
            $this->current_piece_units = $unitCapacity;
        }
        
        $this->save();
        
        $this->createStockHistory($referenceType, $referenceId, $description, $userId, $piecesBefore, $unitsBefore);
    }
    
    /**
     * Sell by piece (deduct whole pieces)
     */
    public function sellByPiece(int $pieces, float $unitCapacity, string $referenceType, ?int $referenceId = null, ?string $description = null, ?int $userId = null): void
    {
        if ($this->piece_count < $pieces) {
            throw new \Exception("Insufficient pieces. Available: {$this->piece_count}, Requested: {$pieces}");
        }
        
        $unitsToDeduct = $pieces * $unitCapacity;
        if ($this->total_units < $unitsToDeduct) {
            throw new \Exception("Insufficient units. Available: {$this->total_units}, Required: {$unitsToDeduct}");
        }
        
        $piecesBefore = $this->piece_count;
        $unitsBefore = $this->total_units;
        
        $this->piece_count -= $pieces;
        $this->total_units -= $unitsToDeduct;
        $this->quantity = $this->piece_count; // Stock level = piece count
        
        // Reset current piece units to full capacity when selling whole pieces
        if ($this->piece_count > 0) {
            $this->current_piece_units = $unitCapacity;
        } else {
            $this->current_piece_units = 0;
        }
        
        $this->save();
        
        $this->createStockHistory($referenceType, $referenceId, $description, $userId, $piecesBefore, $unitsBefore);
    }
    
    /**
     * Sell by unit (deduct units, auto-adjust pieces)
     */
    public function sellByUnit(float $units, float $unitCapacity, string $referenceType, ?int $referenceId = null, ?string $description = null, ?int $userId = null): void
    {
        if ($this->total_units < $units) {
            throw new \Exception("Insufficient units. Available: {$this->total_units}, Requested: {$units}");
        }
        
        $piecesBefore = $this->piece_count;
        $unitsBefore = $this->total_units;
        
        // Initialize current_piece_units if null
        if ($this->current_piece_units === null) {
            $this->current_piece_units = $unitCapacity;
        }
        
        $remainingUnits = $units;
        
        // First, deduct from current piece units
        if ($remainingUnits <= $this->current_piece_units) {
            // Can fulfill from current piece without touching piece count
            $this->current_piece_units -= $remainingUnits;
            $remainingUnits = 0;
        } else {
            // Need to use multiple pieces
            $remainingUnits -= $this->current_piece_units; // Use all units from current piece
            $piecesToDeduct = 1; // Current piece is now empty
            
            // Calculate how many additional full pieces needed
            $additionalFullPieces = floor($remainingUnits / $unitCapacity);
            $piecesToDeduct += $additionalFullPieces;
            
            // Calculate remaining units for the next piece
            $unitsFromNextPiece = $remainingUnits % $unitCapacity;
            
            // Deduct pieces from stock
            $this->piece_count -= $piecesToDeduct;
            
            // Set current piece units for the new current piece
            if ($this->piece_count > 0 && $unitsFromNextPiece > 0) {
                $this->current_piece_units = $unitCapacity - $unitsFromNextPiece;
            } else {
                $this->current_piece_units = $unitCapacity;
            }
        }
        
        // Update total units and quantity
        $this->total_units -= $units;
        $this->quantity = $this->piece_count; // Stock level = piece count
        
        // Ensure consistency
        if ($this->piece_count === 0) {
            $this->total_units = 0;
            $this->current_piece_units = 0;
        }
        
        $this->save();
        
        $this->createStockHistory($referenceType, $referenceId, $description, $userId, $piecesBefore, $unitsBefore);
    }
    
    /**
     * Create stock history record
     */
    private function createStockHistory(string $referenceType, ?int $referenceId, ?string $description, ?int $userId, int $piecesBefore, float $unitsBefore): void
    {
        StockHistory::create([
            'warehouse_id' => $this->warehouse_id,
            'item_id' => $this->item_id,
            'quantity_before' => $piecesBefore,
            'quantity_after' => $this->piece_count,
            'quantity_change' => $this->piece_count - $piecesBefore,
            'units_before' => $unitsBefore,
            'units_after' => $this->total_units,
            'units_change' => $this->total_units - $unitsBefore,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'user_id' => $userId,
        ]);
    }
    
    /**
     * Legacy method for backward compatibility
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
