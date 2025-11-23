<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
class Create extends Component
{
    // Only keep the required field to simplify supplier creation
    public $form = [
        'name' => '',
        'phone' => '',
        'address' => '',
        'reference_no' => '',
        'email' => '',
        'notes' => '',
    ];

    public function mount()
    {
        $this->form['reference_no'] = $this->generateUniqueReferenceNumber();
    }

    private function generateUniqueReferenceNumber()
    {
        return 'SUP-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    protected $rules = [
        'form.name' => 'required|string|max:255|regex:/^[^0-9]+$/',
        'form.phone' => 'required|string|max:20',
        'form.address' => 'required|string|max:255',
    ];

    protected $messages = [
        'form.name.required' => 'The supplier name is required.',
        'form.phone.required' => 'The phone number is required.',
        'form.address.required' => 'The address is required.',
        'form.name.regex' => 'The name may not contain numbers.',
    ];

    public function create()
    {
        // Check permissions
        if (!Auth::user()->can('create', Supplier::class)) {
            $this->dispatch('toast', type: 'error', message: 'You are not authorized to create suppliers.');
            return;
        }

        $this->validate();

        // Automatically mark new suppliers as active
        $supplier = Supplier::create([
            'name' => $this->form['name'],
            'phone' => $this->form['phone'],
            'address' => $this->form['address'],
            'reference_no' => $this->form['reference_no'],
            'email' => $this->form['email'],
            'notes' => $this->form['notes'],
            'is_active' => true,
        ]);

        session()->flash('message', 'Supplier created successfully!');
        return redirect()->route('admin.suppliers.show', $supplier->id);
    }

    public function render()
    {
        // Render the simplified view (no need to pass branches since the dropdown was removed)
        return view('livewire.suppliers.create')->title('Create Supplier');
    }
}