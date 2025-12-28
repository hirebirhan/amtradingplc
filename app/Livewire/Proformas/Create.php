<?php

namespace App\Livewire\Proformas;

use App\Models\Proforma;
use App\Models\ProformaItem;
use App\Models\Customer;
use App\Models\Item;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class Create extends Component
{
    public $customer_id;
    public $proforma_date;
    public $valid_until;
    public $contact_person = '';
    public $contact_email = '';
    public $contact_phone = '';
    public $notes = '';
    public $items = [];
    public $selectedItem;
    public $quantity = 1;
    public $unit_price;

    protected $rules = [
        'customer_id' => 'required|exists:customers,id',
        'proforma_date' => 'required|date',
        'items' => 'required|array|min:1',
        'items.*.item_id' => 'required|exists:items,id',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit_price' => 'required|numeric|min:0',
    ];

    public function mount()
    {
        $this->proforma_date = date('Y-m-d');
    }

    public function addItem()
    {
        $this->validateOnly('selectedItem', [
            'selectedItem' => 'required|exists:items,id',
        ]);
        
        $this->validateOnly('quantity', [
            'quantity' => 'required|numeric|min:0.01',
        ]);
        
        $this->validateOnly('unit_price', [
            'unit_price' => 'required|numeric|min:0',
        ]);

        $item = Item::find($this->selectedItem);
        
        // Check if item already exists
        $existingIndex = collect($this->items)->search(function ($existingItem) use ($item) {
            return $existingItem['item_id'] == $item->id;
        });
        
        if ($existingIndex !== false) {
            $this->addError('selectedItem', 'Item already added to proforma.');
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
        $this->validateOnly('customer_id', [
            'customer_id' => 'required|exists:customers,id',
        ]);
        
        $this->validateOnly('proforma_date', [
            'proforma_date' => 'required|date',
        ]);
        
        if (empty($this->items)) {
            $this->addError('items', 'Please add at least one item to the proforma.');
            return;
        }

        /** @var User $user */
        $user = Auth::user();
        
        $proforma = Proforma::create([
            'reference_no' => 'PRO-' . strtoupper(Str::random(8)),
            'customer_id' => $this->customer_id,
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'warehouse_id' => $user->branch?->warehouses()->first()?->id ?? 1,
            'proforma_date' => $this->proforma_date,
            'valid_until' => $this->valid_until,
            'contact_person' => $this->contact_person,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'total_amount' => $this->subtotal,
            'notes' => $this->notes,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        foreach ($this->items as $item) {
            ProformaItem::create([
                'proforma_id' => $proforma->id,
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['total'],
            ]);
        }

        session()->flash('success', 'Proforma created successfully.');
        return redirect()->route('admin.proformas.show', $proforma);
    }

    public function getSubtotalProperty()
    {
        return collect($this->items)->sum('total');
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        
        return view('livewire.proformas.create', [
            'customers' => Customer::when($user->branch_id, fn($query) => $query->where('branch_id', $user->branch_id))
                                  ->select('id', 'name')
                                  ->orderBy('name')
                                  ->get(),
            'availableItems' => Item::select('id', 'name', 'selling_price')->get(),
            'subtotal' => $this->subtotal,
        ]);
    }
}