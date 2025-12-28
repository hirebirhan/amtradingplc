<?php

namespace App\Livewire\Purchases\Traits;

use App\Models\Item;
use App\Models\Stock;
use App\Models\Branch;

trait HandlesItems
{
    public $itemOptions = [];
    public $selectedItem = null;
    public $current_stock = 0;
    public $editingItemIndex = null;
    public $itemSearch = '';

    public $newItem = [
        'item_id' => '',
        'quantity' => 1,
        'unit_cost' => 0,
        'cost' => 0,
        'notes' => '',
        'unit' => '',
    ];

    public function loadItems()
    {
        $this->itemOptions = Item::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'cost_price' => $item->cost_price ?? 0,
                    'cost_price_per_unit' => $item->cost_price_per_unit ?? 0,
                    'unit' => $item->unit ?? '',
                    'unit_quantity' => $item->unit_quantity ?? 1,
                    'item_unit' => $item->item_unit ?? 'piece',
                    'current_stock' => $this->getItemStock($item->id),
                ];
            });
    }

    public function loadItemOptions()
    {
        $this->loadItems();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Items refreshed successfully!'
        ]);
    }

    public function updatedNewItemItemId($value)
    {
        if (!empty($value)) {
            $item = Item::find($value);
            if ($item) {
                $costPerPiece = $item->cost_price ?? (($item->cost_price_per_unit ?? 0) * ($item->unit_quantity ?? 1));
                $costPerUnit = $item->cost_price_per_unit ?? ($costPerPiece / ($item->unit_quantity ?? 1));
                
                $this->newItem['unit_cost'] = $costPerUnit;
                $this->newItem['cost'] = $costPerPiece;
                $this->newItem['unit'] = $item->unit ?? '';
                $this->current_stock = $this->getItemStock($value);

                $this->selectedItem = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'unit' => $item->unit ?? 'pcs',
                    'unit_quantity' => $item->unit_quantity ?? 1,
                    'item_unit' => $item->item_unit ?? 'piece',
                    'cost_price' => $costPerPiece,
                    'cost_price_per_unit' => $costPerUnit,
                    'description' => $item->description,
                ];
            }
        } else {
            $this->selectedItem = null;
            $this->newItem['unit'] = '';
            $this->newItem['unit_cost'] = 0;
            $this->newItem['cost'] = 0;
            $this->current_stock = 0;
        }
    }

    private function getItemStock($itemId)
    {
        if (empty($itemId)) {
            return 0;
        }
        
        if (!empty($this->form['branch_id'])) {
            $branch = Branch::with('warehouses')->find($this->form['branch_id']);
            if ($branch && $branch->warehouses->isNotEmpty()) {
                return Stock::whereIn('warehouse_id', $branch->warehouses->pluck('id'))
                    ->where('item_id', $itemId)
                    ->sum('quantity');
            }
        }
        return 0;
    }

    public function addItem()
    {
        try {
            if (empty($this->newItem['item_id'])) {
                $this->notify('❌ Please select an item first', 'error');
                return false;
            }

            $this->validate([
                'newItem.item_id' => 'required|exists:items,id',
                'newItem.quantity' => 'required|numeric|min:0.01',
                'newItem.cost' => 'required|numeric|min:0.01',
            ], [
                'newItem.item_id.required' => 'Please select an item',
                'newItem.item_id.exists' => 'Selected item is not valid',
                'newItem.quantity.required' => 'Please enter quantity',
                'newItem.quantity.min' => 'Quantity must be greater than zero',
                'newItem.cost.required' => 'Please enter cost price',
                'newItem.cost.min' => 'Cost must be greater than zero',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = collect($e->validator->errors()->all());
            foreach ($errors as $error) {
                $this->notify('❌ ' . $error, 'error');
            }
            return false;
        }

        return $this->processAddItem();
    }

    public function removeItem($index)
    {
        if (!isset($this->items[$index])) {
            $this->notify('❌ Item not found', 'error');
            return;
        }

        $removedItem = $this->items[$index];
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        
        $this->updateTotals();
        $this->loadItems();
        
        $this->notify("✓ {$removedItem['name']} removed from cart", 'success');
    }

    private function processAddItem()
    {
        $item = Item::find($this->newItem['item_id']);
        if (!$item) {
            $this->notify('❌ Item not found in database', 'error');
            return false;
        }
        
        $cost = round(floatval($this->newItem['cost']), 2);
        $quantity = floatval($this->newItem['quantity']);
        $subtotal = $cost * $quantity;

        $this->items[] = [
            'item_id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'unit' => $item->unit ?? '',
            'unit_quantity' => $item->unit_quantity ?? 1,
            'item_unit' => $item->item_unit ?? 'piece',
            'quantity' => $quantity,
            'cost' => $cost,
            'unit_cost' => $this->newItem['unit_cost'] ?? ($cost / ($item->unit_quantity ?? 1)),
            'subtotal' => $subtotal,
            'notes' => $this->newItem['notes'] ?? null,
        ];

        $this->updateTotals();
        $this->resetItemFields();
        
        return true;
    }

    private function resetItemFields()
    {
        $this->newItem = [
            'item_id' => '',
            'quantity' => 1,
            'unit_cost' => 0,
            'cost' => 0,
            'notes' => '',
            'unit' => '',
        ];
        
        $this->selectedItem = null;
        $this->itemSearch = '';
        $this->current_stock = 0;
        
        $this->resetValidation([
            'newItem.item_id',
            'newItem.quantity', 
            'newItem.cost',
            'newItem.notes'
        ]);
        
        $this->loadItems();
        $this->dispatch('itemFormReset');
        
        if (count($this->items) > 0) {
            $lastItem = end($this->items);
            $this->notify("✓ {$lastItem['name']} added to purchase", 'success');
        }
    }

    public function selectItem($itemId)
    {
        if (empty($itemId)) {
            return;
        }

        $item = Item::find($itemId);
        if (!$item) {
            $this->notify('❌ Item not found', 'error');
            return;
        }

        $this->newItem['item_id'] = $item->id;
        $this->updatedNewItemItemId($item->id);
        $this->itemSearch = '';
    }

    public function getFilteredItemOptionsProperty()
    {
        if (empty($this->itemSearch) || strlen(trim($this->itemSearch)) < 2) {
            return [];
        }
        
        $search = strtolower(trim($this->itemSearch));
        
        return Item::where('is_active', true)
            ->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%'])
                      ->orWhereRaw('LOWER(sku) LIKE ?', ['%' . $search . '%'])
                      ->orWhereRaw('LOWER(barcode) LIKE ?', ['%' . $search . '%']);
            })
            ->orderBy('name')
            ->take(15)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'cost_price' => $item->cost_price ?? 0,
                    'cost_price_per_unit' => $item->cost_price_per_unit ?? 0,
                    'unit' => $item->unit ?? '',
                    'unit_quantity' => $item->unit_quantity ?? 1,
                    'item_unit' => $item->item_unit ?? 'piece',
                    'current_stock' => $this->getItemStock($item->id),
                ];
            })
            ->toArray();
    }
}