<?php

namespace App\Livewire\Transfers;

use App\Models\Transfer;
use App\Services\TransferService;
use App\Exceptions\TransferException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Show extends Component
{
    use AuthorizesRequests;

    public Transfer $transfer;
    public $transferItems = [];
    public $showModal = false;
    public $modalAction = '';

    public function mount(Transfer $transfer)
    {
        $this->transfer = $transfer->load([
            'sourceWarehouse', 'destinationWarehouse', 'sourceBranch', 'destinationBranch',
            'user', 'items.item'
        ]);
        
        $this->loadTransferItems();
    }

    protected function loadTransferItems()
    {
        $this->transferItems = $this->transfer->items()->with('item')->get();
    }

    public function getTransferSummaryProperty()
    {
        return [
            'total_items' => $this->transferItems->count(),
            'total_quantity' => $this->transferItems->sum('quantity'),
        ];
    }

    public function getSourceLocationProperty()
    {
        return $this->transfer->source_location_name;
    }

    public function getDestinationLocationProperty()
    {
        return $this->transfer->destination_location_name;
    }

    public function getTransferTypeProperty()
    {
        return $this->transfer->transfer_type_display;
    }

    public function getStatusBadgeClassProperty()
    {
        return match($this->transfer->status) {
            'pending' => 'badge',
            'approved' => 'bg-info',
            'in_transit' => 'bg-primary', 
            'completed' => 'bg-success',
            'rejected' => 'bg-danger',
            'cancelled' => 'bg-secondary',
            default => 'bg-secondary'
        };
    }
    
    public function getStatusBadgeStyleProperty()
    {
        return $this->transfer->status === 'pending' ? 'background-color: #ffc107; color: #000;' : '';
    }

    public function canViewTransfer()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return true;
        }

        if ($user->isBranchManager() && $user->branch_id) {
            return ($this->transfer->source_type === 'branch' && $this->transfer->source_id === $user->branch_id) ||
                   ($this->transfer->destination_type === 'branch' && $this->transfer->destination_id === $user->branch_id);
        }

        return false;
    }

    public function printTransfer()
    {
        try {
            $this->dispatch('print-page');
        } catch (\Exception $e) {
            session()->flash('error', 'Error printing transfer: ' . $e->getMessage());
        }
    }
    
    public function showConfirmModal($action)
    {
        $this->modalAction = $action;
        $this->showModal = true;
    }
    
    public function closeModal()
    {
        $this->showModal = false;
        $this->modalAction = '';
    }
    
    public function confirmAction()
    {
        if ($this->modalAction === 'approve') {
            $this->approveTransfer();
        } elseif ($this->modalAction === 'reject') {
            $this->rejectTransfer();
        }
        
        $this->closeModal();
    }
    
    public function approveTransfer()
    {
        try {
            $transferService = new TransferService();
            
            $transferService->processTransferWorkflow($this->transfer, auth()->user(), 'approve');
            $transferService->processTransferWorkflow($this->transfer, auth()->user(), 'complete');
            
            session()->flash('success', 'Transfer approved and completed successfully.');
            $this->transfer->refresh();
        } catch (TransferException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to approve transfer: ' . $e->getMessage());
        }
    }
    
    public function rejectTransfer()
    {
        try {
            $transferService = new TransferService();
            
            $transferService->processTransferWorkflow($this->transfer, auth()->user(), 'reject');
            
            session()->flash('success', 'Transfer rejected successfully.');
            $this->transfer->refresh();
        } catch (TransferException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reject transfer: ' . $e->getMessage());
        }
    }
    
    public function canApproveTransfer()
    {
        $user = auth()->user();
        
        if ($this->transfer->status !== 'pending') {
            return false;
        }
        
        return $user->isSuperAdmin() || $user->isGeneralManager() || 
               ($user->isBranchManager() && $user->branch_id === $this->transfer->destination_id);
    }

    public function getStockMovementsProperty()
    {
        $user = auth()->user();
        $query = \App\Models\StockHistory::where('reference_type', 'transfer')
            ->where('reference_id', $this->transfer->id)
            ->with(['item', 'warehouse']);
        
        // Branch isolation: filter stock movements by user's branch
        if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
            if ($user->branch_id) {
                $query->whereHas('warehouse.branches', function($q) use ($user) {
                    $q->where('branches.id', $user->branch_id);
                });
            }
        }
        
        return $query->orderBy('created_at')->get();
    }

    public function render()
    {
        if (!$this->canViewTransfer()) {
            abort(403, 'You do not have permission to view this transfer.');
        }

        return view('livewire.transfers.show');
    }
}