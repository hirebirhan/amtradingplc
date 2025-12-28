<?php

namespace App\Traits;

use App\Models\Item;
use App\Models\Stock;

trait HasItemSelection
{
    // Item selection properties
    public $itemOptions = [];
    public $itemSearch = '';
    public $selectedItem = null;
    public $availableStock = 0;
    public $editingItemIndex = null;

    /**
     * Load available items for the current location
     */
    public function loadAvailableItems()
    {
        $this->loadItemsForLocation();
    }

    /**
     * Handle item search updates
     */
    public function updatedItemSearch()
    {
        // This will trigger the computed property to update
    }

    /**
     * Get item stock for a specific item and warehouse
     */
    protected function getItemStock($itemId, $warehouseId = null)
    {
        $warehouseId = $warehouseId ?: $this->form['warehouse_id'] ?? null;
        
        if (!$warehouseId) {
            return 0;
        }

        $stock = Stock::where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $stock ? max($stock->quantity ?? 0, $stock->piece_count ?? 0) : 0;
    }

    protected function loadItemsForLocation()
    {
        // Items are loaded dynamically via computed property
    }
}