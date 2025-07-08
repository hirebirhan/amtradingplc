<?php

namespace App\Livewire\StockCard;

use App\Models\Item;
use App\Models\StockHistory;
use App\Models\Warehouse;
use App\Models\Stock;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $itemFilter = '';
    public $typeFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 25;

    // Manual entry form
    public $showAddForm = false;
    public $newEntry = [
        'item_id' => '',
        'warehouse_id' => '',
        'movement_type' => 'in',
        'reference_type' => 'manual',
        'reference_number' => '',
        'quantity' => '',
        'description' => '',
        'date' => '',
    ];

    protected $queryString = [
        'itemFilter' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'perPage' => ['except' => 25],
    ];

    protected $rules = [
        'newEntry.item_id' => 'required|exists:items,id',
        'newEntry.warehouse_id' => 'required|exists:warehouses,id',
        'newEntry.movement_type' => 'required|in:in,out',
        'newEntry.reference_type' => 'required|string',
        'newEntry.reference_number' => 'required|string|max:50',
        'newEntry.quantity' => 'required|numeric|min:0.01',
        'newEntry.description' => 'nullable|string|max:255',
        'newEntry.date' => 'required|date',
    ];

    public function mount()
    {
        // Set default date range to current month
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->newEntry['date'] = now()->format('Y-m-d');
    }

    public function updatingItemFilter()
    {
        $this->resetPage();
        \Log::info('Item filter updated', ['itemFilter' => $this->itemFilter]);
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
        \Log::info('Type filter updated', ['typeFilter' => $this->typeFilter]);
    }

    public function testFiltering()
    {
        \Log::info('Testing filtering', [
            'itemFilter' => $this->itemFilter,
            'typeFilter' => $this->typeFilter,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ]);
        
        $movements = $this->getStockMovements(false)->get();
        \Log::info('Filtered results', [
            'count' => $movements->count(),
            'item_ids' => $movements->pluck('item_id')->unique()->toArray(),
        ]);
        
        $this->dispatch('notify', type: 'info', message: 'Filter test: Found ' . $movements->count() . ' movements for selected filters.');
    }

    public function toggleAddForm()
    {
        $this->showAddForm = !$this->showAddForm;
        if (!$this->showAddForm) {
            $this->resetNewEntry();
        }
    }

    public function resetNewEntry()
    {
        $this->newEntry = [
            'item_id' => '',
            'warehouse_id' => '',
            'movement_type' => 'in',
            'reference_type' => 'manual',
            'reference_number' => '',
            'quantity' => '',
            'description' => '',
            'date' => now()->format('Y-m-d'),
        ];
        $this->resetErrorBag();
    }

    public function generateReferenceNumber()
    {
        $type = $this->newEntry['movement_type'];
        $prefix = $type === 'in' ? 'PO' : 'SO';
        $number = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $this->newEntry['reference_number'] = $prefix . '-' . now()->format('Y') . '-' . $number;
    }

    public function saveEntry()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $item = Item::findOrFail($this->newEntry['item_id']);
            $warehouse = Warehouse::findOrFail($this->newEntry['warehouse_id']);
            
            // Get current stock
            $stock = Stock::where('warehouse_id', $this->newEntry['warehouse_id'])
                ->where('item_id', $this->newEntry['item_id'])
                ->first();

            $currentQuantity = $stock ? $stock->quantity : 0;
            $quantityChange = $this->newEntry['movement_type'] === 'in' 
                ? (float)$this->newEntry['quantity'] 
                : -(float)$this->newEntry['quantity'];

            // Validate sufficient stock for out movements
            if ($this->newEntry['movement_type'] === 'out' && $currentQuantity < $this->newEntry['quantity']) {
                throw new \Exception("Insufficient stock. Available: {$currentQuantity}, Required: {$this->newEntry['quantity']}");
            }

            $newQuantity = $currentQuantity + $quantityChange;

            // Update stock
            if ($stock) {
                $stock->update(['quantity' => $newQuantity]);
            } else {
                Stock::create([
                    'warehouse_id' => $this->newEntry['warehouse_id'],
                    'item_id' => $this->newEntry['item_id'],
                    'quantity' => $newQuantity,
                    'reorder_level' => $item->reorder_level ?? 0,
                ]);
            }

            // Create stock history entry
            StockHistory::create([
                'warehouse_id' => $this->newEntry['warehouse_id'],
                'item_id' => $this->newEntry['item_id'],
                'quantity_before' => $currentQuantity,
                'quantity_after' => $newQuantity,
                'quantity_change' => $quantityChange,
                'reference_type' => $this->newEntry['reference_type'],
                'reference_id' => null, // Manual entry, no reference ID
                'description' => $this->newEntry['description'] . ' (Ref: ' . $this->newEntry['reference_number'] . ')',
                'user_id' => auth()->id(),
                'created_at' => $this->newEntry['date'],
                'updated_at' => now(),
            ]);

            DB::commit();

            session()->flash('success', 'Stock movement recorded successfully!');
            $this->resetNewEntry();
            $this->showAddForm = false;
            $this->resetPage();

        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function clearFilters()
    {
        $this->reset(['itemFilter', 'typeFilter', 'dateFrom', 'dateTo']);
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function exportStockCard()
    {
        $stockMovements = $this->getStockMovements(false)->get();
        
        return response()->streamDownload(function() use ($stockMovements) {
            $output = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($output, [
                'Date',
                'Item Name',
                'Item SKU',
                'Unit',
                'Warehouse',
                'Movement Type',
                'Reference',
                'Quantity In',
                'Quantity Out',
                'Balance',
                'Remarks',
                'User'
            ]);
            
            foreach ($stockMovements as $movement) {
                fputcsv($output, [
                    $movement->created_at->format('Y-m-d H:i:s'),
                    $movement->item->name,
                    $movement->item->sku,
                    $movement->item->unit ?? 'PCS',
                    $movement->warehouse->name ?? 'N/A',
                    ucfirst(str_replace('_', ' ', $movement->reference_type)),
                    $movement->reference_id ? "#{$movement->reference_id}" : 'Manual',
                    $movement->quantity_change > 0 ? number_format($movement->quantity_change, 2) : '',
                    $movement->quantity_change < 0 ? number_format(abs($movement->quantity_change), 2) : '',
                    number_format($movement->quantity_after, 2),
                    $movement->description ?? '',
                    $movement->user->name ?? 'System'
                ]);
            }
            
            fclose($output);
        }, 'stock-card-' . now()->format('Y-m-d') . '.csv');
    }



    private function getStockMovements($paginate = true)
    {
        $query = StockHistory::with(['item', 'warehouse', 'user', 'reference']);
        
        // Debug logging
        \Log::info('Stock Card Filtering', [
            'itemFilter' => $this->itemFilter,
            'typeFilter' => $this->typeFilter,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ]);
        
        // Explicit item filtering - only show movements for the selected item
        if ($this->itemFilter && !empty($this->itemFilter)) {
            $query->where('item_id', '=', $this->itemFilter);
            \Log::info('Applied item filter', ['item_id' => $this->itemFilter]);
        } else {
            // If no item is selected, don't show any movements
            $query->where('item_id', '=', 0); // This will return no results
            \Log::info('No item selected - showing no movements');
        }
        
        if ($this->typeFilter) {
            $query->where('reference_type', $this->typeFilter);
            \Log::info('Applied type filter', ['reference_type' => $this->typeFilter]);
        }
        
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
        
        $query->orderBy('created_at', 'desc')
              ->orderBy('id', 'desc');

        $result = $paginate ? $query->paginate($this->perPage) : $query;
        
        // Debug logging for results
        if ($paginate) {
            \Log::info('Stock movements result', [
                'total' => $result->total(),
                'count' => $result->count(),
                'items' => $result->pluck('item_id')->unique()->toArray(),
                'item_names' => $result->pluck('item.name')->unique()->toArray(),
            ]);
        }
        
        return $result;
    }

    public function verifyItemFilter()
    {
        if (!$this->itemFilter) {
            $this->dispatch('notify', type: 'warning', message: 'No item selected for filtering.');
            return;
        }
        
        $movements = $this->getStockMovements(false)->get();
        $itemIds = $movements->pluck('item_id')->unique();
        $itemNames = $movements->pluck('item.name')->unique();
        
        $message = "Filter verification:\n";
        $message .= "• Selected Item ID: {$this->itemFilter}\n";
        $message .= "• Found movements: {$movements->count()}\n";
        $message .= "• Item IDs in results: " . implode(', ', $itemIds->toArray()) . "\n";
        $message .= "• Item names in results: " . implode(', ', $itemNames->toArray());
        
        $this->dispatch('notify', type: 'info', message: $message);
        
        \Log::info('Item filter verification', [
            'selected_item_id' => $this->itemFilter,
            'found_item_ids' => $itemIds->toArray(),
            'found_item_names' => $itemNames->toArray(),
        ]);
    }

    public function getItemsProperty()
    {
        return Item::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getWarehousesProperty()
    {
        $user = auth()->user();
        
        if (!$user) {
            return collect();
        }
        
        if ($user->isSuperAdmin()) {
            return Warehouse::orderBy('name')->get();
        } elseif ($user->warehouse_id) {
            return Warehouse::where('id', $user->warehouse_id)->get();
        } elseif ($user->branch_id) {
            return Warehouse::whereHas('branches', function($query) use ($user) {
                $query->where('branches.id', $user->branch_id);
            })->orderBy('name')->get();
        }
        
        return collect();
    }


    public function getSelectedItemProperty()
    {
        if (!$this->itemFilter) {
            return null;
        }
        
        return Item::find($this->itemFilter);
    }

    public function render()
    {
        return view('livewire.stock-card.index', [
            'stockMovements' => $this->getStockMovements(),
            'items' => $this->items,
            'warehouses' => $this->warehouses,
            'selectedItem' => $this->selectedItem,
        ])->title('Stock Card');
    }
} 