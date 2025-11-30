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
    public $warehouseFilter = '';


    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'dateFilter' => ['except' => null],
        'statusFilter' => ['except' => null],
        'branchFilter' => ['except' => ''],
        'warehouseFilter' => ['except' => ''],
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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatingDateFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingBranchFilter()
    {
        $this->resetPage();
    }

    public function updatingWarehouseFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter', 'dateFilter', 'branchFilter', 'warehouseFilter']);
        $this->resetPage();
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
        return Purchase::forUser(Auth::user());
    }



    /**
     * Get filtered purchases query with user-based access control
     */
    protected function getPurchasesQuery()
    {
        $query = Purchase::forUser(Auth::user())
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
            ->when($this->branchFilter, function ($query) {
                $query->where('branch_id', $this->branchFilter);
            })
            ->when($this->warehouseFilter, function ($query) {
                $query->where('warehouse_id', $this->warehouseFilter);
            });

        return $query->orderBy($this->sortField, $this->sortDirection);
    }

    public function render()
    {
        $purchases = $this->getPurchasesQuery()->paginate($this->perPage);

        // Get branches and warehouses for filters
        $branches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get();
        $warehouses = \App\Models\Warehouse::orderBy('name')->get();

        return view('livewire.purchases.index', [
            'purchases' => $purchases,
            'branches' => $branches,
            'warehouses' => $warehouses,
            'totalPurchasesAmount' => $this->totalPurchasesAmount,
            'currentMonthTotal' => $this->currentMonthTotal,
            'pendingPurchasesCount' => $this->pendingPurchasesCount
        ])->title('Purchases');
    }
}