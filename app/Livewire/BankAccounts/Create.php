<?php

namespace App\Livewire\BankAccounts;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Services\BankService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.app')]
class Create extends Component
{
    public $form = [
        'account_name' => '',
        'account_number' => '',
        'bank_name' => '',
        'location_type' => 'branch', // branch or warehouse
        'location_id' => null,
        'account_type' => 'current',
        'opening_balance' => 0,
        'currency' => 'ETB',
        'description' => '',
        'is_active' => true,
    ];

    public function mount()
    {
        $currentUser = Auth::user();
        
        // Set default branch for non-SuperAdmin users
        if (!$currentUser->isSuperAdmin() && $currentUser->branch_id) {
            $this->form['location_type'] = 'branch';
            $this->form['location_id'] = 'branch_' . $currentUser->branch_id;
        }
    }

    public function save()
    {
        $this->validate([
            'form.account_name' => 'required|string|max:255',
            'form.account_number' => 'required|string|max:255|unique:bank_accounts,account_number',
            'form.bank_name' => 'required|string|max:255',
            'form.location_id' => 'required|string',
            'form.description' => 'nullable|string',
        ]);

        // Parse the location_id to extract type and ID
        list($locationType, $locationId) = explode('_', $this->form['location_id']);
        $locationName = '';
        
        // Validate ID based on type and get location name
        if ($locationType === 'branch') {
            $branch = Branch::find($locationId);
            if (!$branch) {
                $this->addError('form.location_id', 'Selected branch does not exist.');
                return;
            }
            $locationName = $branch->name;
        } elseif ($locationType === 'warehouse') {
            $warehouse = Warehouse::find($locationId);
            if (!$warehouse) {
                $this->addError('form.location_id', 'Selected warehouse does not exist.');
                return;
            }
            $locationName = $warehouse->name;
        } else {
            $this->addError('form.location_id', 'Invalid location type.');
            return;
        }

        // Map form fields to database fields
        $bankAccountData = [
            'account_name' => $this->form['account_name'],
            'account_number' => $this->form['account_number'],
            'bank_name' => $this->form['bank_name'],
            'branch_name' => $locationName,
            'account_type' => $this->form['account_type'],
            'balance' => $this->form['opening_balance'],
            'currency' => $this->form['currency'],
            'is_active' => $this->form['is_active'],
            'branch_id' => $locationType === 'branch' ? (int)$locationId : null,
            'warehouse_id' => $locationType === 'warehouse' ? (int)$locationId : null,
            'notes' => $this->form['description'],
        ];

        $bankAccount = BankAccount::create($bankAccountData);

        $this->dispatch('notify', type: 'success', message: 'Bank account created successfully.');

        return redirect()->route('admin.bank-accounts.show', $bankAccount);
    }

    public function getLocationsProperty()
    {
        $locations = [];
        
        // Add actual branches from database
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        foreach ($branches as $branch) {
            $locations[] = [
                'id' => 'branch_' . $branch->id,
                'name' => $branch->name . ' (Branch)',
                'type' => 'branch'
            ];
        }
        
        // Add actual warehouses from database (warehouses table doesn't have is_active column)
        $warehouses = Warehouse::orderBy('name')->get();
        foreach ($warehouses as $warehouse) {
            $locations[] = [
                'id' => 'warehouse_' . $warehouse->id,
                'name' => $warehouse->name . ' (Warehouse)', 
                'type' => 'warehouse'
        ];
        }
        
        return $locations;
    }
    
    public function getBanksProperty()
    {
        $bankService = new BankService();
        return $bankService->getEthiopianBanks();
    }

    public function render()
    {
        return view('livewire.bank-accounts.create');
    }
}