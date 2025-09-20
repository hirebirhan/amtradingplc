<?php

namespace App\Livewire\Transfers;

use App\Models\Item;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\Stock;
use App\Services\TransferService;
use App\Exceptions\TransferException;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
class Create extends Component
{
    public $form = [
        'source_type' => 'branch',
        'source_id' => '',
        'destination_type' => 'branch', 
        'destination_id' => '',
        'note' => '',
    ];

    public $items = [];
    public $warehouses = [];
    public $branches = [];
    public $itemOptions = [];

    // Available options based on user permissions
    public $availableSourceWarehouses = [];
    public $availableDestinationWarehouses = [];
    public $availableSourceBranches = [];
    public $availableDestinationBranches = [];

    // Item being added
    public $newItem = [
        'item_id' => '',
        'quantity' => 1,
    ];

    // Extra properties for search & editing
    public string $itemSearchTerm = '';
    public ?array $selectedItem = null; // ['id' => int, 'label' => string]
    public ?int $editingItemIndex = null;
    public ?string $duplicateWarning = null;

    // Listeners for Alpine/JS events (keeps signature with other components for consistency)
    protected $listeners = ['itemSelected'];

    public $availableStock = 0;
    public $isSubmitting = false;

    public function mount()
    {
        $this->loadUserPermissions();
        $this->updateAvailableLocations();
    }

    public function updatedFormSourceType()
    {
        $this->form['source_id'] = '';
        $this->form['destination_id'] = '';
        $this->resetItems();
        $this->updateAvailableLocations();
    }

    public function updatedFormDestinationType()
    {
        $this->form['destination_id'] = '';
        $this->updateAvailableLocations();
        $this->validateDifferentLocations();
    }

    public function updatedFormSourceId()
    {
        $this->form['destination_id'] = '';
        $this->resetItems();
        $this->loadAvailableItems();
        $this->validateDifferentLocations();
    }

    public function updatedFormDestinationId()
    {
        $this->validateDifferentLocations();
    }

    public function updatedItemSearchTerm($value)
    {
        // Refresh available items list whenever the search term changes
        $this->loadAvailableItems();
    }

