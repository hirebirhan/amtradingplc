<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $branchFilter = '';
    public $warehouseFilter = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'branchFilter' => ['except' => ''],
        'warehouseFilter' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
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
        $this->reset(['search', 'status', 'dateFrom', 'dateTo', 'branchFilter', 'warehouseFilter']);
        $this->resetPage();
    }

    /**
     * Apply branch filtering based on user role
     */
    protected function applyBranchFiltering($query)
    {
        $user = auth()->user();
        
        // SuperAdmin and GeneralManager can see all sales
        if ($user->isSuperAdmin()) {
            return $query;
        }
        
        // Branch-specific users (BranchManager, Sales, etc.) see only their branch's sales
        if ($user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }
        
        // Warehouse-specific users see only their warehouse's sales
        if ($user->warehouse_id) {
            return $query->where('warehouse_id', $user->warehouse_id);
        }
        
        return $query;
    }

    /**
     * Get the total count of sales this month (filtered by user's branch)
     */
    public function getThisMonthCountProperty()
    {
        $query = Sale::whereYear('sale_date', now()->year)
            ->whereMonth('sale_date', now()->month);
            
        return $this->applyBranchFiltering($query)->count();
    }

    /**
     * Get the total revenue from all sales (filtered by user's branch)
     */
    public function getTotalRevenueProperty()
    {
        $query = Sale::query();
        return $this->applyBranchFiltering($query)->sum('total_amount');
    }

    /**
     * Get the total revenue from sales this month (filtered by user's branch)
     */
    public function getThisMonthRevenueProperty()
    {
        $query = Sale::whereYear('sale_date', now()->year)
            ->whereMonth('sale_date', now()->month);
            
        return $this->applyBranchFiltering($query)->sum('total_amount');
    }

    /**
     * Get the count of pending sales (filtered by user's branch)
     */
    public function getPendingSalesCountProperty()
    {
        $query = Sale::whereIn('payment_status', ['pending', 'partial']);
        return $this->applyBranchFiltering($query)->count();
    }

    public function sortBy($field)
    {
        // Map 'total' to 'total_amount' for sorting
        $actualField = $field === 'total' ? 'total_amount' : $field;
        
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    /**
     * Get filtered sales query based on search and filters
     */
    protected function getSalesQuery()
    {
        $query = Sale::query()
            ->when($this->search, function($query) {
                $query->where('reference_no', 'like', '%' . $this->search . '%')
                    ->orWhereHas('customer', function($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('branch', function($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('warehouse', function($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->when($this->status, function($query) {
                $query->where('payment_status', $this->status);
            })
            ->when($this->dateFrom, function($query) {
                $query->whereDate('sale_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function($query) {
                $query->whereDate('sale_date', '<=', $this->dateTo);
            })
            ->when($this->branchFilter, function($query) {
                $query->whereHas('warehouse', function($q) {
                    $q->whereHas('branch', function($branchQuery) {
                        $branchQuery->where('id', $this->branchFilter);
                    });
                });
            })
            ->when($this->warehouseFilter, function($query) {
                $query->where('warehouse_id', $this->warehouseFilter);
            })
            ->when($this->sortField === 'total', function($query) {
                $query->orderBy('total_amount', $this->sortDirection);
            }, function($query) {
                $query->orderBy($this->sortField, $this->sortDirection);
            })
            ->with(['customer', 'warehouse', 'user', 'items']);
            
        // Apply branch filtering
        return $this->applyBranchFiltering($query);
    }

    public function render()
    {
        $sales = $this->getSalesQuery()->paginate($this->perPage);

        // Get branches and warehouses for filters
        $branches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get();
        $warehouses = \App\Models\Warehouse::orderBy('name')->get();

        return view('livewire.sales.index', [
            'sales' => $sales,
            'branches' => $branches,
            'warehouses' => $warehouses,
            'thisMonthCount' => $this->thisMonthCount,
            'totalRevenue' => $this->totalRevenue,
            'thisMonthRevenue' => $this->thisMonthRevenue,
            'pendingSalesCount' => $this->pendingSalesCount
        ])->title('Sales');
    }
}