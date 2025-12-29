<?php

namespace App\Livewire\Purchases;

use App\Models\Category;
use App\Models\Item;
use App\Enums\ItemUnit;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateItemModal extends Component
{
    public $form = [
        'name' => '',
        'category_id' => '',
        'description' => '',
        'unit' => 'piece',
        'unit_quantity' => 1,
        'item_unit' => '',
        'reorder_level' => null,
        'cost_price_per_unit' => '',
        'selling_price_per_unit' => '',
    ];

    public $isSubmitting = false;

    protected $listeners = ['openModal' => 'resetForm'];

    public function resetForm()
    {
        $this->form = [
            'name' => '',
            'category_id' => '',
            'description' => '',
            'unit' => 'piece',
            'unit_quantity' => 1,
            'item_unit' => '',
            'reorder_level' => null,
            'cost_price_per_unit' => '',
            'selling_price_per_unit' => '',
        ];
        $this->resetValidation();
    }

    public function save()
    {
        $this->isSubmitting = true;
        
        try {
            $validated = $this->validate([
                'form.name' => 'required|string|max:255|min:2',
                'form.category_id' => 'required|exists:categories,id',
                'form.description' => 'nullable|string|max:1000',
                'form.cost_price_per_unit' => 'required|numeric|min:0|max:999999.99',
                'form.selling_price_per_unit' => 'required|numeric|min:0|max:999999.99',
                'form.unit' => 'required|string|in:piece',
                'form.unit_quantity' => 'required|integer|min:1|max:99999',
                'form.item_unit' => 'required|string|in:' . implode(',', ItemUnit::values()),
                'form.reorder_level' => 'nullable|integer|min:0|max:99999',
            ]);

            DB::beginTransaction();

            $sku = $this->generateUniqueSku();
            $barcode = $this->generateUniqueBarcode();

            $costPrice = (float)$validated['form']['cost_price_per_unit'] * (int)$validated['form']['unit_quantity'];
            $sellingPrice = (float)$validated['form']['selling_price_per_unit'] * (int)$validated['form']['unit_quantity'];
            
            $itemData = array_merge($validated['form'], [
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'sku' => $sku,
                'barcode' => $barcode,
                'is_active' => true,
                'created_by' => Auth::id(),
                'branch_id' => Auth::user()->isSuperAdmin() ? null : Auth::user()->branch_id,
                'reorder_level' => $validated['form']['reorder_level'] ?? 1,
            ]);

            $item = Item::create($itemData);

            DB::commit();

            $this->dispatch('itemCreated', [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'cost_price_per_unit' => $validated['form']['cost_price_per_unit'],
                'unit_quantity' => $item->unit_quantity,
                'item_unit' => $item->item_unit,
            ]);

            $this->dispatch('closeModal');
            $this->resetForm();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->isSubmitting = false;
            $this->addError('general', 'Error creating item: ' . $e->getMessage());
        }
    }

    private function generateUniqueSku()
    {
        do {
            $sku = strtoupper(Str::random(6));
            $exists = Item::where('sku', $sku)->exists();
        } while ($exists);
        
        return $sku;
    }

    private function generateUniqueBarcode()
    {
        do {
            $barcode = date('Ymd') . strtoupper(Str::random(4));
            $exists = Item::where('barcode', $barcode)->exists();
        } while ($exists);
        
        return $barcode;
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
        
        return view('livewire.purchases.create-item-modal', [
            'categories' => $categories,
            'itemUnits' => $itemUnits,
        ]);
    }
}