    /**
     * Handle item selection from the dropdown (enhanced with logging)
     */
    public function selectItem($itemId)
    {
        try {
            Log::info('Transfer Create: Item selection initiated', [
                'item_id' => $itemId,
                'user_id' => auth()->id(),
                'source_location' => $this->form['source_type'] . ':' . $this->form['source_id']
            ]);

        $this->newItem['item_id'] = (int) $itemId;
        
        // Find selected item in options to get pre-calculated available stock
        $selectedItemOption = collect($this->itemOptions)->firstWhere('id', $itemId);
        $this->availableStock = $selectedItemOption['available_stock'] ?? $this->getAvailableStock($itemId);
            
            // Get item details for enhanced display
            $item = Item::find($itemId);
            if (!$item) {
                throw new \Exception("Item not found: {$itemId}");
            }
        
        $this->selectedItem = [
            'id'    => (int) $itemId,
                'name'  => $item->name,
                'sku'   => $item->sku,
                'label' => $item->name . ' (' . config('app.sku_prefix', 'CODE-') . $item->sku . ')',
                'unit'  => $item->unit ?? 'pcs'
        ];
            
        $this->itemSearchTerm = '';
            $this->duplicateWarning = null;
            
            Log::info('Transfer Create: Item selected successfully', [
                'item_id' => $itemId,
                'item_name' => $item->name,
                'available_stock' => $this->availableStock,
                'user_id' => auth()->id()
            ]);

        } catch (\Exception $e) {
            Log::error('Transfer Create: Item selection failed', [
                'item_id' => $itemId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            $this->addError('newItem.item_id', 'Failed to select item. Please try again.');
        }
    }
    
    /**
     * Clear selected item
     */
    public function clearSelectedItem()
    {
        $this->selectedItem = null;
        $this->newItem['item_id'] = '';
        $this->newItem['quantity'] = 1;
        $this->availableStock = 0;
        $this->duplicateWarning = null;
        
        Log::info('Transfer Create: Item selection cleared', [
            'user_id' => auth()->id()
        ]);
    }
    
    /**
     * Get filtered item options based on search
     */
    public function getFilteredItemOptionsProperty()
    {
        if (empty($this->itemSearchTerm)) {
            return $this->itemOptions;
        }
        
        $searchTerm = strtolower($this->itemSearchTerm);
        return collect($this->itemOptions)->filter(function ($option) use ($searchTerm) {
            return str_contains(strtolower($option['label']), $searchTerm);
        })->take(10)->toArray(); // Limit to 10 results for performance
    }

    /**
     * Begin editing an existing item row
     */
    public function editItem($index)
    {
        if (!isset($this->items[$index])) {
            return;
        }

        $item = $this->items[$index];
        $this->editingItemIndex = $index;
        $this->newItem['item_id'] = $item['item_id'];
        $this->newItem['quantity'] = $item['quantity'];
        $this->availableStock = $item['available_stock'];
        $this->selectedItem = [
            'id'    => $item['item_id'],
            'label' => $item['item_name'] . ' (' . $item['item_sku'] . ')',
        ];
    }

    /**
     * Cancel editing mode and reset item form
     */
    public function cancelEdit()
    {
        $this->editingItemIndex = null;
        $this->resetNewItemForm();
    }

    private function resetNewItemForm()
    {
        $this->newItem = ['item_id' => '', 'quantity' => 1];
        $this->availableStock = 0;
        $this->selectedItem = null;
        $this->itemSearchTerm = '';
        $this->duplicateWarning = null;
        $this->loadAvailableItems();
    }

    private function loadUserPermissions()
    {
        $user = auth()->user();
        // Branch-only mode: restrict to branches the user can access
        $this->warehouses = collect();
        if ($user->isSuperAdmin()) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        } elseif ($user->branch_id) {
            $this->branches = Branch::where('id', $user->branch_id)->where('is_active', true)->get();
        } else {
            $this->branches = collect();
        }
    }

    private function updateAvailableLocations()
    {
        // Branch-only lists
        $this->availableSourceBranches = $this->getUserAccessibleBranches();
        $this->availableSourceWarehouses = collect();
        $this->availableDestinationBranches = $this->branches;
        $this->availableDestinationWarehouses = collect();
    }

    private function getUserAccessibleWarehouses()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            return $this->warehouses;
        } elseif ($user->warehouse_id) {
            return $this->warehouses->where('id', $user->warehouse_id);
        } elseif ($user->branch_id) {
            return $this->warehouses;
        }
        
