<?php

namespace App\Livewire\Components;

use App\Models\Supplier;
use Livewire\Component;

class SuppliersSearchDropdown extends Component
{
    public $search = '';
    public $filteredSuppliers = [];
    public $selected = null;
    public $showDropdown = false;
    public $placeholder = 'Search for a supplier...';
    public $minimumCharacters = 2;
    public $maxResults = 10;

    // Event that will be emitted when a supplier is selected
    public $emitUpEvent = 'supplierSelected';

    protected $listeners = ['clearSelected'];

    public function mount($selected = null)
    {
        $this->selected = $selected;

        if ($selected) {
            $this->loadSelectedSupplier();
        }
    }

    public function loadSelectedSupplier()
    {
        if (!$this->selected) {
            return;
        }

        $supplier = Supplier::find($this->selected);
        if ($supplier) {
            $this->search = $supplier->name;
        }
    }

    public function updatedSearch()
    {
        $this->filterSuppliers();
    }

    public function filterSuppliers()
    {
        $this->filteredSuppliers = [];

        if (strlen($this->search) < $this->minimumCharacters) {
            $this->showDropdown = false;
            return;
        }

        $query = Supplier::where('is_active', true)
            ->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });

        $suppliers = $query->take($this->maxResults)->get();

        foreach ($suppliers as $supplier) {
            $this->filteredSuppliers[] = [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
            ];
        }

        $this->showDropdown = count($this->filteredSuppliers) > 0;
    }

    public function selectSupplier($supplierId)
    {
        $this->selected = $supplierId;

        // Find the selected supplier in the filtered list
        $selectedSupplier = collect($this->filteredSuppliers)->firstWhere('id', $supplierId);
        if ($selectedSupplier) {
            $this->search = $selectedSupplier['name'];
            // Only dispatch the ID, not the entire supplier object
            $this->dispatch($this->emitUpEvent, $supplierId);
        }

        $this->showDropdown = false;
    }

    public function clearSelected()
    {
        $this->selected = null;
        $this->search = '';
        $this->filteredSuppliers = [];
        $this->showDropdown = false;
    }

    public function render()
    {
        return view('livewire.components.suppliers-search-dropdown');
    }
}