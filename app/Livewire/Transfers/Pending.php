<?php

namespace App\Livewire\Transfers;

use App\Models\Transfer;
use App\Models\Branch;
use App\Models\Stock;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

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
    public $selectedTransfer = null;

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

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    /**
     * Get pending transfers query
     */
    protected function getPendingTransfersQuery()
    {
        return Transfer::query()
            ->where('status', 'pending')
            ->forUser(auth()->user())
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('reference_code', 'like', '%' . $this->search . '%')
                      ->orWhereHas('sourceWarehouse', function($subQ) {
                          $subQ->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('destinationWarehouse', function($subQ) {
                          $subQ->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('sourceBranch', function($subQ) {
                          $subQ->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('destinationBranch', function($subQ) {
                          $subQ->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->dateFrom, function($query) {
                $query->whereDate('date_initiated', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function($query) {
                $query->whereDate('date_initiated', '<=', $this->dateTo);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->with(['sourceWarehouse', 'destinationWarehouse', 'sourceBranch', 'destinationBranch', 'user', 'items']);
    }

    /**
     * Show confirmation modal
     */
    public function showConfirmModal($transferId, $action)
    {
        $this->selectedTransfer = Transfer::findOrFail($transferId);
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
        $this->selectedTransfer = null;
    }
    
    /**
     * Confirm action
     */
    public function confirmAction()
    {
        if ($this->modalAction === 'approve') {
            $this->approveTransfer($this->selectedTransfer->id);
        } elseif ($this->modalAction === 'reject') {
            $this->rejectTransfer($this->selectedTransfer->id);
        }
        
        $this->closeModal();
    }
    
    /**
     * Approve a pending transfer
     */
    public function approveTransfer($transferId)
    {
        $transfer = Transfer::findOrFail($transferId);
        
        if ($transfer->status !== 'pending') {
            session()->flash('error', 'Only pending transfers can be approved.');
            return;
        }
        
        $user = auth()->user();
        if (!($user->isSuperAdmin() || $user->isGeneralManager() || ($user->isBranchManager() && $user->branch_id === $transfer->destination_id))) {
            session()->flash('error', 'Only the destination branch manager can approve this transfer.');
            return;
        }
        
        $this->processTransferApproval($transfer, $user);
        
        session()->flash('success', 'Transfer approved and completed successfully.');
    }
    
    /**
     * Reject a pending transfer
     */
    public function rejectTransfer($transferId)
    {
        $transfer = Transfer::findOrFail($transferId);
        
        if ($transfer->status !== 'pending') {
            session()->flash('error', 'Only pending transfers can be rejected.');
            return;
        }
        
        $user = auth()->user();
        if (!($user->isSuperAdmin() || $user->isGeneralManager() || ($user->isBranchManager() && $user->branch_id === $transfer->destination_id))) {
            session()->flash('error', 'Only the destination branch manager can reject this transfer.');
            return;
        }
        
        $transfer->update(['status' => 'rejected']);
        
        session()->flash('success', 'Transfer rejected successfully.');
    }

    /**
     * Process transfer approval with proper stock movement
     */
    private function processTransferApproval($transfer, $user)
    {
        DB::beginTransaction();
        
        try {
            $sourceBranch = Branch::with('warehouses')->find($transfer->source_id);
            $destinationBranch = Branch::with('warehouses')->find($transfer->destination_id);
            
            if (!$sourceBranch || !$destinationBranch) {
                throw new \Exception('Source or destination branch not found');
            }
            
            $sourceWarehouses = $sourceBranch->warehouses;
            $destinationWarehouse = $destinationBranch->warehouses->first();
            
            // Create warehouse if none exists in destination branch
            if (!$destinationWarehouse) {
                $destinationWarehouse = Warehouse::create([
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
            $purchase = Purchase::create([
                'reference_no' => 'TRF-' . $transfer->reference_code,
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
                'notes' => 'Items transferred from ' . $sourceBranch->name . ' via transfer #' . $transfer->reference_code,
                'created_by' => $user->id,
            ]);
            
            foreach ($transfer->items as $transferItem) {
                $item = $transferItem->item;
                $quantity = $transferItem->quantity;
                
                // Deduct from source warehouses using proper stock methods
                $remainingToDeduct = $quantity;
                foreach ($sourceWarehouses as $warehouse) {
                    if ($remainingToDeduct <= 0) break;
                    
                    $stock = Stock::where('item_id', $item->id)
                        ->where('warehouse_id', $warehouse->id)
                        ->first();
                    
                    if ($stock && $stock->piece_count > 0) {
                        $deductAmount = min($remainingToDeduct, $stock->piece_count);
                        
                        // Use proper stock deduction method like sales
                        $stock->sellByPiece(
                            $deductAmount,
                            $item->unit_quantity,
                            'transfer',
                            $transfer->id,
                            "Transfer to {$destinationBranch->name}",
                            $user->id
                        );
                        
                        $remainingToDeduct -= $deductAmount;
                    }
                }
                
                // Find or create item in destination branch
                $destinationItem = Item::where('branch_id', $destinationBranch->id)
                    ->where('name', $item->name)
                    ->first();
                
                if (!$destinationItem) {
                    $destinationItem = Item::create([
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
                
                // Create purchase item record - quantity represents pieces
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'item_id' => $destinationItem->id,
                    'quantity' => $quantity, // Pieces transferred
                    'unit_cost' => $item->cost_price,
                    'subtotal' => $quantity * $item->cost_price,
                ]);
                
                // Add to destination warehouse stock using proper stock methods
                $destinationStock = Stock::firstOrCreate(
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
                    $transfer->id,
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
            
            $transfer->update([
                'status' => 'completed',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
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
        
        while (Item::where('sku', $newSku)->exists()) {
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
        if (!Item::where('barcode', $originalBarcode)->exists()) {
            return $originalBarcode;
        }
        
        $baseBarcode = $originalBarcode . 'B' . $branchId;
        $counter = 1;
        $newBarcode = $baseBarcode;
        
        while (Item::where('barcode', $newBarcode)->exists()) {
            $newBarcode = $baseBarcode . $counter;
            $counter++;
        }
        
        return $newBarcode;
    }

    public function render()
    {
        $transfers = $this->getPendingTransfersQuery()->paginate($this->perPage);

        return view('livewire.transfers.pending', [
            'transfers' => $transfers,
        ])->title('Pending Transfers');
    }
}