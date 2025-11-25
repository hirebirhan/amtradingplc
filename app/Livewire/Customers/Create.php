<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\Branch;
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
    public $form = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'city' => '',
        'credit_limit' => 0.00,
        'customer_type' => 'retail',
        'notes' => '',
    ];

    public $isSubmitting = false;

    protected function rules()
    {
        return [
            'form.name' => 'required|string|max:255|min:2',
            'form.email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->whereNull('deleted_at')
            ],
            'form.phone' => 'nullable|string|max:20',
            'form.address' => 'nullable|string|max:500',
            'form.city' => 'nullable|string|max:100',
            'form.credit_limit' => 'nullable|numeric|min:0|max:999999.99',
            'form.customer_type' => 'required|in:retail,wholesale,distributor',
            'form.notes' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'form.name.required' => 'Customer name is required.',
        'form.name.min' => 'Customer name must be at least 2 characters.',
        'form.name.max' => 'Customer name cannot exceed 255 characters.',
        'form.email.email' => 'Please enter a valid email address.',
        'form.email.unique' => 'This email is already registered for another customer.',
        'form.email.max' => 'Email cannot exceed 255 characters.',
        'form.phone.max' => 'Phone number cannot exceed 20 characters.',
        'form.credit_limit.numeric' => 'Credit limit must be a valid number.',
        'form.credit_limit.min' => 'Credit limit cannot be negative.',
        'form.credit_limit.max' => 'Credit limit cannot exceed 999,999.99.',
        'form.customer_type.required' => 'Please select a customer type.',
        'form.customer_type.in' => 'Invalid customer type selected.',
        'form.notes.max' => 'Notes cannot exceed 1000 characters.',
        'form.address.max' => 'Address cannot exceed 500 characters.',
        'form.city.max' => 'City name cannot exceed 100 characters.',
    ];

    public function updated($propertyName)
    {
        // Real-time validation for specific fields
        if (in_array($propertyName, [
            'form.name', 
            'form.email', 
            'form.phone', 
            'form.credit_limit',
            'form.customer_type',
            'form.address',
            'form.city',
            'form.notes'
        ])) {
            try {
                $this->validateOnly($propertyName);
            } catch (\Illuminate\Validation\ValidationException $e) {
                // Validation errors will be automatically displayed
            }
        }

        // Auto-format phone number
        if ($propertyName === 'form.phone') {
            $this->form['phone'] = $this->formatPhoneNumber($this->form['phone']);
        }

        // Auto-capitalize name
        if ($propertyName === 'form.name') {
            $this->form['name'] = ucwords(strtolower($this->form['name']));
        }

        // Auto-capitalize city
        if ($propertyName === 'form.city') {
            $this->form['city'] = ucwords(strtolower($this->form['city']));
        }
    }

    private function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
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
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'You are not authorized to create customers.',
            ]);
            return;
        }

        $this->isSubmitting = true;

        try {
            // Validate the form
            $validatedData = $this->validate();

            DB::beginTransaction();

            // Prepare customer data with proper null handling
            $customerData = [
                'name' => $this->form['name'],
                'email' => empty($this->form['email']) ? null : $this->form['email'],
                'phone' => empty($this->form['phone']) ? null : $this->form['phone'],
                'address' => empty($this->form['address']) ? null : $this->form['address'],
                'city' => empty($this->form['city']) ? null : $this->form['city'],
                'credit_limit' => $this->form['credit_limit'] ?? 0.00,
                'customer_type' => $this->form['customer_type'],
                'notes' => empty($this->form['notes']) ? null : $this->form['notes'],
                // Auto-set fields
                'is_active' => true,
                'balance' => 0.00,
                'country' => 'Ethiopia',
                'branch_id' => Auth::user()->branch_id,
                'created_by' => Auth::id(),
            ];

            $customer = Customer::create($customerData);

            DB::commit();

            // Reset the form
            $this->resetForm();

            $this->dispatch('toast', [
                'type' => 'success',
                'message' => 'Customer created successfully!',
            ]);

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
            
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Database error occurred. Please try again.',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->isSubmitting = false;
            
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'An error occurred while creating the customer. Please try again.',
            ]);
        }
    }

    public function resetForm()
    {
        $this->form = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'city' => '',
            'credit_limit' => 0.00,
            'customer_type' => 'retail',
            'notes' => '',
        ];
        
        $this->isSubmitting = false;
        $this->resetValidation();
    }

    public function getCustomerTypesProperty()
    {
        return [
            'retail' => [
                'label' => 'Retail Customer',
                'description' => 'Individual customers buying for personal use',
                'icon' => 'fas fa-user'
            ],
            'wholesale' => [
                'label' => 'Wholesale Customer', 
                'description' => 'Businesses buying in bulk quantities',
                'icon' => 'fas fa-building'
            ],
            'distributor' => [
                'label' => 'Distributor',
                'description' => 'Partners who resell products to other businesses',
                'icon' => 'fas fa-truck'
            ]
        ];
    }

    public function render()
    {
        return view('livewire.customers.create', [
            'customerTypes' => $this->customerTypes,
        ])->title('Create Customer');
    }
}