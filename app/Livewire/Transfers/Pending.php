<?php

namespace App\Livewire\Transfers;

use App\Models\Transfer;
use App\Services\TransferService;
use App\Exceptions\TransferException;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Pending extends Component
{
    use WithPagination;

    public $search = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;
    
    public $showModal = false;
    public $modalAction = '';
    public $selectedTransferId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
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

    public function resetFilters()
    {
        $this->search = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    protected function getPendingTransfersQuery()
    {
        $user = auth()->user();
        $query = Transfer::query()->where('status', 'pending');
            
        if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
            if ($user->branch_id) {
                $query->where(function ($q) use ($user) {
                    $q->where(function ($sq) use ($user) {
                        $sq->where('source_type', 'branch')->where('source_id', $user->branch_id);
                    })->orWhere(function ($sq) use ($user) {
                        $sq->where('destination_type', 'branch')->where('destination_id', $user->branch_id);
                    });
                });
            }
        }
        
        return $query
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('reference_code', 'like', '%' . $this->search . '%')
                      ->orWhereHas('sourceWarehouse', fn($subQ) => $subQ->where('name', 'like', '%' . $this->search . '%'))
                      ->orWhereHas('destinationWarehouse', fn($subQ) => $subQ->where('name', 'like', '%' . $this->search . '%'))
                      ->orWhereHas('sourceBranch', fn($subQ) => $subQ->where('name', 'like', '%' . $this->search . '%'))
                      ->orWhereHas('destinationBranch', fn($subQ) => $subQ->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->when($this->dateFrom, fn($query) => $query->whereDate('date_initiated', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($query) => $query->whereDate('date_initiated', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection)
            ->withCount('items')
            ->with(['sourceWarehouse', 'destinationWarehouse', 'sourceBranch', 'destinationBranch', 'user']);
    }

    public function showConfirmModal($transferId, $action)
    {
        $this->selectedTransferId = $transferId;
        $this->modalAction = $action;
        $this->showModal = true;
    }
    
    public function closeModal()
    {
        $this->showModal = false;
        $this->modalAction = '';
        $this->selectedTransferId = null;
    }
    
    public function confirmAction()
    {
        if (!$this->selectedTransferId) {
            $this->closeModal();
            return;
        }
        
        if ($this->modalAction === 'approve') {
            $this->approveTransfer($this->selectedTransferId);
        } elseif ($this->modalAction === 'reject') {
            $this->rejectTransfer($this->selectedTransferId);
        } elseif ($this->modalAction === 'cancel') {
            $this->cancelTransfer($this->selectedTransferId);
        }
        
        $this->closeModal();
    }
    
    public function approveTransfer($transferId)
    {
        try {
            $transfer = Transfer::findOrFail($transferId);
            $user = auth()->user();
            
            // Prevent approving own branch's outgoing transfers
            if ($user->isBranchManager() && $user->branch_id && 
                $transfer->source_type === 'branch' && $transfer->source_id === $user->branch_id) {
                session()->flash('error', 'You cannot approve transfers from your own branch.');
                return;
            }
            
            // Check permission
            if (!$this->canApproveTransfer($transfer)) {
                session()->flash('error', 'You do not have permission to approve this transfer.');
                return;
            }
            
            $transferService = new TransferService();
            $transferService->processTransferWorkflow($transfer, auth()->user(), 'approve');
            
            session()->flash('success', "Transfer {$transfer->reference_code} approved successfully.");
        } catch (TransferException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to approve transfer: ' . $e->getMessage());
        }
    }
    
    protected function canApproveTransfer($transfer)
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return true;
        }
        
        if ($user->isBranchManager() && $user->branch_id) {
            // Only the RECEIVING branch can approve (destination)
            return $transfer->destination_type === 'branch' && $transfer->destination_id === $user->branch_id;
        }
        
        return false;
    }
    
    public function rejectTransfer($transferId)
    {
        try {
            $transfer = Transfer::findOrFail($transferId);
            $user = auth()->user();
            
            // Check permission - only receiver can reject
            if (!$this->canRejectTransfer($transfer)) {
                session()->flash('error', 'You do not have permission to reject this transfer.');
                return;
            }
            
            $transferService = new TransferService();
            $transferService->processTransferWorkflow($transfer, auth()->user(), 'reject');
            
            session()->flash('success', "Transfer {$transfer->reference_code} rejected successfully.");
        } catch (TransferException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reject transfer: ' . $e->getMessage());
        }
    }
    
    protected function canRejectTransfer($transfer)
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return true;
        }
        
        if ($user->isBranchManager() && $user->branch_id) {
            // Only the RECEIVING branch can reject (destination)
            return $transfer->destination_type === 'branch' && $transfer->destination_id === $user->branch_id;
        }
        
        return false;
    }
    
    public function cancelTransfer($transferId)
    {
        try {
            $transfer = Transfer::findOrFail($transferId);
            $user = auth()->user();
            
            // Check permission - only sender can cancel
            if (!$this->canCancelTransfer($transfer)) {
                session()->flash('error', 'You do not have permission to cancel this transfer.');
                return;
            }
            
            $transferService = new TransferService();
            $transferService->processTransferWorkflow($transfer, auth()->user(), 'cancel');
            
            session()->flash('success', "Transfer {$transfer->reference_code} cancelled successfully.");
        } catch (TransferException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to cancel transfer: ' . $e->getMessage());
        }
    }
    
    protected function canCancelTransfer($transfer)
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return true;
        }
        
        if ($user->isBranchManager() && $user->branch_id) {
            // Only the SENDING branch can cancel (source)
            return $transfer->source_type === 'branch' && $transfer->source_id === $user->branch_id;
        }
        
        return false;
    }

    public function render()
    {
        $transfers = $this->getPendingTransfersQuery()->paginate($this->perPage);
        
        $selectedTransfer = null;
        if ($this->selectedTransferId) {
            $selectedTransfer = Transfer::withCount('items')
                ->with(['sourceWarehouse', 'destinationWarehouse', 'sourceBranch', 'destinationBranch'])
                ->find($this->selectedTransferId);
        }

        return view('livewire.transfers.pending', [
            'transfers' => $transfers,
            'selectedTransfer' => $selectedTransfer,
        ])->title('Pending Transfers');
    }
}