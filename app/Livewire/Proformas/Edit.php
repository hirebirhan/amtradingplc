<?php

namespace App\Livewire\Proformas;

use App\Models\Proforma;
use App\Models\ProformaItem;
use App\Models\Customer;
use App\Models\Item;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Edit extends Component
{
    public Proforma $proforma;
    public $customer_id;
    public $notes = '';
    public $items = [];
    public $selectedItem;
    public $quantity = 1;
    public $unit_price;

    protected $rules = [
        'customer_id' => 'required|exists:customers,id',
        'items' => 'required|array|min:1',
        'items.*.item_id' => 'required|exists:items,id',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit_price' => 'required|numeric|min:0',
    ];

    public function mount(Proforma $proforma)
    {
        // Check if user can access this proforma (branch isolation)
        /** @var User $user */
        $user = Auth::user();
        if ($user->branch_id && $proforma->branch_id !== $user->branch_id) {
            abort(403, 'You can only edit proformas from your branch.');
        }
        
        $this->proforma = $proforma->load(['items.item']);
        $this->customer_id = $proforma->customer_id;
        $this->notes = $proforma->notes ?? '';
        $this->quantity = 1;
        
        $this->items = $proforma->items->map(function ($item) {
            return [
                'item_id' => $item->item_id,
                'item_name' => $item->item->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->subtotal,
            ];
        })->toArray();
    }

    public function addItem()
    {
        $this->validate([
            'selectedItem' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $item = Item::find($this->selectedItem);
        
        // Check if item already exists
        $existingIndex = collect($this->items)->search(function ($existingItem) use ($item) {
            return $existingItem['item_id'] == $item->id;
        });
        
        if ($existingIndex !== false) {
            session()->flash('error', 'Item already added to proforma.');
            return;
        }
        
        $this->items[] = [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total' => $this->quantity * $this->unit_price,
        ];

        $this->reset(['selectedItem', 'quantity', 'unit_price']);
        $this->quantity = 1;
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save()
    {
        $this->validate();

        $this->proforma->update([
            'customer_id' => $this->customer_id,
            'total_amount' => collect($this->items)->sum('total'),
            'notes' => $this->notes,
        ]);

        $this->proforma->items()->delete();

        foreach ($this->items as $item) {
            ProformaItem::create([
                'proforma_id' => $this->proforma->id,
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['total'],
            ]);
        }

        session()->flash('success', 'Proforma updated successfully.');
        return redirect()->route('admin.proformas.show', $this->proforma);
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        
        return view('livewire.proformas.edit', [
            'customers' => Customer::when($user->branch_id, fn($query) => $query->where('branch_id', $user->branch_id))
                                  ->select('id', 'name')
                                  ->orderBy('name')
                                  ->get(),
            'availableItems' => Item::select('id', 'name', 'selling_price')->get(),
        ]);
    }
}