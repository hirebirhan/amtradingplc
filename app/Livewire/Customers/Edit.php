<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\Branch;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use App\Rules\PhoneNumber;

#[Layout('components.layouts.app')]
#[Title('Edit Customer')]
class Edit extends Component
{
    public $customer;
    public $form = [];
    public $isSubmitting = false;

    protected function rules()
    {
        return [
            'form.name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                'regex:/^[a-zA-Z\s\-\'\.\.]+$/',
            ],
            'form.email' => [
                'nullable',
                'email',
                'max:255',
                'unique:customers,email,' . $this->customer->id . ',id,deleted_at,NULL'
            ],
            'form.phone' => [
                'required',
                'string',
                'max:20',
                new PhoneNumber(),
            ],
            'form.notes' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'form.name.required' => 'Customer name is required.',
        'form.name.regex' => 'Customer name cannot include numbers or special characters.',
        'form.email.email' => 'Please enter a valid email address.',
        'form.email.unique' => 'This email is already registered.',
        'form.phone.required' => 'Phone number is required.',
        'form.phone.required' => 'Phone number is required.',
        'form.notes.max' => 'Notes cannot exceed 1000 characters.',
    ];

    public function mount(Customer $customer)
    {
        if (!Auth::user()->can('update', $customer)) {
            return redirect()->route('admin.customers.index')->with('error', 'You do not have permission to edit this customer.');
        }

        $this->customer = $customer;
        $this->form = [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'notes' => $customer->notes,
        ];
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['form.name', 'form.email', 'form.phone', 'form.notes'])) {
            $this->validateOnly($propertyName);
        }

        if ($propertyName === 'form.phone') {
            $this->form['phone'] = $this->formatPhoneNumber($this->form['phone']);
        }
    }

    private function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (str_contains($phone, '+')) {
            $phone = '+' . str_replace('+', '', $phone);
        }
        
        if (strlen($phone) === 10 && !str_starts_with($phone, '+')) {
            $phone = '+251' . substr($phone, 1);
        }
        
        return $phone;
    }

    public function update()
    {
        if ($this->isSubmitting) {
            return;
        }

        if (!Auth::user()->can('update', $this->customer)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'You are not authorized to update this customer.']);
            return;
        }

        $this->isSubmitting = true;

        try {
            $this->validate();

            $this->customer->update([
                'name' => trim($this->form['name']),
                'email' => empty($this->form['email']) ? null : strtolower(trim($this->form['email'])),
                'phone' => $this->formatPhoneNumber($this->form['phone']),
                'notes' => empty($this->form['notes']) ? null : trim($this->form['notes']),
                'updated_by' => Auth::id(),
            ]);

            session()->flash('success', 'Customer updated successfully.');
            return redirect()->route('admin.customers.show', $this->customer);

        } catch (\Exception $e) {
            $this->isSubmitting = false;
            $this->dispatch('notify', ['type' => 'error', 'message' => 'An error occurred while updating the customer.']);
        }
    }

    public function render()
    {
        return view('livewire.customers.edit');
    }
}