        return collect();
    }

    private function getUserAccessibleBranches()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            return $this->branches;
        } elseif ($user->branch_id) {
            return $this->branches->where('id', $user->branch_id);
        }
        
        return collect();
    }

    private function validateDifferentLocations()
    {
        if ($this->form['source_type'] === $this->form['destination_type'] && 
            $this->form['source_id'] === $this->form['destination_id'] &&
            !empty($this->form['source_id']) && !empty($this->form['destination_id'])) {
            $this->form['destination_id'] = '';
            session()->flash('error', 'Source and destination locations must be different.');
        }
    }

    private function resetItems()
    {
        $this->items = [];
        $this->itemOptions = [];
        $this->newItem = ['item_id' => '', 'quantity' => 1];
        $this->availableStock = 0;
    }

    private function loadAvailableItems()
    {
        if (empty($this->form['source_id'])) {
            $this->itemOptions = [];
            return;
        }

        $stockMovementService = new \App\Services\StockMovementService();

        // Determine base items query based on location type
        // Branch-only aggregation of stock across a branch
        $branch = Branch::with('warehouses')->find($this->form['source_id']);
        if (!$branch || $branch->warehouses->isEmpty()) {
            $this->itemOptions = [];
            return;
        }
        $warehouseIds = $branch->warehouses->pluck('id');

        $itemsQuery = Item::select('items.id', 'items.name', 'items.sku')
            ->join('stocks', 'items.id', '=', 'stocks.item_id')
            ->whereIn('stocks.warehouse_id', $warehouseIds)
            ->where('items.is_active', true)
            ->groupBy('items.id', 'items.name', 'items.sku')
            ->havingRaw('SUM(stocks.quantity) > 0');

        // Apply search term filter (handle both raw SKU and formatted SKU with prefix)
        if ($this->itemSearchTerm !== '') {
            $term = strtolower($this->itemSearchTerm);
            $prefix = strtolower(config('app.sku_prefix', 'CODE-'));
            $rawTerm = str_starts_with($term, $prefix) ? substr($term, strlen($prefix)) : $term;
            
            $itemsQuery->where(function ($q) use ($term, $rawTerm) {
                $q->whereRaw('LOWER(items.name) LIKE ?', ["%{$term}%"])
                  ->orWhereRaw('LOWER(items.sku) LIKE ?', ["%{$term}%"])
                  ->orWhereRaw('LOWER(items.sku) LIKE ?', ["%{$rawTerm}%"]);
            });
        }

        // Exclude already added item IDs (unless we are editing that same item)
        $excludeIds = collect($this->items)->pluck('item_id')->toArray();
        if ($this->editingItemIndex !== null) {
            $excludeIds = array_diff($excludeIds, [$this->items[$this->editingItemIndex]['item_id']]);
        }
        if (!empty($excludeIds)) {
            $itemsQuery->whereNotIn('items.id', $excludeIds);
        }

        // Get items and filter by actual available stock (considering reservations)
        $items = $itemsQuery->orderBy('items.name')->get();
        
        $this->itemOptions = $items->filter(function ($item) use ($stockMovementService) {
            // Calculate actual available stock (total - reserved)
            $totalStock = $stockMovementService->getAvailableStock(
                $item->id,
                $this->form['source_type'],
                $this->form['source_id']
            );
            
            $reservedStock = $stockMovementService->getReservedStock(
                $item->id,
                $this->form['source_type'],
                $this->form['source_id']
            );
            
            $availableStock = max(0, $totalStock - $reservedStock);
            
            // Only include items with available stock > 0
            return $availableStock > 0;
        })->map(function ($item) use ($stockMovementService) {
            // Calculate available stock for display
            $totalStock = $stockMovementService->getAvailableStock(
                $item->id,
                $this->form['source_type'],
                $this->form['source_id']
            );
            
            $reservedStock = $stockMovementService->getReservedStock(
                $item->id,
                $this->form['source_type'],
                $this->form['source_id']
            );
            
            $availableStock = max(0, $totalStock - $reservedStock);
            
            // Add low stock warning to label
            $stockWarning = $availableStock < 10 ? ' ⚠️' : '';
            
            return [
                'id'    => $item->id,
                'label' => $item->name . ' (' . config('app.sku_prefix', 'CODE-') . $item->sku . ') - Available: ' . number_format($availableStock, 2) . $stockWarning,
                'available_stock' => $availableStock,
            ];
        })->values()->toArray();
    }

    private function getAvailableStock($itemId)
    {
        if (empty($this->form['source_id']) || empty($itemId)) {
            return 0;
        }

        $stockMovementService = new \App\Services\StockMovementService();
        
        $totalStock = $stockMovementService->getAvailableStock(
            $itemId,
            $this->form['source_type'],
            $this->form['source_id']
        );
        
        $reservedStock = $stockMovementService->getReservedStock(
            $itemId,
            $this->form['source_type'],
            $this->form['source_id']
        );
        
        return max(0, $totalStock - $reservedStock);
    }

    /**
     * Add item to transfer list (enhanced with comprehensive logging and validation)
     */
    public function addItem()
    {
        try {
            Log::info('Transfer Create: Adding item initiated', [
                'item_id' => $this->newItem['item_id'] ?? null,
                'quantity' => $this->newItem['quantity'] ?? null,
                'user_id' => auth()->id(),
                'current_items_count' => count($this->items)
            ]);

            // Enhanced validation with better error messages
        $this->validate([
            'newItem.item_id' => 'required|exists:items,id',
                'newItem.quantity' => 'required|numeric|min:0.01|max:999999',
        ], [
                'newItem.item_id.required' => '❌ Please select an item first',
                'newItem.item_id.exists' => '❌ Selected item does not exist',
                'newItem.quantity.required' => '❌ Please enter quantity',
                'newItem.quantity.numeric' => '❌ Quantity must be a valid number',
                'newItem.quantity.min' => '❌ Quantity must be greater than 0',
                'newItem.quantity.max' => '❌ Quantity is too large',
        ]);

        $availableStock = $this->getAvailableStock($this->newItem['item_id']);
        if ($this->newItem['quantity'] > $availableStock) {
                $this->addError('newItem.quantity', "❌ Insufficient stock. Available: " . number_format($availableStock, 2));
                
                Log::warning('Transfer Create: Insufficient stock', [
                    'item_id' => $this->newItem['item_id'],
                    'requested_quantity' => $this->newItem['quantity'],
                    'available_stock' => $availableStock,
                    'user_id' => auth()->id()
                ]);
            return;
        }

        $item = Item::find($this->newItem['item_id']);
        if (!$item) {
                $this->addError('newItem.item_id', '❌ Item not found in database');
                Log::error('Transfer Create: Item not found', [
                    'item_id' => $this->newItem['item_id'],
                    'user_id' => auth()->id()
                ]);
            return;
        }

            // DUPLICATE CHECK with user-friendly messaging
        foreach ($this->items as $existingItem) {
            if ($existingItem['item_id'] == $this->newItem['item_id']) {
                    $this->duplicateWarning = "⚠️ '{$item->name}' is already in the transfer list. Edit the existing entry instead.";
                    
                    Log::info('Transfer Create: Duplicate item attempt', [
                        'item_id' => $this->newItem['item_id'],
                        'item_name' => $item->name,
                        'user_id' => auth()->id()
                    ]);
                return;
            }
        }

            // ADD NEW ITEM with enhanced data
        $this->items[] = [
            'item_id'        => $item->id,
            'item_name'      => $item->name,
            'item_sku'       => $item->sku,
            'quantity'       => $this->newItem['quantity'],
            'available_stock'=> $availableStock,
                'unit'           => $item->unit ?? 'pcs',
                'added_at'       => now()->toISOString()
            ];

            // Success feedback
            $this->dispatch('showNotification', [
                'type' => 'success',
                'message' => "✅ {$item->name} added to transfer (Qty: " . number_format($this->newItem['quantity'], 2) . ")"
            ]);

            Log::info('Transfer Create: Item added successfully', [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'quantity' => $this->newItem['quantity'],
                'total_items' => count($this->items),
                'user_id' => auth()->id()
            ]);

            $this->resetNewItemForm();

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Transfer Create: Validation failed during add item', [
                'errors' => $e->errors(),
                'user_id' => auth()->id()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Transfer Create: Unexpected error during add item', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            $this->addError('general', '❌ An unexpected error occurred. Please try again.');
        }
    }
    
    /**
     * Update existing item (for edit mode)
     */
    public function updateExistingItem()
    {
        if ($this->editingItemIndex === null) {
            return;
        }
        
        try {
            $this->validate([
                'newItem.quantity' => 'required|numeric|min:0.01|max:999999',
            ], [
                'newItem.quantity.required' => '❌ Please enter quantity',
                'newItem.quantity.numeric' => '❌ Quantity must be a valid number',
                'newItem.quantity.min' => '❌ Quantity must be greater than 0',
                'newItem.quantity.max' => '❌ Quantity is too large',
            ]);

            $availableStock = $this->getAvailableStock($this->newItem['item_id']);
            if ($this->newItem['quantity'] > $availableStock) {
                $this->addError('newItem.quantity', "❌ Insufficient stock. Available: " . number_format($availableStock, 2));
                return;
            }

            $oldQuantity = $this->items[$this->editingItemIndex]['quantity'];
            $this->items[$this->editingItemIndex]['quantity'] = $this->newItem['quantity'];
            $this->items[$this->editingItemIndex]['available_stock'] = $availableStock;
            
            $itemName = $this->items[$this->editingItemIndex]['item_name'];
            
            Log::info('Transfer Create: Item updated', [
                'item_id' => $this->newItem['item_id'],
                'item_name' => $itemName,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $this->newItem['quantity'],
                'user_id' => auth()->id()
            ]);
            
            $this->dispatch('showNotification', [
                'type' => 'success',
                'message' => "✅ {$itemName} quantity updated to " . number_format($this->newItem['quantity'], 2)
            ]);
            
            $this->editingItemIndex = null;
            $this->resetNewItemForm();
            
        } catch (\Exception $e) {
            Log::error('Transfer Create: Error updating item', [
                'error' => $e->getMessage(),
                'editing_index' => $this->editingItemIndex,
                'user_id' => auth()->id()
            ]);
            
            $this->addError('general', '❌ Failed to update item. Please try again.');
        }
    }
    
    /**
     * Clear all items from transfer
     */
    public function clearCart()
    {
        $itemCount = count($this->items);
        $this->items = [];
        $this->resetNewItemForm();
        
        Log::info('Transfer Create: Cart cleared', [
            'items_removed' => $itemCount,
            'user_id' => auth()->id()
        ]);
        
        $this->dispatch('showNotification', [
            'type' => 'info',
            'message' => "🗑️ Cleared {$itemCount} items from transfer"
        ]);
    }

    /**
     * Remove item from transfer list (enhanced with logging)
     */
    public function removeItem($index)
    {
        if (isset($this->items[$index])) {
            $removedItem = $this->items[$index];
            
            Log::info('Transfer Create: Item removed', [
                'item_id' => $removedItem['item_id'],
                'item_name' => $removedItem['item_name'],
                'quantity' => $removedItem['quantity'],
                'user_id' => auth()->id()
            ]);
            
            unset($this->items[$index]);
            $this->items = array_values($this->items); // Re-index array
            
            $this->dispatch('showNotification', [
                'type' => 'info',
                'message' => "🗑️ {$removedItem['item_name']} removed from transfer"
            ]);
            
            // Refresh available items to include the removed item again
            $this->loadAvailableItems();
        }
    }

    /**
     * Save transfer with comprehensive logging and error handling
     */
    public function save()
    {
        if ($this->isSubmitting) {
            Log::warning('Transfer Create: Duplicate save attempt blocked', [
                'user_id' => auth()->id()
            ]);
            return;
        }

        $this->isSubmitting = true;
        $startTime = microtime(true);

        try {
            Log::info('Transfer Create: Save process initiated', [
                'user_id' => auth()->id(),
                'source_location' => $this->form['source_type'] . ':' . $this->form['source_id'],
                'destination_location' => $this->form['destination_type'] . ':' . $this->form['destination_id'],
                'items_count' => count($this->items),
                'total_quantity' => collect($this->items)->sum('quantity')
            ]);

            // Clear any previous errors
            $this->resetErrorBag();

            // Enhanced validation with production-grade error messages
            $this->validate([
                'form.source_type' => 'required|in:branch',
                'form.source_id' => 'required|integer|min:1',
                'form.destination_type' => 'required|in:branch', 
                'form.destination_id' => 'required|integer|min:1',
                'form.note' => 'nullable|string|max:1000',
                'items' => 'required|array|min:1|max:100', // Limit max items for performance
            ], [
                'form.source_id.required' => '❌ Please select a source location',
                'form.destination_id.required' => '❌ Please select a destination location',
                'items.required' => '❌ Please add at least one item to transfer',
                'items.min' => '❌ Transfer must contain at least one item',
                'items.max' => '❌ Transfer cannot contain more than 100 items',
                'form.note.max' => '❌ Notes cannot exceed 1000 characters',
            ]);

            // Validate that source and destination are different
            if ($this->form['source_type'] === $this->form['destination_type'] && 
                $this->form['source_id'] === $this->form['destination_id']) {
                $this->addError('form.destination_id', '❌ Source and destination must be different locations');
                Log::warning('Transfer Create: Same source and destination selected', [
                    'user_id' => auth()->id(),
                    'location' => $this->form['source_type'] . ':' . $this->form['source_id']
                ]);
                return;
            }

            // Validate each item has sufficient stock (final check)
            $stockValidationErrors = [];
            foreach ($this->items as $index => $item) {
                $currentStock = $this->getAvailableStock($item['item_id']);
                if ($item['quantity'] > $currentStock) {
                    $stockValidationErrors[] = "Item '{$item['item_name']}': requested {$item['quantity']}, available {$currentStock}";
                }
            }
            
            if (!empty($stockValidationErrors)) {
                $this->addError('general', '❌ Insufficient stock: ' . implode('; ', $stockValidationErrors));
                Log::warning('Transfer Create: Stock validation failed', [
                    'user_id' => auth()->id(),
                    'stock_errors' => $stockValidationErrors
                ]);
                return;
            }

            $user = auth()->user();

            // Comprehensive service availability check
            if (!class_exists('App\Services\TransferService')) {
                Log::critical('Transfer Create: TransferService class not found', [
                    'user_id' => auth()->id()
                ]);
                $this->addError('general', '❌ Transfer service is not available. Please contact system administrator.');
                return;
            }

            $transferService = new TransferService();

            // Create transfer with enhanced error tracking
            $transferData = [
                'source_type' => 'branch',
                'source_id' => (int)$this->form['source_id'],
                'destination_type' => 'branch',
                'destination_id' => (int)$this->form['destination_id'],
                'note' => $this->form['note'],
                'created_via' => 'web_form',
                'user_agent' => request()->header('User-Agent'),
                'ip_address' => request()->ip()
            ];

            Log::info('Transfer Create: Creating transfer via service', [
                'user_id' => auth()->id(),
                'transfer_data' => $transferData,
                'items_data' => $this->items
            ]);

            $transfer = $transferService->createTransfer($transferData, $this->items, $user);

            Log::info('Transfer Create: Transfer created, processing workflow', [
                'user_id' => auth()->id(),
                'transfer_id' => $transfer->id,
                'reference_code' => $transfer->reference_code
            ]);

            // Auto-complete all transfers - no approval workflow needed
                $transferService->processTransferWorkflow($transfer, $user, 'approve');
                $transferService->processTransferWorkflow($transfer, $user, 'complete');
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Enhanced success message with stock movement confirmation
            $successMessage = "Transfer {$transfer->reference_code} completed successfully! 🎉<br/>" .
                             "✅ Stock deducted from {$this->sourceLocationName}<br/>" .
                             "✅ Stock added to {$this->destinationLocationName}<br/>" .
                             "✅ Inventory levels updated in real-time";

            Log::info('Transfer Create: Transfer completed successfully', [
                'user_id' => auth()->id(),
                'transfer_id' => $transfer->id,
                'reference_code' => $transfer->reference_code,
                'execution_time_ms' => $executionTime,
                'items_transferred' => count($this->items),
                'total_quantity' => collect($this->items)->sum('quantity')
            ]);

            // Verify stock movements were executed
            $this->logStockMovementVerification($transfer);

            // Use Livewire's session flash for immediate feedback
            session()->flash('success', $successMessage);
            
            // Dispatch success event for cleanup
            $this->dispatch('transferCreated', ['transfer_id' => $transfer->id]);
            
            // Close the modal and redirect
            $this->dispatch('closeModal');
            return $this->redirect(route('admin.transfers.show', $transfer), navigate: true);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->isSubmitting = false;
            
            Log::warning('Transfer Create: Validation failed', [
                'user_id' => auth()->id(),
                'validation_errors' => $e->errors(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);
            
            throw $e; // Re-throw validation exceptions to show field errors
            
        } catch (TransferException $e) {
            $this->isSubmitting = false;
            
            Log::error('Transfer Create: Transfer service exception', [
                'user_id' => auth()->id(),
                'error_message' => $e->getMessage(),
                'form_data' => $this->form,
                'items' => $this->items,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);
            
            // Use Livewire's addError for immediate feedback
            $this->addError('general', '❌ Transfer failed: ' . $e->getMessage());
            
            // Show user notification
            $this->dispatch('showNotification', [
                'type' => 'danger',
                'message' => '❌ Transfer failed: ' . $e->getMessage()
            ]);
            
        } catch (\Exception $e) {
            $this->isSubmitting = false;
            
            Log::error('Transfer Create: Unexpected error', [
                'user_id' => auth()->id(),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'form_data' => $this->form,
                'items' => $this->items,
                'stack_trace' => $e->getTraceAsString(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);
            
            // Show user-friendly error message
            $this->addError('general', '❌ An unexpected error occurred. Please try again or contact support if the problem persists.');
            
            // Show user notification
            $this->dispatch('showNotification', [
                'type' => 'danger',
                'message' => '❌ An unexpected error occurred. Please try again.'
            ]);
            
        } finally {
            // Ensure isSubmitting is always reset
            $this->isSubmitting = false;
            
            Log::info('Transfer Create: Save process completed', [
                'user_id' => auth()->id(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'was_successful' => !$this->getErrorBag()->any()
            ]);
        }
    }

    /**
     * Computed: Human-readable source location name
     */
    public function getSourceLocationNameProperty(): string
    {
        if (empty($this->form['source_id'])) {
            return '-';
        }
        return $this->form['source_type'] === 'warehouse'
            ? (Warehouse::find($this->form['source_id'])->name ?? '-')
            : (Branch::find($this->form['source_id'])->name ?? '-');
    }

    /**
     * Computed: Human-readable destination location name
     */
    public function getDestinationLocationNameProperty(): string
    {
        if (empty($this->form['destination_id'])) {
            return '-';
        }
        return $this->form['destination_type'] === 'warehouse'
            ? (Warehouse::find($this->form['destination_id'])->name ?? '-')
            : (Branch::find($this->form['destination_id'])->name ?? '-');
    }

    /**
     * Verify and log stock movements after transfer completion
     */
    private function logStockMovementVerification(Transfer $transfer)
    {
        try {
            // Check if stock history was created for this transfer
            $stockHistoryCount = \App\Models\StockHistory::where('reference_type', 'transfer')
                ->where('reference_id', $transfer->id)
                ->count();

            // Check if reservations were released
            $activeReservations = \App\Models\StockReservation::where('reference_type', 'transfer')
                ->where('reference_id', $transfer->id)
                ->where('expires_at', '>', now())
                ->count();

            // Get detailed stock movements
            $stockMovements = \App\Models\StockHistory::where('reference_type', 'transfer')
                ->where('reference_id', $transfer->id)
                ->with(['item', 'warehouse'])
                ->get();

            Log::info('Transfer Create: Stock Movement Verification', [
                'transfer_id' => $transfer->id,
                'reference_code' => $transfer->reference_code,
                'stock_history_records' => $stockHistoryCount,
                'active_reservations_remaining' => $activeReservations,
                'expected_history_records' => count($this->items) * 2, // Source removal + destination addition
                'stock_movements' => $stockMovements->map(function ($movement) {
                    return [
                        'item' => $movement->item->name,
                        'warehouse' => $movement->warehouse->name,
                        'change' => $movement->quantity_change,
                        'before' => $movement->quantity_before,
                        'after' => $movement->quantity_after,
                        'description' => $movement->description
                    ];
                })->toArray(),
                'user_id' => auth()->id()
            ]);

            // Verify each item was properly moved
            foreach ($this->items as $item) {
                $sourceMovement = $stockMovements->where('quantity_change', '<', 0)
                    ->where('item_id', $item['item_id'])
                    ->first();
                
                $destinationMovement = $stockMovements->where('quantity_change', '>', 0)
                    ->where('item_id', $item['item_id'])
                    ->first();

                Log::info('Transfer Create: Item Movement Verification', [
                    'transfer_id' => $transfer->id,
                    'item_id' => $item['item_id'],
                    'item_name' => $item['item_name'],
                    'requested_quantity' => $item['quantity'],
                    'source_movement_found' => $sourceMovement ? true : false,
                    'destination_movement_found' => $destinationMovement ? true : false,
                    'source_quantity_removed' => $sourceMovement ? abs($sourceMovement->quantity_change) : 0,
                    'destination_quantity_added' => $destinationMovement ? $destinationMovement->quantity_change : 0,
                    'movement_matches_request' => $sourceMovement && $destinationMovement && 
                        abs($sourceMovement->quantity_change) == $item['quantity'] && 
                        $destinationMovement->quantity_change == $item['quantity']
                ]);
            }

            if ($stockHistoryCount === 0) {
                Log::error('Transfer Create: No stock movements recorded!', [
                    'transfer_id' => $transfer->id,
                    'reference_code' => $transfer->reference_code,
                    'this_indicates' => 'Stock was NOT moved between locations'
                ]);
            } elseif ($stockHistoryCount < count($this->items) * 2) {
                Log::warning('Transfer Create: Incomplete stock movements', [
                    'transfer_id' => $transfer->id,
                    'reference_code' => $transfer->reference_code,
                    'expected_movements' => count($this->items) * 2,
                    'actual_movements' => $stockHistoryCount
                ]);
            } else {
                Log::info('Transfer Create: Stock movements verified successfully', [
                    'transfer_id' => $transfer->id,
                    'reference_code' => $transfer->reference_code,
                    'all_items_moved' => true,
                    'reservations_cleaned' => $activeReservations === 0
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Transfer Create: Stock movement verification failed', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.transfers.create');
    }
}
