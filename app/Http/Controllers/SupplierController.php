<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Using Livewire component instead
        return view('suppliers');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Using Livewire component instead
        return view('suppliers-create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:suppliers',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:50',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $supplier = Supplier::create($validated);

        return redirect()->route('admin.suppliers.show', $supplier)
            ->with('success', 'Supplier created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        // Using Livewire component instead
        return view('suppliers-show', ['supplier' => $supplier]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        // Using Livewire component instead
        return view('suppliers-edit', ['supplier' => $supplier]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', Rule::unique('suppliers')->ignore($supplier->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:50',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return redirect()->route('admin.suppliers.show', $supplier)
            ->with('success', 'Supplier updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        // Check if supplier has related records
        if ($supplier->purchases()->count() > 0 || $supplier->returns()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete supplier with related purchases or returns');
        }

        $supplier->delete();

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Supplier deleted successfully');
    }
}
