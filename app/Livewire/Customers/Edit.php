<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\Branch;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('Edit Customer')]
class Edit extends Component
{
    public $customer;
    public $form = [];
    public $branches = [];

    protected function rules()
    {
        return [
            'form.name' => 'required|string|max:255',
            'form.email' => 'nullable|email|max:255|unique:customers,email,' . $this->customer->id,
            'form.phone' => 'nullable|string|max:20',
            'form.address' => 'nullable|string|max:255',
            'form.city' => 'nullable|string|max:100',
            'form.state' => 'nullable|string|max:100',
            'form.postal_code' => 'nullable|string|max:20',
            'form.country' => 'nullable|string|max:100',
            'form.credit_limit' => 'numeric|min:0',
            'form.balance' => 'numeric|min:0',
            'form.customer_type' => 'required|in:retail,wholesale,distributor',
            'form.branch_id' => 'nullable|exists:branches,id',
            'form.is_active' => 'boolean',
            'form.notes' => 'nullable|string',
        ];
    }

    protected $messages = [
        'form.name.required' => 'The customer name is required.',
        'form.email.email' => 'Please enter a valid email address.',
        'form.email.unique' => 'This email is already registered for another customer.',
        'form.credit_limit.numeric' => 'Credit limit must be a number.',
        'form.credit_limit.min' => 'Credit limit cannot be negative.',
        'form.balance.numeric' => 'Balance must be a number.',
        'form.balance.min' => 'Balance cannot be negative.',
        'form.customer_type.required' => 'Please select a customer type.',
        'form.customer_type.in' => 'Invalid customer type selected.',
        'form.branch_id.exists' => 'The selected branch does not exist.',
    ];

    public function mount(Customer $customer)
    {
        if (!Auth::user()->can('update', $customer)) {
            return redirect()->route('admin.customers.index')->with('error', 'You do not have permission to edit this customer.');
        }

        $this->customer = $customer;
        $this->branches = Branch::where('is_active', true)->orderBy('name')->get();

        $this->form = [
            'name' => $customer->name,
            'customer_type' => $customer->customer_type,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'city' => $customer->city,
            'state' => $customer->state,
            'postal_code' => $customer->postal_code,
            'country' => $customer->country,
            'credit_limit' => (string) ($customer->credit_limit ?? '0.00'),
            'balance' => (string) ($customer->balance ?? '0.00'),
            'branch_id' => $customer->branch_id,
            'is_active' => $customer->is_active,
            'notes' => $customer->notes,
        ];
    }

    public function update()
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.customer_type' => 'required|string|in:retail,wholesale,distributor',
            'form.email' => 'nullable|email|max:255',
            'form.phone' => 'nullable|string|max:20',
            'form.address' => 'nullable|string|max:255',
            'form.city' => 'nullable|string|max:100',
            'form.state' => 'nullable|string|max:100',
            'form.postal_code' => 'nullable|string|max:20',
            'form.country' => 'nullable|string|max:100',
            'form.credit_limit' => 'nullable|numeric|min:0',
            'form.balance' => 'nullable|numeric|min:0',
            'form.branch_id' => 'nullable|exists:branches,id',
            'form.is_active' => 'nullable|boolean',
            'form.notes' => 'nullable|string',
        ]);

        if (!Auth::user()->can('update', $this->customer)) {
            return redirect()->route('admin.customers.index')->with('error', 'You do not have permission to update this customer.');
        }

        // Update customer - mutators will handle decimal values automatically
        $this->customer->update([
            'name' => $this->form['name'],
            'customer_type' => $this->form['customer_type'],
            'email' => $this->form['email'] ?: null,
            'phone' => $this->form['phone'] ?: null,
            'address' => $this->form['address'] ?: null,
            'city' => $this->form['city'] ?: null,
            'state' => $this->form['state'] ?: null,
            'postal_code' => $this->form['postal_code'] ?: null,
            'country' => $this->form['country'] ?: null,
            'credit_limit' => $this->form['credit_limit'] ?? 0,
            'balance' => $this->form['balance'] ?? $this->customer->balance,
            'branch_id' => $this->form['branch_id'] ?: null,
            'is_active' => $this->form['is_active'] ?? false,
            'notes' => $this->form['notes'] ?: null,
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'title' => 'Customer Updated',
            'message' => 'Customer has been successfully updated.'
        ]);

        return redirect()->route('admin.customers.show', $this->customer);
    }

    public function render()
    {
        return view('livewire.customers.edit');
    }
}