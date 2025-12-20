<?php

namespace App\Livewire\Components;

use App\Services\ItemSearchService;
use Livewire\Component;
use Livewire\Attributes\On;

class ItemSearchDropdown extends Component
{
    public string $search = '';
    public bool $isOpen = false;
    public int $selectedIndex = -1;
    
    public string $context = 'purchase';
    public ?int $warehouseId = null;
    public string $placeholder = 'Search items...';
    public bool $showStock = true;
    public bool $showPrices = true;

    private ItemSearchService $itemSearchService;

    public function boot(ItemSearchService $itemSearchService)
    {
        $this->itemSearchService = $itemSearchService;
    }

    public function updatedSearch()
    {
        $this->selectedIndex = -1;
        $this->isOpen = strlen(trim($this->search)) >= 2;
    }

    public function getSearchResultsProperty()
    {
        return $this->itemSearchService->search(
            $this->search,
            $this->context,
            $this->warehouseId
        );
    }

    public function selectItem($itemId)
    {
        $item = $this->itemSearchService->getItemWithStock($itemId, $this->warehouseId);
        
        if (!$item) {
            return;
        }

        $stockWarning = false;
        if ($this->context === 'sale' && $this->warehouseId) {
            $stock = $item->stocks->first();
            if (!$stock || $stock->available_quantity <= 0) {
                $stockWarning = true;
            }
        }

        $this->dispatch('item-selected', [
            'item' => $item->toArray(),
            'context' => $this->context,
            'stock' => $this->context === 'sale' && $this->warehouseId 
                ? $item->stocks->first()?->available_quantity ?? 0 
                : null,
            'stock_warning' => $stockWarning
        ]);

        $this->reset(['search', 'isOpen', 'selectedIndex']);
    }

    public function keyDown($key)
    {
        $results = $this->searchResults;
        
        if ($key === 'ArrowDown') {
            $this->selectedIndex = min($this->selectedIndex + 1, $results->count() - 1);
        } elseif ($key === 'ArrowUp') {
            $this->selectedIndex = max($this->selectedIndex - 1, -1);
        } elseif ($key === 'Enter' && $this->selectedIndex >= 0) {
            $item = $results->get($this->selectedIndex);
            if ($item) {
                $this->selectItem($item->id);
            }
        } elseif ($key === 'Escape') {
            $this->reset(['search', 'isOpen', 'selectedIndex']);
        }
    }

    public function render()
    {
        return view('livewire.components.item-search-dropdown');
    }
}