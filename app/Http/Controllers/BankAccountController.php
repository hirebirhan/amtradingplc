<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        return app(\App\Livewire\BankAccounts\Index::class)->render();
    }

    public function create()
    {
        return view('bank-accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255|unique:bank_accounts,account_number',
            'bank_name' => 'required|string|max:255',
            'location_id' => 'required|string', // format: type_id (e.g., branch_1, warehouse_2)
            'account_type' => 'required|string|in:checking,savings,current',
            'opening_balance' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Parse location_id to extract type and ID
        list($locationType, $locationId) = explode('_', $request->location_id);
        $locationName = '';
        
        // Determine location name based on type
        if ($locationType === 'branch') {
            // For production, uncomment and use database check
            // $branch = Branch::find($locationId);
            // $locationName = $branch ? $branch->name : '';
            $locationName = $locationId == 1 ? 'Main Branch' : 'Secondary Branch';
        } elseif ($locationType === 'warehouse') {
            // For production, uncomment and use database check
            // $warehouse = Warehouse::find($locationId);
            // $locationName = $warehouse ? $warehouse->name : '';
            $locationName = $locationId == 1 ? 'Main Warehouse' : 'Secondary Warehouse';
        } else {
            return back()->withErrors(['location_id' => 'Invalid location type.']);
        }

        // Map form fields to database fields
        $bankAccountData = [
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'bank_name' => $validated['bank_name'],
            'branch_name' => $locationName,
            'balance' => $validated['opening_balance'],
            'currency' => $validated['currency'],
            'is_active' => $validated['is_active'] ?? false,
            'branch_id' => $locationType === 'branch' ? (int)$locationId : null,
            'warehouse_id' => $locationType === 'warehouse' ? (int)$locationId : null,
            'notes' => $validated['description'] ?? null,
        ];

        BankAccount::create($bankAccountData);

        return redirect()->route('admin.bank-accounts.index')
            ->with('success', 'Bank account created successfully.');
    }

    public function show(BankAccount $bankAccount)
    {
        return view('bank-accounts.show', compact('bankAccount'));
    }

    public function edit(BankAccount $bankAccount)
    {
        return view('bank-accounts.edit', compact('bankAccount'));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255|unique:bank_accounts,account_number,' . $bankAccount->id,
            'bank_name' => 'required|string|max:255',
            'location_id' => 'required|string', // format: type_id (e.g., branch_1, warehouse_2)
            'account_type' => 'required|string|in:checking,savings,current',
            'opening_balance' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        // Parse location_id to extract type and ID
        list($locationType, $locationId) = explode('_', $request->location_id);
        $locationName = '';
        
        // Determine location name based on type
        if ($locationType === 'branch') {
            // For production, uncomment and use database check
            // $branch = Branch::find($locationId);
            // $locationName = $branch ? $branch->name : '';
            $locationName = $locationId == 1 ? 'Main Branch' : 'Secondary Branch';
        } elseif ($locationType === 'warehouse') {
            // For production, uncomment and use database check
            // $warehouse = Warehouse::find($locationId);
            // $locationName = $warehouse ? $warehouse->name : '';
            $locationName = $locationId == 1 ? 'Main Warehouse' : 'Secondary Warehouse';
        } else {
            return back()->withErrors(['location_id' => 'Invalid location type.']);
        }

        // Map form fields to database fields
        $bankAccountData = [
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'bank_name' => $validated['bank_name'],
            'branch_name' => $locationName,
            'balance' => $validated['opening_balance'],
            'currency' => $validated['currency'],
            'is_active' => $validated['is_active'] ?? false,
            'branch_id' => $locationType === 'branch' ? (int)$locationId : null,
            'warehouse_id' => $locationType === 'warehouse' ? (int)$locationId : null,
            'notes' => $validated['description'] ?? null,
        ];

        $bankAccount->update($bankAccountData);

        return redirect()->route('admin.bank-accounts.index')
            ->with('success', 'Bank account updated successfully.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return redirect()->route('admin.bank-accounts.index')
            ->with('success', 'Bank account deleted successfully.');
    }
}