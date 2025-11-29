<?php

namespace App\Livewire\Transfers;

use App\Models\Transfer;
use App\Models\Branch;
use App\Models\Stock;
use App\Models\Item;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $transferDirection = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'transferDirection' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTransferDirection()
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

    /**
     * Get the total count of transfers this month
     */
    public function getThisMonthCountProperty()
    {
        return Transfer::forUser(auth()->user())
            ->whereYear('date_initiated', now()->year)
            ->whereMonth('date_initiated', now()->month)
            ->count();
    }

    /**
     * Get the count of outgoing transfers
     */
    public function getOutgoingTransfersCountProperty()
    {
        $user = auth()->user();
        return Transfer::forUser($user)
            ->where(function($query) use ($user) {
                $query->where(function($q) use ($user) {
                    $q->where('source_type', 'warehouse')
                      ->where('source_id', $user->warehouse_id);
                })->orWhere(function($q) use ($user) {
                    $q->where('source_type', 'branch')
                      ->where('source_id', $user->branch_id);
                });
            })
            ->count();
    }

    /**
     * Get the count of incoming transfers
     */
    public function getIncomingTransfersCountProperty()
    {
        $user = auth()->user();
        return Transfer::forUser($user)
            ->where(function($query) use ($user) {
                $query->where(function($q) use ($user) {
                    $q->where('destination_type', 'warehouse')
                      ->where('destination_id', $user->warehouse_id);
                })->orWhere(function($q) use ($user) {
                    $q->where('destination_type', 'branch')
                      ->where('destination_id', $user->branch_id);
                });
            })
            ->count();
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
     * Get filtered transfers query based on search and filters
     */
    protected function getTransfersQuery()
    {
        return Transfer::query()
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
            ->when($this->transferDirection, function($query) {
                $user = auth()->user();
                if ($this->transferDirection === 'outgoing') {
                    $query->where(function($q) use ($user) {
                        $q->where(function($subQ) use ($user) {
                            $subQ->where('source_type', 'warehouse')
                                 ->where('source_id', $user->warehouse_id);
                        })->orWhere(function($subQ) use ($user) {
                            $subQ->where('source_type', 'branch')
                                 ->where('source_id', $user->branch_id);
                        });
                    });
                } elseif ($this->transferDirection === 'incoming') {
                    $query->where(function($q) use ($user) {
                        $q->where(function($subQ) use ($user) {
                            $subQ->where('destination_type', 'warehouse')
                                 ->where('destination_id', $user->warehouse_id);
                        })->orWhere(function($subQ) use ($user) {
                            $subQ->where('destination_type', 'branch')
                                 ->where('destination_id', $user->branch_id);
                        });
                    });
                }
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
     * Reset all filters
     */
    public function resetFilters()
    {
        $this->search = '';
        $this->transferDirection = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
        session()->flash('success', 'Filters reset successfully.');
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
     * Process transfer approval with stock movement and item creation
     */
    private function processTransferApproval($transfer, $user)
    {
        DB::beginTransaction();
        
        try {
            // Get source and destination warehouses
            $sourceBranch = Branch::with('warehouses')->find($transfer->source_id);
            $destinationBranch = Branch::with('warehouses')->find($transfer->destination_id);
            
            if (!$sourceBranch || !$destinationBranch) {
                throw new \Exception('Source or destination branch not found');
            }
            
            $sourceWarehouses = $sourceBranch->warehouses;
            $destinationWarehouse = $destinationBranch->warehouses->first();
            
            if (!$destinationWarehouse) {
                throw new \Exception('No warehouse found in destination branch');
            }
            
            foreach ($transfer->items as $transferItem) {
                $item = $transferItem->item;
                $quantity = $transferItem->quantity;
                
                // Deduct from source warehouses
                $remainingToDeduct = $quantity;
                foreach ($sourceWarehouses as $warehouse) {
                    if ($remainingToDeduct <= 0) break;
                    
                    $stock = Stock::where('item_id', $item->id)
                        ->where('warehouse_id', $warehouse->id)
                        ->first();
                    
                    if ($stock && $stock->quantity > 0) {
                        $deductAmount = min($remainingToDeduct, $stock->quantity);
                        $stock->decrement('quantity', $deductAmount);
                        $remainingToDeduct -= $deductAmount;
                    }
                }
                
                // Check if item exists in destination branch
                $destinationItem = Item::where('name', $item->name)
                    ->where('sku', $item->sku)
                    ->where(function($q) use ($destinationBranch) {
                        $q->where('branch_id', $destinationBranch->id)
                          ->orWhereNull('branch_id');
                    })
                    ->first();
                
                if (!$destinationItem) {
                    // Create item in destination branch
                    $destinationItem = Item::create([
                        'name' => $item->name,
                        'sku' => $item->sku . '-' . $destinationBranch->id,
                        'barcode' => $item->barcode,
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
                
                // Add to destination warehouse stock
                $destinationStock = Stock::firstOrCreate(
                    [
                        'item_id' => $destinationItem->id,
                        'warehouse_id' => $destinationWarehouse->id,
                    ],
                    ['quantity' => 0]
                );
                
                $destinationStock->increment('quantity', $quantity);
            }
            
            // Update transfer status
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

    public function render()
    {
        $transfers = $this->getTransfersQuery()->paginate($this->perPage);

        return view('livewire.transfers.index', [
            'transfers' => $transfers,
            'thisMonthCount' => $this->thisMonthCount,
            'outgoingTransfersCount' => $this->outgoingTransfersCount,
            'incomingTransfersCount' => $this->incomingTransfersCount,
        ])->title('Stock Transfers');
    }
}