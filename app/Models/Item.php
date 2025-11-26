<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\PriceHistory;
use App\Models\Category;
use App\Models\StockReservation;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\PurchaseItem;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'category_id',
        'cost_price',
        'selling_price',
        'cost_price_per_unit',
        'selling_price_per_unit',
        'reorder_level',
        'unit',
        'unit_quantity',
        'item_unit',
        'brand',
        'description',
        'image_path',
        'is_active',
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
        'is_active' => 'boolean',
        'category_id' => 'integer',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'cost_price_per_unit' => 'decimal:2',
        'selling_price_per_unit' => 'decimal:2',
        'reorder_level' => 'integer',
        'unit_quantity' => 'integer',
    ];

    /**
     * Get the category that owns the item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all stocks for the item.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Get all stock histories for the item.
     */
    public function stockHistories(): HasMany
    {
        return $this->hasMany(StockHistory::class);
    }

    /**
     * Get all stock reservations for the item.
     */
    public function stockReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    /**
     * Get active (non-expired) stock reservations for the item.
     */
    public function activeReservations(): HasMany
    {
        return $this->hasMany(StockReservation::class)->active();
    }

    /**
     * Get the total quantity in stock across all warehouses (pieces).
     *
     * @return float
     */
    public function getTotalStockAttribute(): float
    {
        return $this->stocks()->sum('piece_count');
    }
    
    /**
     * Get the total pieces in stock across all warehouses.
     *
     * @return int
     */
    public function getTotalPiecesAttribute(): int
    {
        return $this->stocks()->sum('piece_count');
    }
    
    /**
     * Get the total units in stock across all warehouses.
     *
     * @return float
     */
    public function getTotalUnitsAttribute(): float
    {
        return $this->stocks()->sum('total_units');
    }

    /**
     * Get the total quantity in stock converted to individual items.
     * This accounts for unit_quantity (e.g., if 1 box = 3 items, and you have 2 boxes, you have 6 items)
     *
     * @return float
     */
    public function getTotalItemsAttribute(): float
    {
        return $this->getTotalStockAttribute() * $this->unit_quantity;
    }

    /**
     * Convert units to individual items.
     *
     * @param float $units
     * @return float
     */
    public function convertUnitsToItems(float $units): float
    {
        return $units * $this->unit_quantity;
    }

    /**
     * Convert individual items to units.
     *
     * @param float $items
     * @return float
     */
    public function convertItemsToUnits(float $items): float
    {
        return $items / $this->unit_quantity;
    }

    /**
     * Get cost price per individual item (e.g., per kg, per liter).
     *
     * @return float
     */
    public function getCostPricePerItemAttribute(): float
    {
        if ($this->cost_price_per_unit) {
            return $this->cost_price_per_unit;
        }
        
        // Fallback to calculated price if per-unit price not set
        return $this->unit_quantity > 0 ? $this->cost_price / $this->unit_quantity : 0;
    }

    /**
     * Get selling price per individual item (e.g., per kg, per liter).
     *
     * @return float
     */
    public function getSellingPricePerItemAttribute(): float
    {
        if ($this->selling_price_per_unit) {
            return $this->selling_price_per_unit;
        }
        
        // Fallback to calculated price if per-unit price not set
        return $this->unit_quantity > 0 ? $this->selling_price / $this->unit_quantity : 0;
    }

    /**
     * Calculate total cost price for a piece.
     *
     * @return float
     */
    public function getTotalCostPriceAttribute(): float
    {
        return $this->cost_price_per_unit * $this->unit_quantity;
    }

    /**
     * Calculate total selling price for a piece.
     *
     * @return float
     */
    public function getTotalSellingPriceAttribute(): float
    {
        return $this->selling_price_per_unit * $this->unit_quantity;
    }

    /**
     * Get the item unit label (e.g., "kg", "liter", "piece").
     *
     * @return string
     */
    public function getItemUnitLabelAttribute(): string
    {
        return $this->item_unit ?: 'piece';
    }

    /**
     * Get role-based stock quantity.
     * For superadmin/manager: shows all stock
     * For branch users: shows only branch stock (direct branch_id)
     * For warehouse users: shows only warehouse stock
     *
     * @param User|null $user
     * @return float
     */
    public function getRoleBasedStock(?User $user = null): float
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return $this->getTotalStockAttribute();
        }

        // Super admin or no role restrictions - show all stock
        if ($user->isSuperAdmin()) {
            return $this->getTotalStockAttribute();
        }

        // Warehouse user - show only assigned warehouse stock (highest priority)
        if ($user->warehouse_id) {
            return $this->getStockInWarehouse($user->warehouse_id);
        }

        // Branch-assigned users (Branch Managers, Sales, etc.) - show branch stock directly
        if ($user->branch_id) {
            return $this->getStockInBranch($user->branch_id);
        }

        // Default fallback - no specific assignment
        return 0;
    }

    /**
     * Check if the item is low on stock in any warehouse.
     *
     * @return bool
     */
    public function getLowStockAttribute(): bool
    {
        return $this->stocks()->where('quantity', '<', $this->reorder_level)->exists();
    }

    /**
     * Check if the item is low on stock (wrapper for getLowStockAttribute).
     *
     * @return bool
     */
    public function isLowStock(): bool
    {
        return $this->getLowStockAttribute();
    }

    /**
     * Check if the item is low on stock based on user role.
     *
     * @param User|null $user
     * @return bool
     */
    public function isLowStockForUser(?User $user = null): bool
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return $this->isLowStock();
        }

        $roleBasedStock = $this->getRoleBasedStock($user);
        return $roleBasedStock > 0 && $roleBasedStock <= $this->reorder_level;
    }

    /**
     * Get the stock quantity in a specific warehouse (pieces).
     *
     * @param int $warehouseId
     * @return float
     */
    public function getStockInWarehouse(int $warehouseId): float
    {
        $stock = $this->stocks()->where('warehouse_id', $warehouseId)->first();
        return $stock ? $stock->piece_count : 0;
    }
    
    /**
     * Get pieces in a specific warehouse.
     *
     * @param int $warehouseId
     * @return int
     */
    public function getPiecesInWarehouse(int $warehouseId): int
    {
        $stock = $this->stocks()->where('warehouse_id', $warehouseId)->first();
        return $stock ? $stock->piece_count : 0;
    }
    
    /**
     * Get units in a specific warehouse.
     *
     * @param int $warehouseId
     * @return float
     */
    public function getUnitsInWarehouse(int $warehouseId): float
    {
        $stock = $this->stocks()->where('warehouse_id', $warehouseId)->first();
        return $stock ? $stock->total_units : 0;
    }

    /**
     * Get stock quantities grouped by branch (via warehouse relationships).
     *
     * @return array
     */
    public function getStockByBranch(): array
    {
        $branchStock = [];
        
        // Get all branches and calculate stock for each
        $branches = \App\Models\Branch::with('warehouses')->get();
        
        foreach ($branches as $branch) {
            $totalStock = $this->stocks()
                ->whereIn('warehouse_id', $branch->warehouses->pluck('id'))
                ->sum('quantity');
                
            if ($totalStock > 0) {
                $branchStock[] = [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'quantity' => $totalStock
                ];
            }
        }
        
        return $branchStock;
    }

    /**
     * Get stock quantity for a specific branch (via warehouse relationships).
     *
     * @param int $branchId
     * @return float
     */
    public function getStockInBranch(int $branchId): float
    {
        return $this->stocks()
            ->whereHas('warehouse.branches', function($query) use ($branchId) {
                $query->where('branches.id', $branchId);
            })
            ->sum('piece_count');
    }
    
    /**
     * Get pieces for a specific branch.
     *
     * @param int $branchId
     * @return int
     */
    public function getPiecesInBranch(int $branchId): int
    {
        return $this->stocks()
            ->whereHas('warehouse.branches', function($query) use ($branchId) {
                $query->where('branches.id', $branchId);
            })
            ->sum('piece_count');
    }
    
    /**
     * Get units for a specific branch.
     *
     * @param int $branchId
     * @return float
     */
    public function getUnitsInBranch(int $branchId): float
    {
        return $this->stocks()
            ->whereHas('warehouse.branches', function($query) use ($branchId) {
                $query->where('branches.id', $branchId);
            })
            ->sum('total_units');
    }

    public function priceHistories()
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get all purchase items for this item.
     */
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Get all sale items for this item.
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get the total purchase amount for this item.
     * This calculates the sum of all purchase costs for this item.
     *
     * @return float
     */
    public function getTotalPurchaseAmountAttribute(): float
    {
        return $this->purchaseItems()->sum('subtotal') ?: 0;
    }

    /**
     * Get the total quantity purchased for this item.
     *
     * @return float
     */
    public function getTotalPurchaseQuantityAttribute(): float
    {
        return $this->purchaseItems()->sum('quantity') ?: 0;
    }

    /**
     * Get the total sales amount for this item.
     * This calculates the sum of all sales revenue for this item.
     *
     * @return float
     */
    public function getTotalSalesAmountAttribute(): float
    {
        return $this->saleItems()->sum('subtotal') ?: 0;
    }

    /**
     * Get the warehouses that have this item.
     */
    public function warehouses(): HasManyThrough
    {
        return $this->hasManyThrough(
            Warehouse::class,
            Stock::class,
            'item_id', // Foreign key on stocks table
            'id', // Foreign key on warehouses table
            'id', // Local key on items table
            'warehouse_id' // Local key on stocks table
        );
    }

    /**
     * Get the current warehouse for this item.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the SKU with prefix.
     * 
     * @return string
     */
    public function getFormattedSkuAttribute(): string
    {
        $prefix = config('app.sku_prefix', 'CODE-');
        
        // If SKU already starts with the prefix, don't add it again
        if (str_starts_with($this->sku, $prefix)) {
            return $this->sku;
        }
        
        return $prefix . $this->sku;
    }

    /**
     * Get the raw SKU without prefix for internal use.
     * 
     * @return string
     */
    public function getRawSkuAttribute(): string
    {
        $prefix = config('app.sku_prefix', 'CODE-');
        
        // Remove prefix if it exists
        if (str_starts_with($this->sku, $prefix)) {
            return substr($this->sku, strlen($prefix));
        }
        
        return $this->sku;
    }

    /**
     * Set the SKU attribute, automatically handling prefix.
     * 
     * @param string $value
     */
    public function setSkuAttribute($value): void
    {
        $prefix = config('app.sku_prefix', 'CODE-');
        
        // Remove prefix if it exists in the input
        if (str_starts_with($value, $prefix)) {
            $value = substr($value, strlen($prefix));
        }
        
        // Store without prefix in database
        $this->attributes['sku'] = $value;
    }

    /**
     * Get the user who created this item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this item.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this item.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
