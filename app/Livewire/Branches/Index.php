<?php

namespace App\Livewire\Branches;

use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
#[Title('Branches')]
class Index extends Component
{
    use WithPagination, AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $perPage = 10;

    public $sortField = 'name';
    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public $branchToDelete = null;
    public $targetBranches = [];
    public $showTransferModal = false;

    protected $listeners = [
        'confirmBranchDeletion' => 'confirmDelete',
        'deleteBranch' => 'deleteBranch',
        'showTransferOptions' => 'showTransferOptions'
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }



    /**
     * Get the total count of branches (all active)
     */
    public function getActiveBranchesCountProperty()
    {
        return Branch::where('is_active', true)->count();
    }

    /**
     * Get the total count of warehouses across all branches
     */
    public function getTotalWarehousesCountProperty()
    {
        return Warehouse::count();
    }

    /**
     * Get the count of users assigned to branches
     */
    public function getBranchUsersCountProperty()
    {
        return User::whereNotNull('branch_id')->count();
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
     * Get filtered branches query based on search and filters
     */
    protected function getBranchesQuery()
    {
        return Branch::query()
            ->withCount('warehouses')
            ->when($this->search, function ($query) {
                return $query->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%')
                        ->orWhere('address', 'like', '%' . $this->search . '%');
                });
            })
            ->where('is_active', true)
            ->orderBy($this->sortField, $this->sortDirection);
    }

    public function delete($branchId)
    {
        $this->branchToDelete = $branchId;
        
        // Check if branch has stock items
        $branch = Branch::with('warehouses.stocks')->find($this->branchToDelete);
        $stockCount = $this->getBranchStockCount($branch);
        
        if ($stockCount > 0) {
            // Branch has stock - show options for transfer or cancel
            $this->dispatch('showDeleteConfirmation', [
                'title' => 'Delete Branch with Inventory',
                'message' => "This branch has {$stockCount} items in stock. You can either transfer the inventory to another branch before deletion or cancel the operation.",
                'event' => 'showTransferOptions',
                'hasStock' => true,
                'stockCount' => $stockCount,
            ]);
        } else {
            // No stock - normal deletion confirmation
            $this->dispatch('showDeleteConfirmation', [
                'title' => 'Delete Branch',
                'message' => 'Are you sure you want to delete this branch? This action cannot be undone.',
                'event' => 'deleteBranch',
                'hasStock' => false,
            ]);
        }
    }

    public function confirmDelete($data)
    {
        $this->branchToDelete = $data['branchId'];
        
        // Check if branch has stock items
        $branch = Branch::with('warehouses.stocks')->find($this->branchToDelete);
        $stockCount = $this->getBranchStockCount($branch);
        
        if ($stockCount > 0) {
            // Branch has stock - show options for transfer or cancel
            $this->dispatch('showDeleteConfirmation', [
                'title' => 'Delete Branch with Inventory',
                'message' => "This branch has {$stockCount} items in stock. You can either transfer the inventory to another branch before deletion or cancel the operation.",
                'event' => 'showTransferOptions',
                'hasStock' => true,
                'stockCount' => $stockCount,
            ]);
        } else {
            // No stock - normal deletion confirmation
        $this->dispatch('showDeleteConfirmation', [
            'title' => 'Delete Branch',
            'message' => 'Are you sure you want to delete this branch? This action cannot be undone.',
            'event' => 'deleteBranch',
                'hasStock' => false,
        ]);
        }
    }

    public function deleteBranch()
    {
        try {
        // Check permission
        if (!auth()->user()->can('branches.delete')) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'You do not have permission to delete branches.']);
            return;
        }

            if (!$this->branchToDelete) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'No branch selected for deletion.']);
                return;
            }

            $branch = Branch::findOrFail($this->branchToDelete);

            // Check if branch has users
            $userCount = \App\Models\User::where('branch_id', $branch->id)->count();
            if ($userCount > 0) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'Cannot delete branch. Please reassign all users first.']);
                return;
            }

            // Check if branch has warehouses with stock
            $stockCount = $this->getBranchStockCount($branch);
            if ($stockCount > 0) {
                $this->dispatch('notify', ['type' => 'error', 'message' => "Cannot delete branch. It has {$stockCount} items in stock. Please transfer items first or use the transfer option."]);
                return;
            }

            // Check for other dependencies
            $this->checkBranchDependencies($branch);

            $branchName = $branch->name;
            $branch->delete();
            
            $this->dispatch('notify', ['type' => 'success', 'message' => "Branch '{$branchName}' deleted successfully."]);
            
            // Dispatch event to close modal
            $this->dispatch('branchDeleted');
            $this->branchToDelete = null;
            
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Error deleting branch: ' . $e->getMessage()]);
        }
    }

    public function showTransferOptions()
    {
        if (!$this->branchToDelete) return;

        $branch = Branch::find($this->branchToDelete);
        if (!$branch) return;

        $stockCount = $this->getBranchStockCount($branch);
        if ($stockCount === 0) {
            // No stock to transfer, proceed with normal deletion
            $this->deleteBranch();
            return;
        }

        // Get available target branches
        $this->targetBranches = Branch::where('id', '!=', $this->branchToDelete)
            ->where('is_active', true)
            ->with('warehouses')
            ->get()
            ->filter(function($branch) {
                return $branch->warehouses->count() > 0;
            });

        if ($this->targetBranches->isEmpty()) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'No other branches with warehouses available for stock transfer.']);
            return;
        }

        $this->showTransferModal = true;
        $this->dispatch('showTransferModal');
    }

    public function transferAndDelete($data)
    {
        $targetBranchId = $data['targetBranchId'];
        
        try {
            if (!$this->branchToDelete || !$targetBranchId) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'Missing branch information for transfer.']);
                return;
            }

            $sourceBranch = Branch::with('warehouses.stocks.item')->findOrFail($this->branchToDelete);
            $targetBranch = Branch::with('warehouses')->findOrFail($targetBranchId);

            if ($targetBranch->warehouses->isEmpty()) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'Target branch has no warehouses for stock transfer.']);
                return;
            }

            // Get the first warehouse in target branch for transfers
            $targetWarehouse = $targetBranch->warehouses->first();

            \DB::beginTransaction();

            $transferredItems = 0;

            // Transfer all stock from source branch warehouses to target branch
            foreach ($sourceBranch->warehouses as $sourceWarehouse) {
                foreach ($sourceWarehouse->stocks as $stock) {
                    if ($stock->quantity > 0) {
                        // Find or create stock in target warehouse
                        $targetStock = \App\Models\Stock::firstOrCreate(
                            [
                                'warehouse_id' => $targetWarehouse->id,
                                'item_id' => $stock->item_id,
                            ],
                            [
                                'quantity' => 0,
                            ]
                        );

                        // Transfer the stock
                        $targetStock->quantity += $stock->quantity;
                        $targetStock->save();

                        // Record stock history for the transfer
                        \App\Models\StockHistory::create([
                            'item_id' => $stock->item_id,
                            'warehouse_id' => $targetWarehouse->id,
                            'quantity_change' => $stock->quantity,
                            'quantity_before' => $targetStock->quantity - $stock->quantity,
                            'quantity_after' => $targetStock->quantity,
                            'reference_type' => 'branch_deletion_transfer',
                            'reference_id' => $this->branchToDelete,
                            'user_id' => auth()->id(),
                            'description' => "Stock transferred from {$sourceBranch->name} to {$targetBranch->name} before branch deletion",
                        ]);

                        // Delete the source stock
                        $stock->delete();
                        $transferredItems++;
                    }
                }
            }

            // Now delete the branch
            $branchName = $sourceBranch->name;
            $sourceBranch->delete();

            \DB::commit();

            $this->dispatch('notify', ['type' => 'success', 'message' => "Branch '{$branchName}' deleted successfully. {$transferredItems} items transferred to '{$targetBranch->name}'."]);
            
            $this->showTransferModal = false;
            $this->branchToDelete = null;
            $this->dispatch('branchDeleted');
            $this->dispatch('hideTransferModal');

        } catch (\Exception $e) {
            \DB::rollBack();
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Error during transfer and deletion: ' . $e->getMessage()]);
        }
    }

    private function getBranchStockCount(Branch $branch): int
    {
        $totalStock = 0;
        foreach ($branch->warehouses as $warehouse) {
            $totalStock += $warehouse->stocks()->where('quantity', '>', 0)->count();
        }
        return $totalStock;
    }

    private function checkBranchDependencies(Branch $branch): void
    {
        // Check for sales
        $salesCount = \App\Models\Sale::where('branch_id', $branch->id)->count();
        if ($salesCount > 0) {
            throw new \Exception("Branch has {$salesCount} sales records. Cannot delete.");
        }

        // Check for purchases  
        $purchasesCount = \App\Models\Purchase::where('branch_id', $branch->id)->count();
        if ($purchasesCount > 0) {
            throw new \Exception("Branch has {$purchasesCount} purchase records. Cannot delete.");
        }

        // Check for suppliers
        $suppliersCount = \App\Models\Supplier::where('branch_id', $branch->id)->count();
        if ($suppliersCount > 0) {
            throw new \Exception("Branch has {$suppliersCount} suppliers assigned. Please reassign suppliers first.");
        }
    }

    public function render()
    {
        $branches = $this->getBranchesQuery()->paginate($this->perPage);

        return view('livewire.branches.index', [
            'branches' => $branches,
            'activeBranchesCount' => $this->activeBranchesCount,
            'totalWarehousesCount' => $this->totalWarehousesCount,
            'branchUsersCount' => $this->branchUsersCount,
            'targetBranches' => $this->targetBranches ?? collect()
        ]);
    }


}