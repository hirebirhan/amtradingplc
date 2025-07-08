<?php

namespace App\Livewire\Components;

use App\Models\Customer;
use Livewire\Component;

class CustomersSearchDropdown extends Component
{
    public $search = '';
    public $filteredCustomers = [];
    public $selected = null;
    public $showDropdown = false;
    public $placeholder = 'Search for a customer...';
    public $minimumCharacters = 2;
    public $maxResults = 10;

    // Event that will be emitted when a customer is selected
    public $emitUpEvent = 'customerSelected';

    protected $listeners = ['clearSelected'];

    public function mount($selected = null)
    {
        $this->selected = $selected;

        if ($selected) {
            $this->loadSelectedCustomer();
        }
    }

    public function loadSelectedCustomer()
    {
        if (!$this->selected) {
            return;
        }

        $customer = Customer::find($this->selected);
        if ($customer) {
            $this->search = $customer->name;
        }
    }

    public function updatedSearch()
    {
        $this->filterCustomers();
    }

    public function filterCustomers()
    {
        $this->filteredCustomers = [];

        if (strlen($this->search) < $this->minimumCharacters) {
            $this->showDropdown = false;
            return;
        }

        $query = Customer::where('is_active', true)
            ->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });

        $customers = $query->take($this->maxResults)->get();

        foreach ($customers as $customer) {
            $this->filteredCustomers[] = [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'balance' => $customer->balance
            ];
        }

        $this->showDropdown = count($this->filteredCustomers) > 0;
    }

    public function selectCustomer($customerId)
    {
        $this->selected = $customerId;

        // Find the selected customer in the filtered list
        $selectedCustomer = collect($this->filteredCustomers)->firstWhere('id', $customerId);
        if ($selectedCustomer) {
            $this->search = $selectedCustomer['name'];
            $this->dispatch($this->emitUpEvent, $selectedCustomer);
        }

        $this->showDropdown = false;
    }

    public function clearSelected()
    {
        $this->selected = null;
        $this->search = '';
        $this->filteredCustomers = [];
        $this->showDropdown = false;
    }

    public function render()
    {
        return view('livewire.components.customers-search-dropdown');
    }
}