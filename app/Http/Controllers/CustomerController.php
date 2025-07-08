<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Using Livewire component instead
        return view('customers');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Using Livewire component instead
        return view('customers-create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'customer_type' => ['required', Rule::in(['retail', 'wholesale', 'distributor'])],
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create($validated);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Customer created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        // Using Livewire component instead
        return view('customers-show', ['customer' => $customer]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        // Using Livewire component instead
        return view('customers-edit', ['customer' => $customer]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', Rule::unique('customers')->ignore($customer->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'customer_type' => ['required', Rule::in(['retail', 'wholesale', 'distributor'])],
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Customer updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        // Check if customer has related records
        if ($customer->sales()->count() > 0 || $customer->returns()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete customer with related sales or returns');
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer deleted successfully');
    }
}
