<?php

namespace App\Livewire\Components;

use App\Models\Item;
use App\Models\Stock;
use Livewire\Component;

class ItemsSearchDropdown extends Component
{
    public $search = '';
    public $filteredItems = [];
    public $selected = null;
    public $showDropdown = false;
    public $placeholder = 'Search for an item...';
    public $minimumCharacters = 2;
    public $maxResults = 10;
    public $showQuantity = true;

    // Event that will be emitted when an item is selected
    public $emitUpEvent = 'itemSelected';

    protected $listeners = ['clearSelected'];

    public function mount($selected = null)
    {
        $this->selected = $selected;

        if ($selected) {
            $this->loadSelectedItem();
        }
    }

    public function loadSelectedItem()
    {
        if (!$this->selected) {
            return;
        }

        $item = Item::find($this->selected);
        if ($item) {
            $this->search = $item->name . ' - ' . $item->formatted_sku;
        }
    }

    public function updatedSearch()
    {
        $this->filterItems();
    }

    public function filterItems()
    {
        $this->filteredItems = [];

        if (strlen($this->search) < $this->minimumCharacters) {
            $this->showDropdown = false;
            return;
        }

        // Handle SKU search with and without prefix
        $search = $this->search;
        $prefix = config('app.sku_prefix', 'CODE-');
        $rawSearch = str_starts_with($search, $prefix) ? substr($search, strlen($prefix)) : $search;

        $query = Item::where('is_active', true)
            ->where(function($q) use ($search, $rawSearch) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $rawSearch . '%')
                  ->orWhere('barcode', 'like', '%' . $search . '%');
            });

        $items = $query->take($this->maxResults)->get();

        foreach ($items as $item) {
            $this->filteredItems[] = [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'formatted_sku' => $item->formatted_sku,
                'unit' => $item->unit,
                'cost_price' => $item->cost_price,
                'current_stock' => $item->current_stock ?? 0,
            ];
        }

        $this->showDropdown = count($this->filteredItems) > 0;
    }

    public function selectItem($itemId)
    {
        $this->selected = $itemId;

        // Find the selected item in the filtered list
        $selectedItem = collect($this->filteredItems)->firstWhere('id', $itemId);
        if ($selectedItem) {
            $this->search = $selectedItem['name'] . ' - ' . $selectedItem['formatted_sku'];
            // Dispatch the item data
            $this->dispatch($this->emitUpEvent, $selectedItem);
        }

        $this->showDropdown = false;
    }

    public function clearSelected()
    {
        $this->selected = null;
        $this->search = '';
        $this->filteredItems = [];
        $this->showDropdown = false;
    }

    public function render()
    {
        return view('livewire.components.items-search-dropdown');
    }
}