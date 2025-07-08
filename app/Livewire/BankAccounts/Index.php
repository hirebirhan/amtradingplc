<?php

namespace App\Livewire\BankAccounts;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $filters = [
        'bank_name' => '',
        'branch_id' => '',
    ];

    public $perPage = 10;

    protected $listeners = ['refreshBankAccounts' => '$refresh'];

    public function mount()
    {
        // Initialize filters if needed
    }

    public function delete($id)
    {
        $account = BankAccount::findOrFail($id);
        $account->delete();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Bank account deleted successfully.'
        ]);
        
        $this->dispatch('accountDeleted');
    }

    public function clearFilters()
    {
        $this->filters = [
            'bank_name' => '',
            'branch_id' => '',
        ];
        $this->perPage = 10;
    }



    /**
     * Get list of all banks for filtering
     */
    public function getBanksProperty()
    {
        return BankAccount::distinct()->pluck('bank_name')->toArray();
    }

    /**
     * Get list of branches for filtering
     */
    public function getBranchesProperty()
    {
        if (Auth::user()->hasRole('admin')) {
            return \App\Models\Branch::orderBy('name')->get();
        }
        return \App\Models\Branch::where('id', Auth::user()->branch_id)->get();
    }

    /**
     * Get total balance across all bank accounts
     */
    public function getTotalBalanceProperty()
    {
        return BankAccount::sum('balance');
    }

    /**
     * Get number of active accounts
     */
    public function getActiveAccountsCountProperty()
    {
        return BankAccount::where('is_active', true)->count();
    }

    /**
     * Get bank with highest balance
     */
    public function getTopBankProperty()
    {
        $topBank = BankAccount::select('bank_name', DB::raw('SUM(balance) as total_balance'))
            ->groupBy('bank_name')
            ->orderByDesc('total_balance')
            ->first();
            
        return $topBank ? [
            'name' => $topBank->bank_name,
            'balance' => $topBank->total_balance
        ] : null;
    }

    /**
     * Get query for bank accounts
     */
    protected function getAccountsQuery()
    {
        $query = BankAccount::query()
            ->where('is_active', true)
            ->when($this->filters['bank_name'], function ($query, $bank) {
                return $query->where('bank_name', $bank);
            })
            ->when($this->filters['branch_id'], function ($query, $branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->orderBy('created_at', 'desc');

        // Only apply branch filtering if user has a branch_id AND is not an admin
        // This ensures all accounts are visible in the admin interface
        if (!Auth::user()->hasRole('admin') && Auth::user()->branch_id) {
            $query->where(function($query) {
                $query->where('branch_id', Auth::user()->branch_id)
                      ->orWhereNull('branch_id'); // Also show accounts with no branch assignment
            });
        }

        return $query;
    }

    public function render()
    {
        return view('livewire.bank-accounts.index', [
            'accounts' => $this->getAccountsQuery()->paginate($this->perPage),
            'banks' => $this->banks,
            'branches' => $this->branches,
            'totalBalance' => $this->totalBalance,
            'activeAccountsCount' => $this->activeAccountsCount,
            'topBank' => $this->topBank
        ]);
    }
}