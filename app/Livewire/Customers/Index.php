<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\Branch;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
use App\Exports\CreditsExport;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $branchFilter = '';
    public $typeFilter = '';

    public $perPage = 10;
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $customerToDelete;

    protected $listeners = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'branchFilter' => ['except' => ''],
        'typeFilter' => ['except' => ''],
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

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }



    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->search = '';
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
     * Validate a customer before deletion to check for dependencies
     */
    public function validateDelete($id)
    {
        $customer = Customer::withCount(['sales'])->findOrFail($id);
        
        $this->dispatch('showDeleteWarnings', 
            customerName: $customer->name,
            customerId: $customer->id,
            hasSales: $customer->sales_count > 0 ? $customer->sales_count : false,
            hasBalance: $customer->balance > 0 ? number_format($customer->balance, 2) : false
        );
    }

    /**
     * Export customers to CSV/Excel
     */
    public function exportCustomers()
    {
        $this->authorize('export', Customer::class);
        $this->dispatch('notify', ['type' => 'info', 'message' => 'Your customer export is being generated and will download shortly.']);
        return Excel::download(new CreditsExport, 'customers.xlsx');
    }

    /**
     * Get filtered customers query with branch isolation
     */
    private function getFilteredCustomers($paginate = true)
    {
        $user = auth()->user();
        $query = Customer::query()
            ->withCount(['sales'])
            ->where('is_active', true);
            
        // Apply branch filtering for non-admin users
        if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
            if ($user->branch_id) {
                $query->forBranch($user->branch_id);
            }
        }
        
        $query->when($this->search, function (Builder $query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->branchFilter, function (Builder $query) {
                $query->where('branch_id', $this->branchFilter);
            })
            ->when($this->typeFilter, function (Builder $query) {
                $query->where('customer_type', $this->typeFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection);
            
        return $paginate 
            ? $query->paginate($this->perPage) 
            : $query;
    }

    public function render()
    {
        $customers = $this->getFilteredCustomers();
        $user = auth()->user();
        
        // Calculate total customers count with branch filtering
        $totalQuery = Customer::where('is_active', true);
        if (!$user->isSuperAdmin() && !$user->isGeneralManager() && $user->branch_id) {
            $totalQuery->forBranch($user->branch_id);
        }
        $totalCustomersCount = $totalQuery->count();
        
        // Calculate credit customers count with branch filtering
        $creditQuery = Customer::where('credit_limit', '>', 0);
        if (!$user->isSuperAdmin() && !$user->isGeneralManager() && $user->branch_id) {
            $creditQuery->forBranch($user->branch_id);
        }
        $creditCustomersCount = $creditQuery->count();
        
        // Get branches for filter dropdown based on user role
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            $branches = Branch::orderBy('name')->get();
        } else {
            $branches = collect([]);
        }
        
        return view('livewire.customers.index', [
            'customers' => $customers,
            'branches' => $branches,
            'creditCustomersCount' => $creditCustomersCount
        ])->layout('layouts.app');
    }

    #[On('setCustomerToDelete')]
    public function setCustomerToDelete($customerId)
    {
        $this->customerToDelete = $customerId;
    }

    #[On('deleteConfirmed')]
    public function deleteConfirmed($data = null)
    {
        try {
            // Handle case where data might be passed differently by Livewire
            if (is_array($data) && isset($data['customerId'])) {
                $customerId = $data['customerId'];
            } else if (is_numeric($data)) {
                $customerId = $data;
            } else {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Customer ID not provided for deletion'
                ]);
                return;
            }

            $customer = Customer::find($customerId);

            if (!$customer) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Customer not found'
                ]);
                return;
            }

            // Check if customer has related sales
            if ($customer->sales()->count() > 0) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Cannot delete customer with related sales'
                ]);
                return;
            }

            // Check permissions
            if (!auth()->user()->can('delete', $customer)) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'You are not authorized to delete this customer.'
                ]);
                return;
            }

            $customerName = $customer->name;
            $customer->delete();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Customer '{$customerName}' deleted successfully."
            ]);
            $this->dispatch('customerDeleted');
            
        } catch (\Exception $e) {
            \Log::error('Error during customer deletion', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId ?? 'unknown',
                'user_id' => auth()->id(),
            ]);
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'An error occurred while deleting the customer.'
            ]);
        }
    }
    
    /**
     * Alternative method to handle delete confirmation
     * Can be called directly from JavaScript
     */
    public function delete($customerId)
    {
        try {
            $customer = Customer::find($customerId);

            if (!$customer) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Customer not found'
                ]);
                $this->dispatch('customerDeleted');
                return;
            }

            // Check if customer has related sales
            if ($customer->sales()->count() > 0) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Cannot delete customer with related sales'
                ]);
                $this->dispatch('customerDeleted');
                return;
            }

            // Check permissions
            if (!auth()->user()->can('delete', $customer)) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'You are not authorized to delete this customer.'
                ]);
                $this->dispatch('customerDeleted');
                return;
            }

            $customerName = $customer->name;
            $customer->delete();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Customer '{$customerName}' deleted successfully."
            ]);
            $this->dispatch('customerDeleted');
            
        } catch (\Exception $e) {
            \Log::error('Error during customer deletion', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'user_id' => auth()->id(),
            ]);
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'An error occurred while deleting the customer.'
            ]);
            $this->dispatch('customerDeleted');
        }
    }
    


    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        $this->search = '';
        $this->branchFilter = '';
        $this->typeFilter = '';
        $this->resetPage();
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'All filters cleared. Showing active customers only.'
        ]);
    }


}