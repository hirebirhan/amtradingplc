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
        $user = Auth::user();
        $query = Purchase::query();

        // Apply same user-based filtering as main query
        if (!$user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                if ($user->warehouse_id) {
                    $q->where('warehouse_id', $user->warehouse_id);
                } elseif ($user->branch_id) {
                    $q->where('branch_id', $user->branch_id)
                      ->orWhereHas('warehouse', function ($warehouseQuery) use ($user) {
                          $warehouseQuery->whereHas('branches', function ($branchQuery) use ($user) {
                              $branchQuery->where('branch_id', $user->branch_id);
                          });
                      });
                } else {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        return $query;
    }

    public function delete($id)
    {
        // Find the purchase by its ID
        $purchase = Purchase::findOrFail($id);

        // Optional: Check for user authorization
        // if (!Auth::user()->can('delete', $purchase)) {
        //     session()->flash('error', 'You are not authorized to delete this purchase.');
        //     return;
        // }

        try {
            // The 'deleting' event on the Purchase model will handle the rest
            $purchase->delete();
            
            session()->flash('success', 'Purchase ' . $purchase->reference_no . ' and all related records have been deleted successfully.');
            
            // Optional: Dispatch an event if needed by other components
            $this->dispatch('purchaseDeleted');

        } catch (\Exception $e) {
            \Log::error('Failed to delete purchase: ' . $e->getMessage());
            session()->flash('error', 'Failed to delete purchase. Please check the logs for more details.');
        }
    }

    /**
     * Get filtered purchases query with user-based access control
     */
    protected function getPurchasesQuery()
    {
        $user = Auth::user();
        
        $query = Purchase::with(['supplier', 'warehouse', 'branch'])
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
                $query->whereHas('warehouse', function ($q) {
                    $q->whereHas('branch', function ($branchQuery) {
                        $branchQuery->where('id', $this->branchFilter);
                    });
                });
            })
            ->when($this->warehouseFilter, function ($query) {
                $query->where('warehouse_id', $this->warehouseFilter);
            });

        // Apply user-based filtering
        if (!$user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                // If user is assigned to a specific warehouse, show only purchases from that warehouse
                if ($user->warehouse_id) {
                    $q->where('warehouse_id', $user->warehouse_id);
                }
                // If user is assigned to a branch (and not a specific warehouse), show purchases from that branch
                elseif ($user->branch_id) {
                    $q->where('branch_id', $user->branch_id)
                      ->orWhereHas('warehouse', function ($warehouseQuery) use ($user) {
                          $warehouseQuery->whereHas('branches', function ($branchQuery) use ($user) {
                              $branchQuery->where('branch_id', $user->branch_id);
                          });
                      });
                }
                // If user has no specific assignment, they can't see any purchases (except SuperAdmin/GeneralManager)
                else {
                    $q->whereRaw('1 = 0'); // This will return no results
                }
            });
        }

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