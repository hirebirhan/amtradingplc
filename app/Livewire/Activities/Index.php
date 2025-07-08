<?php

namespace App\Livewire\Activities;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\StockHistory;
use App\Models\Item;
use App\Models\Warehouse;
use Carbon\Carbon;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $activityType = '';
    public $warehouseFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 10;

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
        $query = StockHistory::query()
            ->with(['item:id,name,sku', 'warehouse:id,name', 'user:id,name']);

        // Filter by user for Sales role - they can only see their own activities
        $user = auth()->user();
        $isSales = $user->hasRole('Sales');
        $isSuperAdminOrManager = $user->hasAnyRole(['SuperAdmin', 'BranchManager']);
        
        if ($isSales && !$isSuperAdminOrManager) {
            $query->where('user_id', auth()->id());
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
        return Warehouse::select('id', 'name')->orderBy('name')->get();
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

    public function render()
    {
        return view('livewire.activities.index', [
            'activities' => $this->activities,
            'warehouses' => $this->warehouses,
        ])->layout('layouts.app');
    }
} 