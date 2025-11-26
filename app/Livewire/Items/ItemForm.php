<?php

namespace App\Livewire\Items;

use App\Models\Category;
use App\Models\Item;
use App\Traits\HasFlashMessages;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class ItemForm extends Component
{
    use WithFileUploads, HasFlashMessages;

    public Item $item;
    public $isEdit = false;
    public $image;
    public $categories;

    protected $rules = [
        'item.name' => 'required|string|max:255',
        'item.sku' => 'required|string|max:50|unique:items,sku',
        'item.barcode' => 'nullable|string|max:50|unique:items,barcode',
        'item.category_id' => 'required|exists:categories,id',
        'item.cost_price' => 'required|numeric|min:0',
        'item.selling_price' => 'required|numeric|min:0',
        'item.reorder_level' => 'required|integer|min:0',
        'item.unit' => 'required|string|max:50',
        'item.brand' => 'nullable|string|max:255',
        'item.description' => 'nullable|string',
        'item.is_active' => 'boolean',
        'image' => 'nullable|image|max:2048',
    ];

    public function mount(Item $item = null)
    {
        $this->item = $item ?? new Item();
        $this->isEdit = $item ? true : false;
        $this->categories = Category::orderBy('name')->get();

        if (!$this->isEdit) {
            $this->item->is_active = true;
        }
    }

    public function updated($propertyName)
    {
        if ($this->isEdit) {
            $this->rules['item.sku'] = 'required|string|max:50|unique:items,sku,' . $this->item->id;
            $this->rules['item.barcode'] = 'nullable|string|max:50|unique:items,barcode,' . $this->item->id;
        }
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        // Apply unique rules for edit mode
        if ($this->isEdit) {
            $this->rules['item.sku'] = 'required|string|max:50|unique:items,sku,' . $this->item->id;
            $this->rules['item.barcode'] = 'nullable|string|max:50|unique:items,barcode,' . $this->item->id;
        }

        $this->validate();

        if ($this->image) {
            $imageName = Str::slug($this->item->name) . '-' . time() . '.' . $this->image->extension();
            $this->image->storeAs('public/items', $imageName);
            $this->item->image_path = 'items/' . $imageName;
        }

        $this->item->save();

        $this->flashCrudSuccess('item', $this->isEdit ? 'updated' : 'created');
        return redirect()->route('admin.items.index');
    }

    public function render()
    {
        return view('livewire.items.item-form');
    }
}
