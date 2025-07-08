<?php

namespace App\Livewire\BankAccounts;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Warehouse;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.app')]
class Edit extends Component
{
    public BankAccount $bankAccount;
    public $form = [
        'account_name' => '',
        'account_number' => '',
        'bank_name' => '',
        'location_id' => null,
        'account_type' => '',
        'opening_balance' => 0,
        'currency' => 'ETB',
        'description' => '',
        'is_active' => true,
    ];

    public function mount(BankAccount $bankAccount)
    {
        $this->bankAccount = $bankAccount;
        
        // Determine location
        $locationId = null;
        if ($bankAccount->branch_id) {
            $locationId = 'branch_' . $bankAccount->branch_id;
        } elseif ($bankAccount->warehouse_id) {
            $locationId = 'warehouse_' . $bankAccount->warehouse_id;
        }
        
        // Map database fields to form fields
        $this->form = [
            'account_name' => $bankAccount->account_name,
            'account_number' => $bankAccount->account_number,
            'bank_name' => $bankAccount->bank_name,
            'location_id' => $locationId,
            'account_type' => $bankAccount->account_type ?? 'current', // Default if not set
            'opening_balance' => $bankAccount->balance,
            'currency' => $bankAccount->currency,
            'description' => $bankAccount->notes,
            'is_active' => $bankAccount->is_active,
        ];
    }

    public function update()
    {
        $this->validate([
            'form.account_name' => 'required|string|max:255',
            'form.account_number' => 'required|string|max:255|unique:bank_accounts,account_number,' . $this->bankAccount->id,
            'form.bank_name' => 'required|string|max:255',
            'form.location_id' => 'required|string',
            'form.account_type' => 'required|string|in:savings,checking,current',
            'form.opening_balance' => 'required|numeric|min:0',
            'form.currency' => 'required|string|size:3',
            'form.description' => 'nullable|string',
            'form.is_active' => 'boolean',
        ]);

        // Parse the location_id to extract type and ID
        list($locationType, $locationId) = explode('_', $this->form['location_id']);
        $locationName = '';
        
        // Validate ID based on type and get location name
        if ($locationType === 'branch') {
            // For production, uncomment the following lines to verify branch exists
            // $branch = Branch::find($locationId);
            // if (!$branch) {
            //     $this->addError('form.location_id', 'Selected branch does not exist.');
            //     return;
            // }
            // $locationName = $branch->name;
            
            // Using hardcoded data for now
            $locationName = $locationId == 1 ? 'Main Branch' : 'Secondary Branch';
        } elseif ($locationType === 'warehouse') {
            // For production, uncomment the following lines to verify warehouse exists
            // $warehouse = Warehouse::find($locationId);
            // if (!$warehouse) {
            //     $this->addError('form.location_id', 'Selected warehouse does not exist.');
            //     return;
            // }
            // $locationName = $warehouse->name;
            
            // Using hardcoded data for now
            $locationName = $locationId == 1 ? 'Main Warehouse' : 'Secondary Warehouse';
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
            'balance' => $this->form['opening_balance'],
            'currency' => $this->form['currency'],
            'is_active' => $this->form['is_active'],
            'branch_id' => $locationType === 'branch' ? (int)$locationId : null,
            'warehouse_id' => $locationType === 'warehouse' ? (int)$locationId : null,
            'notes' => $this->form['description'],
        ];

        $this->bankAccount->update($bankAccountData);

        $this->dispatch('notify', type: 'success', message: 'Bank account updated successfully.');

        return redirect()->route('admin.bank-accounts.show', $this->bankAccount);
    }

    public function getLocationsProperty()
    {
        // Hardcoded locations for testing
        return [
            [
                'id' => 'branch_1',
                'name' => 'Main Branch (Branch)',
                'type' => 'branch'
            ],
            [
                'id' => 'branch_2',
                'name' => 'Secondary Branch (Branch)',
                'type' => 'branch'
            ],
            [
                'id' => 'warehouse_1',
                'name' => 'Main Warehouse (Warehouse)',
                'type' => 'warehouse'
            ],
            [
                'id' => 'warehouse_2',
                'name' => 'Secondary Warehouse (Warehouse)',
                'type' => 'warehouse'
            ],
        ];
    }
    
    public function getBanksProperty()
    {
        // List of Ethiopian banks
        return [
            'Abay Bank',
            'Addis International Bank',
            'Awash Bank',
            'Bank of Abyssinia',
            'Berhan International Bank',
            'Bunna International Bank',
            'Commercial Bank of Ethiopia',
            'Cooperative Bank of Oromia',
            'Dashen Bank',
            'Debub Global Bank',
            'Development Bank of Ethiopia',
            'Enat Bank',
            'Lion International Bank',
            'Nib International Bank',
            'Oromia International Bank',
            'United Bank',
            'Wegagen Bank',
            'Zemen Bank',
        ];
    }

    public function render()
    {
        return view('livewire.bank-accounts.edit');
    }
}