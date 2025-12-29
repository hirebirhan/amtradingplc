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
        $user = auth()->user();
        $query = Item::where('is_active', true);
        
        // Apply branch filtering for non-admin users
        if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
            if ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }
        }
        
        $this->itemOptions = $query->orderBy('name')
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

    public function updatedNewItemUnitCost($value)
    {
        if ($this->selectedItem && !empty($value)) {
            $unitQuantity = $this->selectedItem['unit_quantity'] ?? 1;
            $this->newItem['cost'] = round(floatval($value) * $unitQuantity, 2);
        }
    }

    public function updatedNewItemCost($value)
    {
        if ($this->selectedItem && !empty($value)) {
            $unitQuantity = $this->selectedItem['unit_quantity'] ?? 1;
            $this->newItem['unit_cost'] = round(floatval($value) / $unitQuantity, 2);
        }
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

    public function clearSelectedItem()
    {
        $this->selectedItem = null;
        $this->newItem['item_id'] = '';
        $this->newItem['unit'] = '';
        $this->newItem['unit_cost'] = 0;
        $this->newItem['cost'] = 0;
        $this->current_stock = 0;
        $this->itemSearch = '';
    }

    public function handleItemCreated($itemData)
    {
        $this->loadItems();
        
        if (isset($itemData['id'])) {
            $this->newItem['item_id'] = $itemData['id'];
            $this->updatedNewItemItemId($itemData['id']);
            $this->notify('✓ Item created and selected successfully!', 'success');
        }
    }

    public function editItem($index)
    {
        if (!isset($this->items[$index])) {
            $this->notify('❌ Item not found', 'error');
            return;
        }

        $item = $this->items[$index];
        
        $this->newItem = [
            'item_id' => $item['item_id'],
            'quantity' => $item['quantity'],
            'unit_cost' => $item['unit_cost'],
            'cost' => $item['cost'],
            'notes' => $item['notes'] ?? '',
            'unit' => $item['unit'],
        ];
        
        $this->selectedItem = [
            'id' => $item['item_id'],
            'name' => $item['name'],
            'sku' => $item['sku'],
            'unit' => $item['unit'],
            'unit_quantity' => $item['unit_quantity'],
            'item_unit' => $item['item_unit'],
        ];
        
        $this->editingItemIndex = $index;
    }

    public function updateExistingItem()
    {
        if ($this->editingItemIndex === null) {
            return;
        }

        try {
            $this->validate([
                'newItem.quantity' => 'required|numeric|min:0.01',
                'newItem.cost' => 'required|numeric|min:0.01',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->validator->errors()->all() as $error) {
                $this->notify('❌ ' . $error, 'error');
            }
            return;
        }

        $this->items[$this->editingItemIndex]['quantity'] = floatval($this->newItem['quantity']);
        $this->items[$this->editingItemIndex]['cost'] = round(floatval($this->newItem['cost']), 2);
        $this->items[$this->editingItemIndex]['unit_cost'] = $this->newItem['unit_cost'];
        $this->items[$this->editingItemIndex]['subtotal'] = $this->items[$this->editingItemIndex]['quantity'] * $this->items[$this->editingItemIndex]['cost'];
        $this->items[$this->editingItemIndex]['notes'] = $this->newItem['notes'];

        $this->updateTotals();
        $this->cancelEdit();
        
        $this->notify('✓ Item updated successfully', 'success');
    }

    public function cancelEdit()
    {
        $this->editingItemIndex = null;
        $this->resetItemFields();
    }

    public function getFilteredItemOptionsProperty()
    {
        if (empty($this->itemSearch) || strlen(trim($this->itemSearch)) < 2) {
            return [];
        }
        
        $search = strtolower(trim($this->itemSearch));
        $user = auth()->user();
        $query = Item::where('is_active', true);
        
        // Apply branch filtering for non-admin users
        if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
            if ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }
        }
        
        return $query->where(function ($query) use ($search) {
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