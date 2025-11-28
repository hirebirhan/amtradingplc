<?php

namespace App\Livewire\Transfers;

use App\Models\Transfer;
use App\Models\TransferItem;
use App\Services\TransferService;
use App\Exceptions\TransferException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\PdfService;
use Livewire\Attributes\On;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Show extends Component
{
    use AuthorizesRequests;

    public Transfer $transfer;
    public $transferItems = [];

    public function mount(Transfer $transfer)
    {
        // Load transfer with all relationships
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

    /**
     * Get transfer summary data
     */
    public function getTransferSummaryProperty()
    {
        return [
            'total_items' => $this->transferItems->count(),
            'total_quantity' => $this->transferItems->sum('quantity'),
        ];
    }

    /**
     * Get source location name
     */
    public function getSourceLocationProperty()
    {
        return $this->transfer->source_location_name;
    }

    /**
     * Get destination location name
     */
    public function getDestinationLocationProperty()
    {
        return $this->transfer->destination_location_name;
    }

    /**
     * Get transfer type display
     */
    public function getTransferTypeProperty()
    {
        return $this->transfer->transfer_type_display;
    }

    /**
     * Get status badge class - all transfers are now completed
     */
    public function getStatusBadgeClassProperty()
    {
        return 'bg-success'; // All transfers are auto-completed
    }

    /**
     * Check if user can view this transfer
     */
    public function canViewTransfer()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return true;
        }

        // Branch managers can view transfers involving their branch
        if ($user->isBranchManager() && $user->branch_id) {
            return ($this->transfer->source_type === 'branch' && $this->transfer->source_id === $user->branch_id) ||
                   ($this->transfer->destination_type === 'branch' && $this->transfer->destination_id === $user->branch_id);
        }

        return false;
    }

    /**
     * Print transfer
     */
    public function printTransfer()
    {
        try {
            // Trigger browser print dialog
            $this->dispatch('print-page');
        } catch (\Exception $e) {
            session()->flash('error', 'Error printing transfer: ' . $e->getMessage());
        }
    }

    /**
     * Get stock movements for this transfer
     */
    public function getStockMovementsProperty()
    {
        return \App\Models\StockHistory::where('reference_type', 'transfer')
            ->where('reference_id', $this->transfer->id)
            ->with(['item', 'warehouse'])
            ->orderBy('created_at')
            ->get();
    }

    public function render()
    {
        // Check permissions
        if (!$this->canViewTransfer()) {
            abort(403, 'You do not have permission to view this transfer.');
        }

        return view('livewire.transfers.show');
    }
}