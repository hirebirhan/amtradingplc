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
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'branchFilter' => ['except' => ''],
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

    public function clearFilters()
    {
        $this->reset(['search', 'status', 'dateFrom', 'dateTo', 'branchFilter']);
        $this->resetPage();
    }

    /**
     * Apply branch filtering based on user role
     */
    protected function applyBranchFiltering($query)
    {
        $user = auth()->user();
        
        // SuperAdmin and GeneralManager can see all sales
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return $query;
        }
        
        // Other users see only their branch's sales
        if ($user->branch_id) {
            return $query->forBranch($user->branch_id);
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
                    $q->whereHas('branches', function($branchQuery) {
                        $branchQuery->where('branches.id', $this->branchFilter);
                    });
                });
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

    public function createMissingCredits()
    {
        $salesWithoutCredits = Sale::whereIn('payment_status', ['due', 'partial'])
            ->whereDoesntHave('credit')
            ->where('due_amount', '>', 0)
            ->get();
            
        $count = 0;
        foreach ($salesWithoutCredits as $sale) {
            try {
                $sale->createCreditRecord();
                $count++;
            } catch (\Exception $e) {
                \Log::error('Failed to create credit for sale ' . $sale->id . ': ' . $e->getMessage());
            }
        }
        
        session()->flash('success', 'Created ' . $count . ' missing credit records.');
    }

    public function render()
    {
        $sales = $this->getSalesQuery()->with('credit')->paginate($this->perPage);
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

        return view('livewire.sales.index', [
            'sales' => $sales,
            'branches' => $branches,
            'thisMonthCount' => $this->thisMonthCount,
            'totalRevenue' => $this->totalRevenue,
            'thisMonthRevenue' => $this->thisMonthRevenue,
            'pendingSalesCount' => $this->pendingSalesCount
        ])->title('Sales');
    }
}