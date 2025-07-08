<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use App\Models\Branch;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;

#[Title('Edit Supplier')]
class Edit extends Component
{
    public $supplier;
    public $form = [];
    public $branches = [];

    protected function rules()
    {
        return [
            'form.name' => 'required|string|max:255',
            'form.email' => 'nullable|email|max:255|unique:suppliers,email,' . $this->supplier->id,
            'form.phone' => 'nullable|string|max:20',
            'form.company' => 'nullable|string|max:255',
            'form.address' => 'nullable|string|max:255',
            'form.city' => 'nullable|string|max:100',
            'form.state' => 'nullable|string|max:100',
            'form.postal_code' => 'nullable|string|max:20',
            'form.country' => 'nullable|string|max:100',
            'form.tax_number' => 'nullable|string|max:50',
            'form.branch_id' => 'nullable|exists:branches,id',
            'form.is_active' => 'boolean',
            'form.notes' => 'nullable|string',
        ];
    }

    protected $messages = [
        'form.name.required' => 'The supplier name is required.',
        'form.email.email' => 'Please enter a valid email address.',
        'form.email.unique' => 'This email is already registered for another supplier.',
        'form.branch_id.exists' => 'The selected branch does not exist.',
    ];

    public function mount(Supplier $supplier)
    {
        if (!Auth::user()->can('update', $supplier)) {
            return redirect()->route('admin.suppliers.index')->with('error', 'You do not have permission to edit this supplier.');
        }

        $this->supplier = $supplier;
        $this->branches = Branch::where('is_active', true)->orderBy('name')->get();

        $this->form = [
            'name' => $supplier->name,
            'company' => $supplier->company,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'address' => $supplier->address,
            'city' => $supplier->city,
            'state' => $supplier->state,
            'postal_code' => $supplier->postal_code,
            'country' => $supplier->country,
            'tax_number' => $supplier->tax_number,
            'branch_id' => $supplier->branch_id,
            'is_active' => $supplier->is_active,
            'notes' => $supplier->notes,
        ];
    }

    public function update()
    {
        $this->validate();

        if (!Auth::user()->can('update', $this->supplier)) {
            return redirect()->route('admin.suppliers.index')->with('error', 'You do not have permission to update this supplier.');
        }

        $this->supplier->update([
            'name' => $this->form['name'],
            'company' => $this->form['company'],
            'email' => $this->form['email'],
            'phone' => $this->form['phone'],
            'address' => $this->form['address'],
            'city' => $this->form['city'],
            'state' => $this->form['state'],
            'postal_code' => $this->form['postal_code'],
            'country' => $this->form['country'],
            'tax_number' => $this->form['tax_number'],
            'branch_id' => $this->form['branch_id'],
            'is_active' => $this->form['is_active'] ?? false,
            'notes' => $this->form['notes'],
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'title' => 'Supplier Updated',
            'message' => 'Supplier has been successfully updated.'
        ]);

        return redirect()->route('admin.suppliers.show', $this->supplier);
    }

    public function render()
    {
        return view('livewire.suppliers.edit');
    }
}