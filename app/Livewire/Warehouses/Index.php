<?php

namespace App\Livewire\Warehouses;

use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\Stock;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Layout('layouts.app')]
#[Title('Warehouse Management')]
class Index extends Component
{
    use WithPagination, AuthorizesRequests;

    public $search = '';
    public $branchFilter = '';
    public $perPage = 10;
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $warehouseToDelete;

    protected $queryString = [
        'search' => ['except' => ''],
        'branchFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingBranchFilter()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    /**
     * Get the total count of warehouses
     */
    public function getWarehousesCountProperty()
    {
        return Warehouse::count();
    }

    /**
     * Get the total count of items across all warehouses
     */
    public function getTotalItemsCountProperty()
    {
        return Stock::sum('quantity');
    }

    /**
     * Get count of warehouses with low stock items
     */
    public function getLowStockWarehousesCountProperty()
    {
        return Warehouse::whereHas('stocks', function($query) {
            $query->whereHas('item', function($q) {
                $q->whereColumn('stocks.quantity', '<=', 'items.reorder_level');
            });
        })->count();
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

    /**
     * Get filtered warehouses query based on search and filters
     */
    protected function getWarehousesQuery()
    {
        return Warehouse::query()
            ->with(['branches', 'stocks'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('code', 'like', '%' . $this->search . '%')
                        ->orWhere('address', 'like', '%' . $this->search . '%')
                        ->orWhere('manager_name', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->branchFilter, function ($query) {
                $query->whereHas('branches', function($q) {
                    $q->where('branches.id', $this->branchFilter);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection);
    }

    public function render()
    {
        $warehouses = $this->getWarehousesQuery()->paginate($this->perPage);

        return view('livewire.warehouses.index', [
            'warehouses' => $warehouses,
            'branches' => Branch::orderBy('name')->get(),
            'active' => 'warehouses',
            'activeWarehousesCount' => $this->warehousesCount,
            'totalItemsCount' => $this->totalItemsCount,
            'lowStockWarehousesCount' => $this->lowStockWarehousesCount
        ])->title('Warehouse Management');
    }

    public function deleteWarehouse($warehouseId)
    {
        // Check permission
        if (!auth()->user()->can('warehouses.delete')) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'You do not have permission to delete warehouses.']);
            return;
        }

        $warehouse = Warehouse::find($warehouseId);
        
        if (!$warehouse) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Warehouse not found.']);
            $this->dispatch('warehouseDeleted', ['success' => false]);
            return;
        }
        
        // Check if warehouse has stock items
        if ($warehouse->stocks()->count() > 0) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Cannot delete warehouse with stock items. Remove items first.']);
            $this->dispatch('warehouseDeleted', ['success' => false]);
            return;
        }
        
        try {
            // Delete the warehouse
            $warehouse->delete();
            
            $this->dispatch('notify', ['type' => 'success', 'message' => "Warehouse '{$warehouse->name}' deleted successfully."]);
            $this->dispatch('warehouseDeleted', ['success' => true]);
            $this->resetPage(); // Reset to first page after deletion
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => "Error deleting warehouse: {$e->getMessage()}"]);
            $this->dispatch('warehouseDeleted', ['success' => false]);
        }
    }
}