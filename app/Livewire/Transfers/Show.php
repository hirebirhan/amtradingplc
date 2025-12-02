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
    public $showModal = false;
    public $modalAction = '';

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
     * Get status badge class based on transfer status
     */
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
    
    /**
     * Get status badge style for pending status
     */
    public function getStatusBadgeStyleProperty()
    {
        return $this->transfer->status === 'pending' ? 'background-color: #ffc107; color: #000;' : '';
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
     * Show confirmation modal
     */
    public function showConfirmModal($action)
    {
        $this->modalAction = $action;
        $this->showModal = true;
    }
    
    /**
     * Close modal
     */
    public function closeModal()
    {
        $this->showModal = false;
        $this->modalAction = '';
    }
    
    /**
     * Confirm action
     */
    public function confirmAction()
    {
        if ($this->modalAction === 'approve') {
            $this->approveTransfer();
        } elseif ($this->modalAction === 'reject') {
            $this->rejectTransfer();
        }
        
        $this->closeModal();
    }
    
    /**
     * Approve transfer
     */
    public function approveTransfer()
    {
        if ($this->transfer->status !== 'pending') {
            session()->flash('error', 'Only pending transfers can be approved.');
            return;
        }
        
        $user = auth()->user();
        if (!($user->isSuperAdmin() || $user->isGeneralManager() || ($user->isBranchManager() && $user->branch_id === $this->transfer->destination_id))) {
            session()->flash('error', 'Only the destination branch manager can approve this transfer.');
            return;
        }
        
        $this->processTransferApproval($user);
        
        session()->flash('success', 'Transfer approved and completed successfully.');
        $this->transfer->refresh();
    }
    
    /**
     * Reject transfer
     */
    public function rejectTransfer()
    {
        if ($this->transfer->status !== 'pending') {
            session()->flash('error', 'Only pending transfers can be rejected.');
            return;
        }
        
        $user = auth()->user();
        if (!($user->isSuperAdmin() || $user->isGeneralManager() || ($user->isBranchManager() && $user->branch_id === $this->transfer->destination_id))) {
            session()->flash('error', 'Only the destination branch manager can reject this transfer.');
            return;
        }
        
        $this->transfer->update(['status' => 'rejected']);
        
        session()->flash('success', 'Transfer rejected successfully.');
        $this->transfer->refresh();
    }
    
    /**
     * Process transfer approval with proper stock movement
     */
    private function processTransferApproval($user)
    {
        \DB::beginTransaction();
        
        try {
            $sourceBranch = \App\Models\Branch::with('warehouses')->find($this->transfer->source_id);
            $destinationBranch = \App\Models\Branch::with('warehouses')->find($this->transfer->destination_id);
            
            if (!$sourceBranch || !$destinationBranch) {
                throw new \Exception('Source or destination branch not found');
            }
            
            $sourceWarehouses = $sourceBranch->warehouses;
            $destinationWarehouse = $destinationBranch->warehouses->first();
            
            // Create warehouse if none exists in destination branch
            if (!$destinationWarehouse) {
                $destinationWarehouse = \App\Models\Warehouse::create([
                    'name' => $destinationBranch->name . ' Main Warehouse',
                    'code' => 'WH-' . strtoupper(substr($destinationBranch->code ?? $destinationBranch->name, 0, 3)) . '-001',
                    'address' => $destinationBranch->address,
                    'branch_id' => $destinationBranch->id,
                    'created_by' => $user->id,
                ]);
                
                // Attach to branch via pivot table
                $destinationBranch->warehouses()->attach($destinationWarehouse->id);
            }
            
            // Create purchase record for transferred items
            $purchase = \App\Models\Purchase::create([
                'reference_no' => 'TRF-' . $this->transfer->reference_code,
                'branch_id' => $destinationBranch->id,
                'warehouse_id' => $destinationWarehouse->id,
                'user_id' => $user->id,
                'purchase_date' => now(),
                'total_amount' => 0,
                'status' => 'received',
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'paid_amount' => 0,
                'due_amount' => 0,
                'notes' => 'Items transferred from ' . $sourceBranch->name . ' via transfer #' . $this->transfer->reference_code,
                'created_by' => $user->id,
            ]);
            
            foreach ($this->transfer->items as $transferItem) {
                $item = $transferItem->item;
                $quantity = $transferItem->quantity;
                
                // Deduct from source warehouses using proper stock methods
                $remainingToDeduct = $quantity;
                foreach ($sourceWarehouses as $warehouse) {
                    if ($remainingToDeduct <= 0) break;
                    
                    $stock = \App\Models\Stock::where('item_id', $item->id)
                        ->where('warehouse_id', $warehouse->id)
                        ->first();
                    
                    if ($stock && $stock->piece_count > 0) {
                        $deductAmount = min($remainingToDeduct, $stock->piece_count);
                        
                        // Use proper stock deduction method like sales
                        $stock->sellByPiece(
                            $deductAmount,
                            $item->unit_quantity,
                            'transfer',
                            $this->transfer->id,
                            "Transfer to {$destinationBranch->name}",
                            $user->id
                        );
                        
                        $remainingToDeduct -= $deductAmount;
                    }
                }
                
                // Find or create item in destination branch
                $destinationItem = \App\Models\Item::where('branch_id', $destinationBranch->id)
                    ->where('name', $item->name)
                    ->first();
                
                if (!$destinationItem) {
                    $destinationItem = \App\Models\Item::create([
                        'name' => $item->name,
                        'sku' => $this->generateUniqueSku($item->sku, $destinationBranch->id),
                        'barcode' => $this->generateUniqueBarcode($item->barcode, $destinationBranch->id),
                        'category_id' => $item->category_id,
                        'branch_id' => $destinationBranch->id,
                        'cost_price' => $item->cost_price,
                        'selling_price' => $item->selling_price,
                        'unit' => $item->unit,
                        'unit_quantity' => $item->unit_quantity,
                        'item_unit' => $item->item_unit,
                        'reorder_level' => $item->reorder_level,
                        'brand' => $item->brand,
                        'description' => $item->description,
                        'is_active' => true,
                        'created_by' => $user->id,
                    ]);
                }
                
                // Create purchase item record
                \App\Models\PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'item_id' => $destinationItem->id,
                    'quantity' => $quantity,
                    'unit_cost' => $item->cost_price,
                    'subtotal' => $quantity * $item->cost_price,
                ]);
                
                // Add to destination warehouse stock using proper stock methods
                $destinationStock = \App\Models\Stock::firstOrCreate(
                    [
                        'item_id' => $destinationItem->id,
                        'warehouse_id' => $destinationWarehouse->id,
                    ],
                    [
                        'quantity' => 0,
                        'piece_count' => 0,
                        'total_units' => 0,
                        'current_piece_units' => $destinationItem->unit_quantity,
                        'branch_id' => $destinationBranch->id
                    ]
                );
                
                // Use proper stock addition method like purchases
                $destinationStock->addPieces(
                    $quantity,
                    $destinationItem->unit_quantity,
                    'transfer',
                    $this->transfer->id,
                    "Transfer from {$sourceBranch->name}",
                    $user->id
                );
            }
            
            // Update purchase total amount
            $totalAmount = $purchase->purchaseItems()->sum('subtotal');
            $purchase->update([
                'total_amount' => $totalAmount,
                'paid_amount' => $totalAmount,
                'due_amount' => 0,
            ]);
            
            $this->transfer->update([
                'status' => 'completed',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
            
            \DB::commit();
            
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Generate unique SKU for destination branch
     */
    private function generateUniqueSku($originalSku, $branchId)
    {
        $baseSku = $originalSku . '-B' . $branchId;
        $counter = 1;
        $newSku = $baseSku;
        
        while (\App\Models\Item::where('sku', $newSku)->exists()) {
            $newSku = $baseSku . '-' . $counter;
            $counter++;
        }
        
        return $newSku;
    }
    
    /**
     * Generate unique barcode for destination branch
     */
    private function generateUniqueBarcode($originalBarcode, $branchId)
    {
        if (!\App\Models\Item::where('barcode', $originalBarcode)->exists()) {
            return $originalBarcode;
        }
        
        $baseBarcode = $originalBarcode . 'B' . $branchId;
        $counter = 1;
        $newBarcode = $baseBarcode;
        
        while (\App\Models\Item::where('barcode', $newBarcode)->exists()) {
            $newBarcode = $baseBarcode . $counter;
            $counter++;
        }
        
        return $newBarcode;
    }
    
    /**
     * Check if user can approve this transfer
     */
    public function canApproveTransfer()
    {
        $user = auth()->user();
        
        if ($this->transfer->status !== 'pending') {
            return false;
        }
        
        return $user->isSuperAdmin() || $user->isGeneralManager() || 
               ($user->isBranchManager() && $user->branch_id === $this->transfer->destination_id);
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