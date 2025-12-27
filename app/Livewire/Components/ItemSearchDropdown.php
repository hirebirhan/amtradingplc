<?php

namespace App\Livewire\Components;

use App\Models\Item;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ItemSearchDropdown extends Component
{
    public $searchTerm = '';
    public $selectedItem = null;
    public $selectedItemId = null;
    public $warehouseId = null;
    public $context = 'purchase'; // 'purchase' or 'sale'
    public $placeholder = 'Search items by name or SKU...';
    public $showAvailableStock = true;
    public $minSearchLength = 2;
    public $maxResults = 15;
    
    // Events
    public $itemSelectedEvent = 'itemSelected';
    
    // Internal state
    public $isOpen = false;
    public $searchResults = [];
    public $highlightedIndex = -1;
    
    protected $listeners = ['clearSelection' => 'clearSelection'];

    public function mount($warehouseId = null, $context = 'purchase', $placeholder = null, $showAvailableStock = true)
    {
        $this->warehouseId = $warehouseId;
        $this->context = $context;
        $this->showAvailableStock = $showAvailableStock;
        
        if ($placeholder) {
            $this->placeholder = $placeholder;
        }
    }

    public function updatedSearchTerm()
    {
        $this->highlightedIndex = -1;
        
        if (strlen($this->searchTerm) >= $this->minSearchLength) {
            $this->searchItems();
            $this->isOpen = true;
        } else {
            $this->searchResults = [];
            $this->isOpen = false;
        }
    }

    public function searchItems()
    {
        $user = Auth::user();
        $searchTerm = trim($this->searchTerm);
        
        if (strlen($searchTerm) < $this->minSearchLength) {
            $this->searchResults = [];
            return;
        }

        $query = Item::select([
            'id', 'name', 'sku', 'barcode', 'branch_id',
            'cost_price', 'selling_price', 'cost_price_per_unit', 
            'selling_price_per_unit', 'unit_quantity', 'item_unit'
        ])
        ->where('is_active', true)
        ->where(function ($q) use ($searchTerm) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhereRaw('LOWER(sku) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
              ->orWhereRaw('LOWER(barcode) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
        });

        // Apply branch-level filtering
        $query = $this->applyBranchFiltering($query, $user);

        // For sales context, filter items with available stock
        if ($this->context === 'sale' && $this->warehouseId) {
            $query = $this->filterByAvailableStock($query);
        }

        $items = $query->orderBy('name')
            ->limit($this->maxResults)
            ->get();

        // Load stock information if needed
        if ($this->showAvailableStock && $this->warehouseId) {
            $items->load(['stocks' => function ($q) {
                $q->where('warehouse_id', $this->warehouseId);
            }]);
        }

        $this->searchResults = $items->map(function ($item) {
            return $this->formatItemForDisplay($item);
        })->toArray();
    }

    private function applyBranchFiltering($query, User $user)
    {
        // SuperAdmin and GeneralManager see all items
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return $query;
        }

        // Branch users see only their branch items + global items (null branch_id)
        if ($user->branch_id) {
            return $query->where(function($q) use ($user) {
                $q->where('branch_id', $user->branch_id)
                  ->orWhereNull('branch_id');
            });
        }

        return $query;
    }

    private function filterByAvailableStock($query)
    {
        return $query->whereHas('stocks', function ($q) {
            $q->where('warehouse_id', $this->warehouseId)
              ->where('piece_count', '>', 0);
        });
    }

    private function formatItemForDisplay($item)
    {
        $availableStock = 0;
        $isLowStock = false;

        if ($this->showAvailableStock && $this->warehouseId) {
            $stock = $item->stocks->first();
            $availableStock = $stock ? $stock->piece_count : 0;
            $isLowStock = $availableStock > 0 && $availableStock <= $item->reorder_level;
        }

        return [
            'id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'barcode' => $item->barcode,
            'cost_price' => $item->cost_price,
            'selling_price' => $item->selling_price,
            'cost_price_per_unit' => $item->cost_price_per_unit,
            'selling_price_per_unit' => $item->selling_price_per_unit,
            'unit_quantity' => $item->unit_quantity,
            'item_unit' => $item->item_unit,
            'available_stock' => $availableStock,
            'is_low_stock' => $isLowStock,
            'display_text' => $this->getDisplayText($item, $availableStock, $isLowStock),
        ];
    }

    private function getDisplayText($item, $availableStock, $isLowStock)
    {
        $text = $item->name . ' (' . $item->sku . ')';
        
        if ($this->showAvailableStock && $this->warehouseId) {
            $text .= ' - Available: ' . number_format($availableStock, 2);
            if ($isLowStock) {
                $text .= ' ⚠️';
            }
        }
        
        return $text;
    }

    public function selectItem($itemId)
    {
        $item = collect($this->searchResults)->firstWhere('id', $itemId);
        
        if ($item) {
            $this->selectedItem = $item;
            $this->selectedItemId = $itemId;
            $this->searchTerm = $item['display_text'];
            $this->isOpen = false;
            
            // Dispatch event to parent component
            $this->dispatch($this->itemSelectedEvent, $item);
        }
    }

    public function clearSelection()
    {
        $this->selectedItem = null;
        $this->selectedItemId = null;
        $this->searchTerm = '';
        $this->searchResults = [];
        $this->isOpen = false;
        $this->highlightedIndex = -1;
    }

    public function closeDropdown()
    {
        $this->isOpen = false;
        $this->highlightedIndex = -1;
    }

    public function handleKeydown($key)
    {
        switch ($key) {
            case 'ArrowDown':
                $this->highlightedIndex = min($this->highlightedIndex + 1, count($this->searchResults) - 1);
                break;
            case 'ArrowUp':
                $this->highlightedIndex = max($this->highlightedIndex - 1, -1);
                break;
            case 'Enter':
                if ($this->highlightedIndex >= 0 && isset($this->searchResults[$this->highlightedIndex])) {
                    $this->selectItem($this->searchResults[$this->highlightedIndex]['id']);
                }
                break;
            case 'Escape':
                $this->closeDropdown();
                break;
        }
    }

    public function render()
    {
        return view('livewire.components.item-search-dropdown');
    }
}