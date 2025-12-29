<?php

namespace App\Livewire\Items;

use App\Models\Category;
use App\Models\Item;
use App\Enums\ItemUnit;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Edit extends Component
{
    public Item $item;
    public $form = [];

    public function mount(Item $item)
    {
        $this->item = $item;
        $this->form = [
            'name' => $item->name,
            'category_id' => $item->category_id,
            'description' => $item->description,
            'cost_price_per_unit' => $item->cost_price_per_unit ?? ($item->unit_quantity > 0 ? round($item->cost_price / $item->unit_quantity, 2) : $item->cost_price),
            'selling_price_per_unit' => $item->selling_price_per_unit ?? ($item->unit_quantity > 0 ? round($item->selling_price / $item->unit_quantity, 2) : $item->selling_price),
            'unit' => $item->unit,
            'unit_quantity' => $item->unit_quantity ?? 1,
            'item_unit' => $item->item_unit ?? 'piece',
            'reorder_level' => $item->reorder_level,
        ];
    }

    public function updated($propertyName)
    {
        // Ensure unit quantity is at least 1
        if ($propertyName === 'form.unit_quantity') {
            if ((int)$this->form['unit_quantity'] < 1) {
                $this->form['unit_quantity'] = 1;
            }
        }

        // Validate selling price vs cost price
        if (in_array($propertyName, ['form.cost_price_per_unit', 'form.selling_price_per_unit'])) {
            $this->validateSellingPrice();
        }
    }

    /**
     * Validate that selling price is higher than cost price
     */
    protected function validateSellingPrice()
    {
        if (!empty($this->form['cost_price_per_unit']) && !empty($this->form['selling_price_per_unit'])) {
            if ((float)$this->form['selling_price_per_unit'] <= (float)$this->form['cost_price_per_unit']) {
                $this->addError('form.selling_price_per_unit', 'Selling price must be higher than cost price');
            } else {
                $this->resetErrorBag('form.selling_price_per_unit');
            }
        }
    }



    public function save()
    {
        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? null : $user->branch_id;
        
        $validated = $this->validate([
            'form.name' => [
                'required',
                'string', 
                'max:255',
                "unique:items,name,{$this->item->id},id,branch_id,{$branchId}"
            ],
            'form.category_id' => 'required|exists:categories,id',
            'form.description' => 'nullable|string',
            'form.cost_price_per_unit' => 'required|numeric|min:0|max:999999.99',
            'form.selling_price_per_unit' => 'required|numeric|min:0|max:999999.99',
            'form.unit' => 'required|string|in:piece',
            'form.unit_quantity' => 'required|integer|min:1|max:99999',
            'form.item_unit' => 'required|string|in:' . implode(',', ItemUnit::values()),
            'form.reorder_level' => 'nullable|integer|min:0|max:99999',
        ], [
            'form.name.required' => 'Item name is required.',
            'form.name.max' => 'Item name cannot exceed 255 characters.',
            'form.name.unique' => 'This item name already exists in your branch. Please choose a different name.',
            'form.category_id.required' => 'Please select a category.',
            'form.category_id.exists' => 'Selected category is invalid.',
            'form.cost_price_per_unit.required' => 'Cost price per unit is required.',
            'form.cost_price_per_unit.min' => 'Cost price per unit cannot be negative.',
            'form.cost_price_per_unit.max' => 'Cost price per unit is too high.',
            'form.selling_price_per_unit.required' => 'Selling price per unit is required.',
            'form.selling_price_per_unit.min' => 'Selling price per unit cannot be negative.',
            'form.selling_price_per_unit.max' => 'Selling price per unit is too high.',
            'form.unit_quantity.required' => 'Items per piece is required.',
            'form.unit_quantity.min' => 'Items per piece must be at least 1.',
            'form.unit_quantity.max' => 'Items per piece cannot exceed 99,999.',
            'form.item_unit.required' => 'Please select an item unit.',
            'form.item_unit.in' => 'Selected item unit is invalid.',
            'form.reorder_level.min' => 'Reorder level cannot be negative.',
            'form.reorder_level.max' => 'Reorder level cannot exceed 99,999.',
        ]);

        // Additional validation to ensure selling price is higher than cost price
        if ((float)$validated['form']['selling_price_per_unit'] <= (float)$validated['form']['cost_price_per_unit']) {
            $this->addError('form.selling_price_per_unit', 'Selling price must be higher than cost price.');
            return;
        }

        // Calculate total prices for backward compatibility
        $costPrice = (float)$validated['form']['cost_price_per_unit'] * (int)$validated['form']['unit_quantity'];
        $sellingPrice = (float)$validated['form']['selling_price_per_unit'] * (int)$validated['form']['unit_quantity'];
        
        $updateData = array_merge($validated['form'], [
            'cost_price' => $costPrice,
            'selling_price' => $sellingPrice,
        ]);

        $this->item->update($updateData);

        session()->flash('success', 'Item updated successfully.');
        return $this->redirect(route('admin.items.index'));
    }

    public function cancel()
    {
        return $this->redirect(route('admin.items.index'));
    }
    public function render()
    {
        $user = auth()->user();
        
        // Apply branch filtering to categories
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            $categories = Category::where('is_active', true)->orderBy('name')->get();
        } else {
            $categories = Category::forBranch($user->branch_id)->where('is_active', true)->orderBy('name')->get();
        }
        
        $itemUnits = collect(ItemUnit::cases())->mapWithKeys(function ($unit) {
            return [$unit->value => $unit->label()];
        });
        
        return view('livewire.items.edit', [
            'categories' => $categories,
            'itemUnits' => $itemUnits,
        ])->title('Edit Item - ' . $this->item->name);
    }
}
