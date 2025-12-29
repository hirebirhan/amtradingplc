<?php

namespace App\Livewire\Activities;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\StockHistory;
use App\Models\Item;
use App\Models\Warehouse;
use Carbon\Carbon;
use App\Helpers\UserHelper;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $activityType = '';
    public $warehouseFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 10;
    public $isSalesView = false;
    public $pageTitle = 'Recent Activity';
    public $pageDescription = 'Monitor all inventory movements, stock changes, and system activities';

    protected $queryString = [
        'search' => ['except' => ''],
        'activityType' => ['except' => ''],
        'warehouseFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function mount()
    {
        $this->dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        
        // Set view-specific properties based on user role
        $userHelper = new UserHelper();
        $this->isSalesView = $userHelper->isSales();
        
        if ($this->isSalesView) {
            $this->pageTitle = 'My Activity Log';
            $this->pageDescription = 'Track your inventory movements, sales, and stock changes';
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingActivityType()
    {
        $this->resetPage();
    }

    public function updatingWarehouseFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'activityType', 'warehouseFilter']);
        $this->dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->resetPage();
    }

    public function getActivitiesProperty()
    {
        $user = auth()->user();
        $query = StockHistory::query()
            ->with(['item:id,name,sku', 'warehouse:id,name', 'user:id,name']);

        // Apply branch filtering for non-admin users
        if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
            if ($user->branch_id) {
                // Filter by warehouses in user's branch
                $query->whereHas('warehouse', function($q) use ($user) {
                    $q->whereHas('branches', function($branchQuery) use ($user) {
                        $branchQuery->where('branches.id', $user->branch_id);
                    });
                });
            }
            
            // Sales users see only their own activities
            if ($user->hasRole('Sales')) {
                $query->where('user_id', $user->id);
            }
        }

        $query = $query
            ->when($this->search, function ($query) {
                // Handle SKU search with and without prefix
                $search = $this->search;
                $prefix = config('app.sku_prefix', 'CODE-');
                $rawSearch = str_starts_with($search, $prefix) ? substr($search, strlen($prefix)) : $search;
                
                $query->whereHas('item', function ($q) use ($search, $rawSearch) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('sku', 'like', '%' . $search . '%')
                      ->orWhere('sku', 'like', '%' . $rawSearch . '%');
                });
            })
            ->when($this->activityType, function ($query) {
                $query->where('reference_type', 'like', '%' . $this->activityType . '%');
            })
            ->when($this->warehouseFilter, function ($query) {
                $query->where('warehouse_id', $this->warehouseFilter);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            })
            ->latest();

        return $query->paginate($this->perPage);
    }

    public function getWarehousesProperty()
    {
        $user = auth()->user();
        
        // SuperAdmin and GeneralManager see all warehouses
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return Warehouse::select('id', 'name')->orderBy('name')->get();
        }
        
        // Branch users see only warehouses in their branch
        if ($user->branch_id) {
            return Warehouse::select('id', 'name')
                ->whereHas('branches', function($q) use ($user) {
                    $q->where('branches.id', $user->branch_id);
                })
                ->orderBy('name')
                ->get();
        }
        
        return collect([]);
    }

    public function getActivityTypeDisplayName($referenceType)
    {
        return match(strtolower($referenceType)) {
            'purchase' => 'Purchase',
            'sale' => 'Sale', 
            'transfer' => 'Transfer',
            'return_sale', 'return_purchase' => 'Return',
            'initial' => 'Initial Stock',
            default => 'Adjustment'
        };
    }

    public function getActivityTypeBadgeClass($referenceType)
    {
        return match(strtolower($referenceType)) {
            'purchase' => 'bg-primary',
            'sale' => 'bg-success',
            'transfer' => 'bg-warning',
            'return_sale', 'return_purchase' => 'bg-info',
            'initial' => 'bg-secondary',
            default => 'bg-secondary'
        };
    }

    public function getActivityTypes()
    {
        $types = [
            'sale' => 'Sale',
            'purchase' => 'Purchase',
            'transfer' => 'Transfer',
            'return' => 'Return',
            'adjustment' => 'Adjustment'
        ];

        // If user is sales, only show sale and return types
        if ($this->isSalesView) {
            return array_intersect_key($types, array_flip(['sale', 'return']));
        }

        return $types;
    }

    public function render()
    {
        return view('livewire.activities.index', [
            'activities' => $this->activities,
            'warehouses' => $this->warehouses,
            'activityTypes' => $this->getActivityTypes(),
            'isSalesView' => $this->isSalesView,
            'pageTitle' => $this->pageTitle,
            'pageDescription' => $this->pageDescription
        ])->layout('layouts.app');
    }
} 