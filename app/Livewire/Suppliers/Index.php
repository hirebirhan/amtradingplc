<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use App\Models\Branch;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination, AuthorizesRequests;

    public $search = '';
    public $branchFilter = '';

    public $perPage = 10;
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $supplierToDelete;

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

    public function clearFilters()
    {
        $this->search = '';
        $this->branchFilter = '';
        $this->resetPage();
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
     * Get the count of active suppliers
     */
    public function getActiveSupplierCountProperty()
    {
        return Supplier::where('is_active', true)->count();
    }

    /**
     * Get the count of suppliers added in the last 30 days
     */
    public function getRecentSuppliersCountProperty()
    {
        return Supplier::where('created_at', '>=', now()->subDays(30))->count();
    }

    /**
     * Get the total purchase amount from suppliers
     */
    public function getTotalPurchasesAmountProperty()
    {
        return DB::table('purchases')
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->sum('purchases.total_amount') ?? 0;
    }



    /**
     * Get suppliers query with filters
     */
    protected function getSuppliersQuery()
    {
        return Supplier::query()
            ->when($this->search, function ($query) {
                $searchTerm = '%' . strtolower(trim($this->search)) . '%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(phone) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(reference_no) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(address) LIKE ?', [$searchTerm]);
                });
            })
            ->when($this->branchFilter && $this->branchFilter !== '', function ($query) {
                $query->where('branch_id', (int) $this->branchFilter);
            })
            ->where('is_active', true)
            ->orderBy($this->sortField, $this->sortDirection);
    }

    public function render()
    {
        $suppliers = $this->getSuppliersQuery()->paginate($this->perPage);
        
        // Get all branches - let users see all options
        $branches = Branch::orderBy('name')->get();

        return view('livewire.suppliers.index', [
            'suppliers' => $suppliers,
            'branches' => $branches,
            'activeSupplierCount' => $this->activeSupplierCount,
            'recentSuppliersCount' => $this->recentSuppliersCount,
            'totalPurchasesAmount' => $this->totalPurchasesAmount
        ])->title('Supplier Management');
    }

    // Set supplier for deletion and open modal
    #[On('setSupplierToDelete')]
    public function setSupplierToDelete($supplierId)
    {
        $this->supplierToDelete = $supplierId;
    }

    // Handle delete request from JS
    #[On('deleteSupplier')]
    public function deleteSupplierById()
    {
        if ($this->supplierToDelete) {
            $supplier = Supplier::find($this->supplierToDelete);
            
            if ($supplier) {
                // Check permissions
                if (!auth()->user()->can('suppliers.delete')) {
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'You do not have permission to delete suppliers.'
                    ]);
                    return;
                }
                
                // Check if supplier has related purchases
                if ($supplier->purchases()->count() > 0) {
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'Cannot delete supplier with related purchases.'
                    ]);
                    return;
                }
                
                $supplier->delete();
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Supplier deleted successfully.'
                ]);
            }
        }
    }

    // Legacy method - kept for compatibility
    public function deleteSupplier(Supplier $supplier)
    {
        // Check permissions
        if (!auth()->user()->can('suppliers.delete')) {
            return $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to delete suppliers.',
            ]);
        }

        // Check if supplier has related purchases
        if ($supplier->purchases()->count() > 0) {
            return $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete supplier with related purchases.',
            ]);
        }

        $supplier->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Supplier deleted successfully.',
        ]);
    }

    public function confirmDelete($supplierId)
    {
        $this->dispatch('confirmSupplierDeletion', $supplierId);
    }

    // Simple delete method called from the template
    public function delete($supplierId)
    {
        $supplier = Supplier::find($supplierId);
        
        if (!$supplier) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Supplier not found.'
            ]);
            return;
        }
        
        // Check permissions
        if (!auth()->user()->can('suppliers.delete')) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to delete suppliers.'
            ]);
            return;
        }
        
        // Check if supplier has related purchases
        if ($supplier->purchases()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete supplier with related purchases.'
            ]);
            return;
        }
        
        $supplier->delete();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Supplier deleted successfully.'
        ]);
    }

    #[On('deleteSupplierConfirmed')]
    public function deleteSupplierConfirmed($data)
    {
        if (isset($data['supplierId'])) {
            $supplier = Supplier::find($data['supplierId']);
            
            if ($supplier) {
                // Check permissions
                if (!auth()->user()->can('suppliers.delete')) {
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'You do not have permission to delete suppliers.'
                    ]);
                    return;
                }
                
                // Check if supplier has related purchases
                if ($supplier->purchases()->count() > 0) {
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'Cannot delete supplier with related purchases.'
                    ]);
                    return;
                }
                
                $supplier->delete();
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Supplier deleted successfully.'
                ]);
                $this->dispatch('supplierDeleted');
            }
        }
    }
}