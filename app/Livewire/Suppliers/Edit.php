<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use App\Models\Branch;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('Edit Supplier')]
class Edit extends Component
{
    public $supplier;
    public $form = [];
    public $isSubmitting = false;
    public $branches = [];

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
            'form.company' => 'nullable|string|max:255',
            'form.email' => [
                'nullable',
                'email',
                'max:255',
                'unique:suppliers,email,' . $this->supplier->id . ',id,deleted_at,NULL'
            ],
            'form.phone' => 'nullable|string|max:20|regex:/^\+?[0-9]+$/',
            'form.branch_id' => 'nullable|exists:branches,id',
            'form.address' => 'nullable|string|max:500',
            'form.notes' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'form.name.required' => 'Supplier name is required.',
        'form.name.regex' => 'Supplier name cannot include numbers or special characters.',
        'form.phone.regex' => 'Phone number can only contain digits and + symbol.',
        'form.email.email' => 'Please enter a valid email address.',
        'form.email.unique' => 'This email is already registered.',
        'form.branch_id.exists' => 'Please select a valid branch.',
        'form.notes.max' => 'Notes cannot exceed 1000 characters.',
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
            'branch_id' => $supplier->branch_id,
            'address' => $supplier->address,
            'notes' => $supplier->notes,
        ];
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['form.name', 'form.company', 'form.email', 'form.phone', 'form.branch_id', 'form.address', 'form.notes'])) {
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

        if (!Auth::user()->can('update', $this->supplier)) {
            $this->dispatch('notify', type: 'error', message: 'You are not authorized to update this supplier.');
            return;
        }

        $this->isSubmitting = true;

        try {
            $this->validate();

            $this->supplier->update([
                'name' => trim($this->form['name']),
                'company' => empty($this->form['company']) ? null : trim($this->form['company']),
                'email' => empty($this->form['email']) ? null : strtolower(trim($this->form['email'])),
                'phone' => empty($this->form['phone']) ? null : $this->formatPhoneNumber($this->form['phone']),
                'branch_id' => empty($this->form['branch_id']) ? null : $this->form['branch_id'],
                'address' => empty($this->form['address']) ? null : trim($this->form['address']),
                'notes' => empty($this->form['notes']) ? null : trim($this->form['notes']),
                'updated_by' => Auth::id(),
            ]);

            session()->flash('success', 'Supplier updated successfully.');
            return redirect()->route('admin.suppliers.show', $this->supplier);

        } catch (\Exception $e) {
            $this->isSubmitting = false;
            $this->dispatch('notify', type: 'error', message: 'An error occurred while updating the supplier.');
        }
    }

    public function render()
    {
        return view('livewire.suppliers.edit', [
            'branches' => $this->branches
        ]);
    }
}