<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\Branch;
use App\Traits\HasFlashMessages;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

#[Layout('components.layouts.app')]
class Create extends Component
{
    use HasFlashMessages;
    public $form = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'notes' => '',
    ];

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
                Rule::unique('customers', 'email')->whereNull('deleted_at')
            ],
            'form.phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^\+?[0-9]+$/',
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
        'form.phone.regex' => 'Phone number can only contain digits and + symbol.',
        'form.notes.max' => 'Notes cannot exceed 1000 characters.',
    ];

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
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Ensure + is only at the beginning
        if (str_contains($phone, '+')) {
            $phone = '+' . str_replace('+', '', $phone);
        }
        
        // Basic Ethiopian phone number formatting
        if (strlen($phone) === 10 && !str_starts_with($phone, '+')) {
            $phone = '+251' . substr($phone, 1);
        }
        
        return $phone;
    }

    public function create()
    {
        // Prevent double submission
        if ($this->isSubmitting) {
            return;
        }

        // Check permissions
        if (!Auth::user()->can('create', Customer::class)) {
            $this->dispatch('notify', type: 'error', message: 'You are not authorized to create customers.');
            return;
        }

        $this->isSubmitting = true;

        try {
            // Validate the form
            $validatedData = $this->validate();

            DB::beginTransaction();

            $customerData = [
                'name' => trim($this->form['name']),
                'email' => empty($this->form['email']) ? null : strtolower(trim($this->form['email'])),
                'phone' => $this->formatPhoneNumber($this->form['phone']),
                'notes' => empty($this->form['notes']) ? null : trim($this->form['notes']),
                'customer_type' => 'retail',
                'is_active' => true,
                'balance' => 0.00,
                'credit_limit' => 0.00,
                'branch_id' => Auth::user()->branch_id,
                'created_by' => Auth::id(),
            ];

            $customer = Customer::create($customerData);

            DB::commit();

            $this->reset('form');

            session()->flash('success', 'Customer created successfully.');

            return redirect()->route('admin.customers.show', $customer->id);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->isSubmitting = false;
            
            // Re-throw to show validation errors
            throw $e;
            
        } catch (QueryException $e) {
            DB::rollBack();
            $this->isSubmitting = false;
            
            // Handle duplicate email constraint
            if ($e->errorInfo[1] === 1062 && str_contains($e->getMessage(), 'email')) {
                $this->addError('form.email', 'This email address is already registered.');
                return;
            }
            
            $this->dispatch('notify', type: 'error', message: 'Database error occurred. Please try again.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->isSubmitting = false;
            
            $this->dispatch('notify', type: 'error', message: 'An error occurred while creating the customer. Please try again.');
        }
    }

    public function resetForm()
    {
        $this->reset('form');
        $this->isSubmitting = false;
    }

    public function render()
    {
        return view('livewire.customers.create')->title('Create Customer');
    }
}