<?php

namespace App\Livewire\Purchases;

use App\Models\Purchase;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $dateFilter = null;
    public $statusFilter = null;
    public $branchFilter = '';


    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'dateFilter' => ['except' => null],
        'statusFilter' => ['except' => null],
        'branchFilter' => ['except' => ''],
    ];

    public function mount()
    {
        // Check if user has permission to view purchases
        if (!Auth::user()->can('purchases.view')) {
            abort(403, 'You do not have permission to view purchases.');
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter', 'dateFilter', 'branchFilter']);
        $this->resetPage();
    }

    /**
     * Apply branch filtering based on user role
     */
    protected function applyBranchFiltering($query)
    {
        $user = auth()->user();
        
        // SuperAdmin and GeneralManager can see all purchases
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return $query;
        }
        
        // Other users see only their branch's purchases
        if ($user->branch_id) {
            return $query->forBranch($user->branch_id);
        }
        
        return $query;
    }

    /**
     * Get total amount of all purchases (filtered by user access)
     */
    public function getTotalPurchasesAmountProperty()
    {
        return $this->getBaseQueryForStats()->sum('total_amount');
    }

    /**
     * Get total amount of purchases this month (filtered by user access)
     */
    public function getCurrentMonthTotalProperty()
    {
        return $this->getBaseQueryForStats()
            ->whereYear('purchase_date', now()->year)
            ->whereMonth('purchase_date', now()->month)
            ->sum('total_amount');
    }

    /**
     * Get count of pending purchases (unpaid or partially paid, filtered by user access)
     */
    public function getPendingPurchasesCountProperty()
    {
        return $this->getBaseQueryForStats()
            ->whereIn('payment_status', ['pending', 'partial', 'due'])
            ->count();
    }

    /**
     * Get base query for statistics with user-based filtering
     */
    private function getBaseQueryForStats()
    {
        $query = Purchase::query();
        return $this->applyBranchFiltering($query);
    }



    /**
     * Get filtered purchases query with user-based access control
     */
    protected function getPurchasesQuery()
    {
        $query = Purchase::query()
            ->with(['supplier', 'warehouse', 'branch', 'credit'])
            ->when($this->search, function ($query) {
                $query->where('reference_no', 'like', '%' . $this->search . '%')
                    ->orWhereHas('supplier', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('warehouse', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('branch', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->when($this->dateFilter, function ($query) {
                $query->whereDate('purchase_date', $this->dateFilter);
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('payment_status', $this->statusFilter);
            })
            ->when($this->branchFilter, function($query) {
                $query->whereHas('warehouse', function($q) {
                    $q->whereHas('branches', function($branchQuery) {
                        $branchQuery->where('branches.id', $this->branchFilter);
                    });
                });
            });

        // Apply branch filtering based on user role
        $query = $this->applyBranchFiltering($query);
        
        return $query->orderBy($this->sortField, $this->sortDirection);
    }

    public function render()
    {
        $purchases = $this->getPurchasesQuery()->paginate($this->perPage);
        $user = auth()->user();

        // Get branches for filters based on user role
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            $branches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get();
        } elseif ($user->isBranchManager() && $user->branch_id) {
            $branches = \App\Models\Branch::where('id', $user->branch_id)->where('is_active', true)->get();
        } elseif ($user->warehouse_id) {
            $warehouse = \App\Models\Warehouse::with('branches')->find($user->warehouse_id);
            $branches = $warehouse ? $warehouse->branches : collect([]);
        } else {
            $branches = collect([]);
        }

        return view('livewire.purchases.index', [
            'purchases' => $purchases,
            'branches' => $branches,
            'totalPurchasesAmount' => $this->totalPurchasesAmount,
            'currentMonthTotal' => $this->currentMonthTotal,
            'pendingPurchasesCount' => $this->pendingPurchasesCount
        ])->title('Purchases');
    }
}