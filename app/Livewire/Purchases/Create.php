<?php

namespace App\Livewire\Purchases;

use App\Models\BankAccount;
use App\Models\Supplier;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\Stock;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Facades\UserHelperFacade as UserHelper;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
class Create extends Component
{
    protected $listeners = [
        'supplierSelected',
        'itemSelected',
        'setItemAndCost' => 'handleSetItemAndCost',
        'setSupplierManually' => 'setSupplierManually'
    ];

    // Properties to store calculated totals
    public $subtotal = 0;
    public $taxAmount = 0;
    public $totalAmount = 0;

    protected function rules()
    {
        $rules = [
            'form.purchase_date' => 'required|date',
            'form.supplier_id' => 'required|exists:suppliers,id',
            'form.branch_id' => ['required','exists:branches,id'],
            'form.payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'form.tax' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
        ];

        if ($this->form['payment_method'] === PaymentMethod::TELEBIRR->value) {
            $rules['form.transaction_number'] = 'required|string|min:5';
        }

        if ($this->form['payment_method'] === PaymentMethod::BANK_TRANSFER->value) {
            $rules['form.bank_account_id'] = 'required|exists:bank_accounts,id';
        }

        if ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value) {
            $rules['form.advance_amount'] = 'required|numeric|min:0.01|max:' . $this->totalAmount;
        }

        return $rules;
    }

    protected $messages = [
        'form.supplier_id.required' => 'Please select a supplier.',
        'form.branch_id.required' => 'Please select a branch.',
        'items.required' => 'Please add at least one item to the purchase.',
        'items.min' => 'Please add at least one item to the purchase.',
    ];

    public $form = [
        'reference_no' => '',
        'supplier_id' => '',
        'branch_id' => '',
        'purchase_date' => '',
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'transaction_number' => '',
        'bank_name' => '',
        'account_number' => '',
        'bank_account_id' => '',
        'receipt_url' => '',
        'receipt_image' => '',
        'advance_amount' => 0,
        'notes' => '',
        'tax' => 0,
    ];

    public $items = [];
    public $suppliers = [];
    public $branches = [];
    public $bankAccounts = [];

    // Item being added
    public $newItem = [
        'item_id' => '',
        'quantity' => 1,
        'unit_cost' => 0, // Cost per unit (e.g., per kg)
        'cost' => 0, // Calculated cost per piece
        'notes' => '',
        'unit' => '',
    ];

    // Search fields
    public $searchTerm = '';
    public $supplierSearch = '';
    public $itemOptions = [];
    public $selectedItem = null;
    public $selectedSupplier = null;
    public $current_stock = 0;

    // Add missing properties
    public $editingItemIndex = null;
    public $itemSearch = '';

    public function __construct()
    {
        // Enable query logging to debug database issues
        \DB::connection()->enableQueryLog();
    }

    public function mount()
    {
        // Check if user has permission to create purchases
        if (!$this->canCreatePurchases()) {
            abort(403, 'You do not have permission to create purchases.');
        }

        $this->form = [
            'purchase_date' => date('Y-m-d'),
            'reference_no' => $this->getDefaultReferenceNumber(),
            'supplier_id' => '',
            'branch_id' => '',
            'payment_method' => PaymentMethod::defaultForPurchases()->value,
            'payment_status' => PaymentStatus::PAID->value,
            'tax' => 0,
            'transaction_number' => '',
            'bank_account_id' => '',
            'advance_amount' => 0,
            'notes' => '',
        ];

        $this->loadItems();
        $this->suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        // Load accessible branches (branch-only mode)
        $this->branches = $this->getAccessibleBranches();

        $this->bankAccounts = BankAccount::where('is_active', true)->orderBy('account_name')->get();
        
        // Auto-select branch based on user assignment
        $user = auth()->user();
        if ($user && $user->branch_id) {
            $this->form['branch_id'] = $user->branch_id;
        } elseif ($this->branches->count() >= 1) {
            $this->form['branch_id'] = $this->branches->first()->id;
        }

        // Auto-select supplier if supplier_id is provided in URL
        $supplierId = request()->query('supplier_id');
        if ($supplierId) {
            $supplier = Supplier::find($supplierId);
            if ($supplier && $supplier->is_active) {
                $this->form['supplier_id'] = $supplierId;
            }
        }
    }

    /**
     * Check if the current user can create purchases
     */
    private function canCreatePurchases()
    {
        $user = auth()->user();
        
        // SuperAdmin and GeneralManager can create purchases
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        // Sales users can create purchases for their assigned branch
        if ($user->isSales() && $user->branch_id) {
            return true;
        }
        
        // BranchManager can create purchases
        if ($user->isBranchManager()) {
            return true;
        }
        
        // WarehouseUser can create purchases for their assigned warehouse
        if ($user->isWarehouseUser() && $user->warehouse_id) {
            return true;
        }
        
        // Check if user has the purchases.create permission
        return $user->hasPermissionTo('purchases.create');
    }

    /**
     * Get warehouses that the current user can access based on their role and assignment
     */
    private function getAccessibleBranches()
    {
        $user = auth()->user();
        if ($user->isSuperAdmin() || $user->isBranchManager()) {
            return Branch::where('is_active', true)->orderBy('name')->get();
        }
        if ($user->branch_id) {
            return Branch::where('id', $user->branch_id)->where('is_active', true)->get();
        }
        return collect();
    }

    /**
     * Check if the current user can access a specific warehouse
     */
    private function canAccessWarehouse($warehouseId)
    {
        return UserHelper::hasAccessToWarehouse((int)$warehouseId);
    }

    public function loadItems()
    {
        // Get items that are already in the cart
        $addedItemIds = collect($this->items)->pluck('item_id')->toArray();
        
        $this->itemOptions = Item::where('is_active', true)
            ->whereNotIn('id', $addedItemIds) // Exclude items already in cart
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'cost_price' => $item->cost_price ?? 0,
                    'cost_price_per_unit' => $item->cost_price_per_unit ?? 0,
                    'unit' => $item->unit ?? '',
                    'unit_quantity' => $item->unit_quantity ?? 1,
                    'item_unit' => $item->item_unit ?? 'piece',
                    'current_stock' => $this->getItemStock($item->id),
                ];
            });
    }
    
    /**
     * Reload item options for the refresh button
     */
    public function loadItemOptions()
    {
        $this->loadItems();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Items refreshed successfully!'
        ]);
    }

    public function updatedNewItemItemId($value)
    {
        if (!empty($value)) {
            $item = Item::find($value);
            if ($item) {
                // Debug before setting cost
                $this->debugCostValues($item, 'updatedNewItemItemId-before');

                // Set the unit cost and calculate piece cost
                $this->newItem['unit_cost'] = $item->cost_price_per_unit ?? 0;
                $this->newItem['cost'] = ($item->cost_price_per_unit ?? 0) * ($item->unit_quantity ?? 1);

                // Store additional useful information
                $this->newItem['unit'] = $item->unit ?? '';
                $this->current_stock = $this->getItemStock($value);

                // Set the selectedItem property for the view
                $this->selectedItem = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'unit' => $item->unit ?? 'pcs',
                    'unit_quantity' => $item->unit_quantity ?? 1,
                    'item_unit' => $item->item_unit ?? 'piece',
                    'cost_price' => $item->cost_price,
                    'cost_price_per_unit' => $item->cost_price_per_unit ?? 0,
                    'description' => $item->description,
                ];

                // Debug after setting cost
                $this->debugCostValues($item, 'updatedNewItemItemId-after');
                
                \Log::info('Item selected and data set', [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'unit' => $item->unit,
                    'selectedItem' => $this->selectedItem,
                    'newItem' => $this->newItem
                ]);
            }
        } else {
            // Clear selected item when no item is selected
            $this->selectedItem = null;
            $this->newItem['unit'] = '';
            $this->newItem['unit_cost'] = 0;
            $this->newItem['cost'] = 0;
            $this->current_stock = 0;
        }
    }

    // Helper method to get stock for an item
    private function getItemStock($itemId)
    {
        if (empty($itemId)) {
            return 0;
        }
        // Optional: show aggregate stock for the selected branch
        if (!empty($this->form['branch_id'])) {
            $branch = Branch::with('warehouses')->find($this->form['branch_id']);
            if ($branch && $branch->warehouses->isNotEmpty()) {
                return Stock::whereIn('warehouse_id', $branch->warehouses->pluck('id'))
                    ->where('item_id', $itemId)
                    ->sum('quantity');
            }
        }
        return 0;
    }

    // Properly reset item fields without losing important values
    private function resetItemFields()
    {
        // Clear the new item form completely
        $this->newItem = [
            'item_id' => '',     // Reset item selection
            'quantity' => 1,     // Reset to default quantity
            'unit_cost' => 0,    // Reset unit cost to 0
            'cost' => 0,         // Reset cost to 0 (fresh start)
            'notes' => '',       // Reset notes
            'unit' => '',        // Reset unit
        ];
        
        // Clear related properties
        $this->selectedItem = null;
        $this->itemSearch = '';
        $this->current_stock = 0;
        
        // Reset validation errors for item fields
        $this->resetValidation([
            'newItem.item_id',
            'newItem.quantity', 
            'newItem.cost',
            'newItem.notes'
        ]);
        
        // Reload item options to exclude newly added items
        $this->loadItems();
        
        // Dispatch frontend event to reset any UI state
        $this->dispatch('itemFormReset');
        
        // Show success notification when an item has been added
        if (count($this->items) > 0) {
            $lastItem = end($this->items);
            $this->notify("✓ {$lastItem['name']} added to purchase", 'success');
        }
    }

    public function addItem()
    {
        try {
            // Validate item selection first
            if (empty($this->newItem['item_id'])) {
                $this->notify('❌ Please select an item first', 'error');
                return false;
            }

            // Run validation with custom messages
            $this->validate([
                'newItem.item_id' => 'required|exists:items,id',
                'newItem.quantity' => 'required|numeric|min:0.01',
                'newItem.cost' => 'required|numeric|min:0.01',
            ], [
                'newItem.item_id.required' => 'Please select an item',
                'newItem.item_id.exists' => 'Selected item is not valid',
                'newItem.quantity.required' => 'Please enter quantity',
                'newItem.quantity.min' => 'Quantity must be greater than zero',
                'newItem.cost.required' => 'Please enter cost price',
                'newItem.cost.min' => 'Cost must be greater than zero',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Show specific validation errors
            $errors = collect($e->validator->errors()->all());
            foreach ($errors as $error) {
                $this->notify('❌ ' . $error, 'error');
            }
            return false;
        }

        $item = Item::find($this->newItem['item_id']);
        if (!$item) {
            $this->notify('❌ Item not found in database', 'error');
            return false;
        }
        
        // Check if item is already in cart
        $existingIndex = collect($this->items)->search(function ($existingItem) use ($item) {
            return $existingItem['item_id'] == $item->id;
        });

        if ($existingIndex !== false) {
            $this->notify('⚠️ Item already in cart. Use edit to modify quantity or cost.', 'warning');
            return false;
        }
        
        // Convert values to proper floating point numbers
        $cost = round(floatval($this->newItem['cost']), 2);
        $quantity = floatval($this->newItem['quantity']);

        // Calculate subtotal
        $subtotal = $cost * $quantity;

            // Add as a new item
            $this->items[] = [
                'item_id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'unit' => $item->unit ?? '',
            'unit_quantity' => $item->unit_quantity ?? 1,
                'quantity' => $quantity,
                'cost' => $cost,
                'subtotal' => $subtotal,
                'notes' => $this->newItem['notes'] ?? null,
            ];

        // Dispatch event that item was added
        $this->dispatch('itemAdded', [
            'itemId' => $item->id,
            'itemName' => $item->name,
            'quantity' => $quantity,
            'cost' => $cost
        ]);

        // Update totals
        $this->updateTotals();
        
        // Reset form for next item (this will show success message)
        $this->resetItemFields();
        
        return true;
    }

    public function removeItem($index)
    {
        if (!isset($this->items[$index])) {
            $this->notify('❌ Item not found', 'error');
            return;
        }

        $removedItem = $this->items[$index];
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        
        // Update totals
        $this->updateTotals();
        
        // Reload available items to include the removed item
        $this->loadItems();
        
        // Show success message
        $this->notify("✓ {$removedItem['name']} removed from cart", 'success');
    }

    /**
     * Update all totals based on the current items
     */
    private function updateTotals()
    {
        // Quick calculation of subtotal
        $subtotal = 0;
        foreach ($this->items as $item) {
            $subtotal += isset($item['subtotal']) ? floatval($item['subtotal']) : 0;
        }
        
        // Store the subtotal
        $this->subtotal = round($subtotal, 2);
        
        // Calculate tax
        $taxRate = isset($this->form['tax']) ? floatval($this->form['tax']) : 0;
        $this->taxAmount = round($subtotal * ($taxRate / 100), 2);
        
        // Calculate total
        $this->totalAmount = round($subtotal + $this->taxAmount, 2);
        
        // Handle credit with advance
        if ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value) {
            if (!isset($this->form['advance_amount']) || floatval($this->form['advance_amount']) <= 0) {
                $this->form['advance_amount'] = round($this->totalAmount * 0.2, 2); // Default 20%
            } else if (floatval($this->form['advance_amount']) > $this->totalAmount) {
                $this->form['advance_amount'] = $this->totalAmount;
            }
        }
    }

    public function getTotalAmount()
    {
        // If we have a calculated totalAmount, use it
        if (isset($this->totalAmount) && $this->totalAmount > 0) {
            return $this->totalAmount;
        }

        // Otherwise calculate on demand
        $subtotal = collect($this->items)->sum('subtotal');
        $taxAmount = $subtotal * ($this->form['tax'] / 100);

        return $subtotal + $taxAmount;
    }

    public function updatedFormPaymentMethod($value)
    {
        // Reset payment-specific fields when payment method changes
        $this->form['transaction_number'] = '';
        $this->form['bank_name'] = '';
        $this->form['account_number'] = '';
        $this->form['bank_account_id'] = '';
        $this->form['receipt_url'] = '';
        $this->form['receipt_image'] = '';
        $this->form['advance_amount'] = 0;

        // Set payment status based on payment method
        if (in_array($value, [PaymentMethod::CASH->value, PaymentMethod::BANK_TRANSFER->value, PaymentMethod::TELEBIRR->value], true)) {
            // For immediate payment methods, set to paid
            $this->form['payment_status'] = PaymentStatus::PAID->value;
        } else if ($value === PaymentMethod::CREDIT_ADVANCE->value) {
            // For credit with advance, set to partial
            $this->form['payment_status'] = PaymentStatus::PARTIAL->value;

            // Initialize advance amount with a percentage of the total (e.g., 20%)
            if ($this->totalAmount > 0) {
                $this->form['advance_amount'] = round($this->totalAmount * 0.2, 2);
            }
        } else if ($value === PaymentMethod::FULL_CREDIT->value) {
            // For full credit, set to due
            $this->form['payment_status'] = PaymentStatus::DUE->value;
        }

        // Update totals after changing payment method
        $this->updateTotals();
    }

    public function save()
    {
        // Clear any previous validation errors
        $this->resetErrorBag();
        
        // Basic validation first - collect errors instead of throwing exceptions
        $validationErrors = [];
        
        if (empty($this->items) || count($this->items) === 0) {
            $validationErrors['items'] = 'No items found for this purchase. Please add at least one item.';
        }

        if (empty($this->form['supplier_id'])) {
            $validationErrors['form.supplier_id'] = 'Please select a supplier.';
        }

        if (empty($this->form['branch_id'])) {
            $validationErrors['form.branch_id'] = 'Please select a branch.';
        }

        // Check payment method specific validations
        if ($this->form['payment_method'] === PaymentMethod::TELEBIRR->value && empty($this->form['transaction_number'])) {
            $validationErrors['form.transaction_number'] = 'Transaction number is required for Telebirr payments.';
        }

        if ($this->form['payment_method'] === PaymentMethod::BANK_TRANSFER->value && empty($this->form['bank_account_id'])) {
            $validationErrors['form.bank_account_id'] = 'Bank account is required for bank transfer payments.';
        }

        if ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value) {
            if (empty($this->form['advance_amount']) || $this->form['advance_amount'] <= 0) {
                $validationErrors['form.advance_amount'] = 'Advance amount is required and must be greater than zero.';
            } elseif ($this->form['advance_amount'] > $this->totalAmount) {
                $validationErrors['form.advance_amount'] = 'Advance amount cannot exceed the total amount.';
            }
        }

        // If there are validation errors, add them and return false
        if (!empty($validationErrors)) {
            foreach ($validationErrors as $field => $message) {
                $this->addError($field, $message);
            }
            
            $this->notify('❌ Please fix the validation errors before saving.', 'error');
            return false;
        }

        // Validate form data using Laravel validation
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Add validation errors to the component
            foreach ($e->validator->errors()->getMessages() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
            
            $this->notify('❌ Please fix the validation errors before saving.', 'error');
            return false;
        }
        
            // Use our stored reference number which has already been validated for uniqueness
        $referenceNo = $this->generateUniqueReferenceNumber();
            
            // Start a transaction to ensure all related operations succeed or fail together
            \DB::beginTransaction();
            
        try {
            \Log::info('Starting purchase save process', [
                'items_count' => count($this->items),
                'total_amount' => $this->totalAmount,
                'supplier_id' => $this->form['supplier_id'],
                'branch_id' => $this->form['branch_id']
            ]);

            // Create the purchase record
            $branchId = (int)($this->form['branch_id'] ?? 0);
            if ($branchId <= 0) {
                $branchId = auth()->user()->branch_id ?? (Branch::value('id') ?? 1);
            }
            // Resolve internal warehouse for branch
            $warehouseId = $this->resolveWarehouseIdForBranch($branchId);
            
            $purchase = new Purchase();
            $purchase->reference_no = $referenceNo;
            $purchase->user_id = auth()->id();
            $purchase->branch_id = $branchId;
            $purchase->supplier_id = $this->form['supplier_id'];
            $purchase->warehouse_id = $warehouseId; // internal detail
            $purchase->purchase_date = $this->form['purchase_date'];
            $purchase->payment_method = $this->form['payment_method'];
            $purchase->payment_status = $this->form['payment_status'];
            $purchase->status = 'received';
            $purchase->discount = 0;
            $purchase->tax = $this->taxAmount;
            $purchase->total_amount = $this->totalAmount;
            $purchase->paid_amount = $this->form['payment_method'] === PaymentMethod::FULL_CREDIT->value ? 0 : 
                                    ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value ? $this->form['advance_amount'] : $this->totalAmount);
            $purchase->due_amount = $this->form['payment_method'] === PaymentMethod::FULL_CREDIT->value ? $this->totalAmount : 
                                   ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value ? $this->totalAmount - $this->form['advance_amount'] : 0);
            $purchase->notes = $this->form['notes'];
            
            // Handle payment type specific fields
            if ($this->form['payment_method'] === PaymentMethod::BANK_TRANSFER->value) {
                $purchase->bank_account_id = $this->form['bank_account_id'] ?? null;
                // Note: receipt_url field doesn't exist in database schema
            } elseif ($this->form['payment_method'] === PaymentMethod::TELEBIRR->value) {
                $purchase->transaction_number = $this->form['transaction_number'] ?? null;
            }
            
            // Attempt to save the purchase
                if (!$purchase->save()) {
                $this->addError('general', 'Failed to save purchase record. Please try again.');
                $this->notify('❌ Failed to save purchase record.', 'error');
                \DB::rollBack();
                return false;
            }

            \Log::info('Purchase record saved successfully', ['purchase_id' => $purchase->id]);
            
            // Now create the purchase items
            $itemTotal = 0;
            foreach ($this->items as $index => $item) {
                    // Type safety - ensure all values are the correct type
                    $itemId = intval($item['item_id'] ?? 0);
                    $quantity = floatval($item['quantity'] ?? 0);
                    $cost = floatval($item['cost'] ?? 0);
                    $subtotal = floatval($item['subtotal'] ?? ($quantity * $cost));
                    
                    // Skip invalid items
                    if ($itemId <= 0 || $quantity <= 0 || $cost <= 0) {
                        \Log::warning('Skipping invalid item in purchase', [
                            'index' => $index,
                            'item' => $item
                        ]);
                        continue;
                    }
                    
                    // Get the actual Item record to ensure it exists
                    $itemRecord = Item::find($itemId);
                    if (!$itemRecord) {
                        \Log::warning('Item not found, skipping', ['id' => $itemId]);
                        continue;
                    }
                    
                    // Create purchase item
                    $purchaseItem = new PurchaseItem();
                    $purchaseItem->purchase_id = $purchase->id;
                    $purchaseItem->item_id = $itemId;
                    $purchaseItem->quantity = $quantity;
                $purchaseItem->unit_cost = $cost;
                    $purchaseItem->discount = 0;
                    $purchaseItem->subtotal = $subtotal;
                    
                    // Add notes if any
                    if (!empty($item['notes'])) {
                        $purchaseItem->notes = $item['notes'];
                    }
                    
                    if (!$purchaseItem->save()) {
                    $this->addError('items', "Failed to save purchase item {$index}. Please try again.");
                    $this->notify('❌ Failed to save purchase item.', 'error');
                    \DB::rollBack();
                    return false;
                    }
                    
                    // Add to total
                    $itemTotal += $subtotal;
                    
                    // Update stock
                    $this->updateStock($purchase->warehouse_id, $itemId, $quantity, $purchase->id);
                    
                    // Update item cost price automatically
                    if ($cost > 0) {
                        $itemRecord->cost_price = $cost;
                        $itemRecord->save();
                }
            }
            
            // Create credit record for credit-type payments
            if (in_array($this->form['payment_method'], [PaymentMethod::FULL_CREDIT->value, PaymentMethod::CREDIT_ADVANCE->value], true)) {
                $dueAmount = $this->form['payment_method'] === PaymentMethod::FULL_CREDIT->value ? 
                    $this->totalAmount : 
                    ($this->totalAmount - $this->form['advance_amount']);
                
                if ($dueAmount > 0) {
                    // Create credit record
                    $credit = \App\Models\Credit::create([
                        'supplier_id' => $purchase->supplier_id,
                        'amount' => $dueAmount,
                        'paid_amount' => 0,
                        'balance' => $dueAmount,
                        'reference_no' => $purchase->reference_no,
                        'reference_type' => 'purchase',
                        'reference_id' => $purchase->id,
                        'credit_type' => 'payable',
                        'description' => 'Credit for purchase #' . $purchase->reference_no,
                        'credit_date' => $purchase->purchase_date,
                        'due_date' => now()->addDays(30), // Default 30-day term
                        'status' => 'active',
                        'user_id' => auth()->id(),
                        'branch_id' => $branchId,
                        'warehouse_id' => $purchase->warehouse_id,
                    ]);
                    
                    \Log::info('Credit record created', [
                        'credit_id' => $credit->id,
                        'purchase_id' => $purchase->id,
                        'amount' => $dueAmount
                    ]);
                }
            }
            
            // Commit transaction
            \DB::commit();
            
            \Log::info('Purchase saved successfully', [
                'purchase_id' => $purchase->id,
                'reference_no' => $purchase->reference_no,
                'total_amount' => $purchase->total_amount
            ]);

            // Show success message
            $this->notify('✅ Purchase created successfully!', 'success');
            
            // Redirect to purchases index
            return redirect()->route('admin.purchases.index')
                ->with('success', 'Purchase created successfully!');
            
        } catch (\Exception $e) {
            // Roll back transaction on error
            \DB::rollBack();
            
            \Log::error('Error processing purchase', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Add a general error message
            $this->addError('general', 'An unexpected error occurred while saving the purchase. Please try again.');
            $this->notify('❌ Error: ' . $e->getMessage(), 'error');
            
            return false;
        }
    }

    public function cancel()
    {
        // Clear any validation errors before canceling
        $this->resetValidation();
        
        return $this->redirect(route('admin.purchases.index'));
    }

    public function render()
    {
        // Make sure totals are up to date
        $this->updateTotals();

        return view('livewire.purchases.create', [
            'totalAmount' => $this->totalAmount,
            'subtotal' => $this->subtotal,
            'taxAmount' => $this->taxAmount,
        ])->title('Create New Purchase');
    }

    public function selectSupplier($supplierId)
    {
        $supplier = Supplier::find($supplierId);
        if ($supplier) {
            $this->selectedSupplier = [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone
            ];
            $this->form['supplier_id'] = $supplierId;
            $this->supplierSearch = '';
            $this->resetErrorBag('form.supplier_id');
        }
    }

    public function clearSupplier()
    {
        $this->selectedSupplier = null;
        $this->form['supplier_id'] = '';
    }

    public function getFilteredSupplierOptionsProperty()
    {
        if (empty($this->supplierSearch)) {
            return [];
        }

        return $this->suppliers
            ->filter(function ($supplier) {
                return stripos($supplier->name, $this->supplierSearch) !== false ||
                       stripos($supplier->phone, $this->supplierSearch) !== false ||
                       stripos($supplier->email, $this->supplierSearch) !== false;
            })
            ->take(5)
            ->map(function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'phone' => $supplier->phone
                ];
            })
            ->toArray();
    }

    public function supplierSelected($supplier)
    {
        // Log what we're receiving to help debug
        \Log::info('Supplier selected in component', [
            'supplier' => $supplier,
            'type' => gettype($supplier)
        ]);
        
        if (!$supplier) {
            $this->form['supplier_id'] = '';
            return;
        }

        // Handle if we get an array or object
        if (is_array($supplier) && isset($supplier['id'])) {
            // Convert to integer and ensure it's set in the form
            $this->form['supplier_id'] = (int)$supplier['id'];
            $this->selectedSupplier = $supplier;
            
            // Log this event for debugging
            \Log::info('Supplier selected from dropdown', [
                'supplier_id' => $this->form['supplier_id'],
                'supplier_name' => $supplier['name'] ?? 'unknown'
            ]);
            
            // Create or update a hidden input field for direct DOM access
            $this->dispatch('createHiddenSupplierField', [
                'id' => $this->form['supplier_id'],
                'name' => $supplier['name'] ?? 'unknown'
            ]);
        } elseif (is_numeric($supplier)) {
            // Handle case where we just get an ID
            $this->form['supplier_id'] = (int)$supplier;
            
            // Try to get the name from the database
            $supplierModel = \App\Models\Supplier::find($this->form['supplier_id']);
            if ($supplierModel) {
                $this->selectedSupplier = [
                    'id' => $supplierModel->id,
                    'name' => $supplierModel->name,
                    'phone' => $supplierModel->phone
                ];
            }
            
            $supplierName = $supplierModel ? $supplierModel->name : 'unknown';
            
            \Log::info('Supplier selected by ID', [
                'supplier_id' => $this->form['supplier_id'],
                'supplier_name' => $supplierName
            ]);
            
            // Create or update a hidden input field for direct DOM access
            $this->dispatch('createHiddenSupplierField', [
                'id' => $this->form['supplier_id'],
                'name' => $supplierName
            ]);
        }
    }

    public function itemSelected($itemData)
    {
        \Log::info('Item selected from dropdown', [
            'item_data' => $itemData,
            'data_type' => gettype($itemData)
        ]);
        
        // Handle different data structures that might be passed
        if (is_array($itemData)) {
            $itemId = $itemData['id'] ?? null;
            $costPrice = $itemData['cost_price'] ?? 0;
            $costPricePerUnit = $itemData['cost_price_per_unit'] ?? 0;
            $unit = $itemData['unit'] ?? '';
            $currentStock = $itemData['current_stock'] ?? 0;
            $unitQuantity = $itemData['unit_quantity'] ?? 1;
            $itemUnit = $itemData['item_unit'] ?? 'piece';
        } else {
            \Log::warning('Unexpected item data type received', ['type' => gettype($itemData)]);
            return;
        }
        
        if (!$itemId) {
            \Log::warning('No item ID found in item data');
            return;
        }
        
        // Set the item data
        $this->newItem['item_id'] = $itemId;
        $this->newItem['unit_cost'] = $costPricePerUnit; // Set default unit cost
        $this->newItem['cost'] = $costPricePerUnit * $unitQuantity; // Calculate piece cost
        $this->newItem['unit'] = $unit;
        
        // Store current stock for display
        $this->current_stock = $currentStock;
        
        // Store selected item data for display
        $this->selectedItem = [
            'id' => $itemId,
            'name' => $itemData['name'] ?? '',
            'cost_price_per_unit' => $costPricePerUnit,
            'unit_quantity' => $unitQuantity,
            'item_unit' => $itemUnit,
        ];
        
        \Log::info('Item data set successfully', [
            'item_id' => $this->newItem['item_id'],
            'unit_cost' => $this->newItem['unit_cost'],
            'cost' => $this->newItem['cost'],
            'unit' => $this->newItem['unit'],
            'stock' => $this->current_stock
        ]);
    }

    // Add an explicit listener for supplier ID changes
    public function updatedFormSupplierId($value)
    {
        \Log::info('Supplier ID updated in component', [
            'new_value' => $value,
            'parsed_value' => (int)$value
        ]);
        
        // Ensure it's an integer
        $this->form['supplier_id'] = (int)$value;
        
        // Update the selectedSupplier property
        if (!empty($this->form['supplier_id'])) {
            $supplier = $this->suppliers->firstWhere('id', $this->form['supplier_id']);
            if ($supplier) {
                $this->selectedSupplier = [
                    'id' => $supplier->id,
                    'name' => $supplier->name
                ];
            }
        } else {
            $this->selectedSupplier = null;
        }
    }

    // Branch change handler (branch-only mode)
    public function updatedFormBranchId($value)
    {
        \Log::info('Branch ID updated in component', [
            'new_value' => $value,
            'parsed_value' => (int)$value
        ]);
        
        // Ensure it's an integer
        $this->form['branch_id'] = (int)$value;
        
        // If branch has been selected and we have a current item selected
        // update the stock information for that item
        if (!empty($this->form['branch_id']) && !empty($this->newItem['item_id'])) {
            // Get updated stock for the selected item
            $this->current_stock = $this->getItemStock($this->newItem['item_id']);
            
            \Log::debug('Updated stock for item after branch change', [
                'item_id' => $this->newItem['item_id'],
                'branch_id' => $this->form['branch_id'],
                'new_stock' => $this->current_stock
            ]);
        }
        
        // If we have items in the items array, notify the user about the branch change
        if (count($this->items) > 0) {
            $this->notify('Branch changed. Please verify items and quantities for the new branch.', 'warning');
        }
    }

    /**
     * Debug helper to log important values for troubleshooting
     */
    private function debugCostValues($item, $source = 'unknown')
    {
        if (app()->environment('local', 'development')) {
            \Log::debug("Debug cost values from {$source}: " . json_encode([
                'item_id' => $item->id ?? null,
                'item_name' => $item->name ?? null,
                'cost_price_from_db' => $item->cost_price ?? null,
                'newItem.cost' => $this->newItem['cost'] ?? null,
                'cost_numeric' => is_numeric($item->cost_price ?? null),
                'cost_type' => gettype($item->cost_price ?? null),
                'db_value_raw' => $item->getRawOriginal('cost_price') ?? null,
            ]));
        }
    }

    /**
     * Show a notification to the user
     *
     * @param string $message The message to display
     * @param string $type The type of notification (success, error, info, warning)
     * @return void
     */
    private function notify($message, $type = 'info')
    {
        // Validate notification type
        $validTypes = ['success', 'error', 'info', 'warning'];
        if (!in_array($type, $validTypes)) {
            $type = 'info';
        }
        
        // Log the notification
        \Log::info('Purchase notification: ' . $message, [
            'type' => $type,
            'user_id' => auth()->id()
        ]);
        
        // Dispatch the notification event to the frontend
        $this->dispatch('notify', [
            'message' => $message,
            'type' => $type
        ]);
    }

    // This new method allows setting the item ID and cost at the same time,
    // avoiding race conditions that could reset the cost
    public function setItemAndCost($itemId, $cost = null)
    {
        // Always try to find the item if we have an ID
        $item = null;
        if (!empty($itemId)) {
            $item = Item::find($itemId);
        }
        
        // If we found an item, update our state
        if ($item) {
            $this->newItem['item_id'] = $item->id;
            
            // Get the item's current cost if none was provided or if the provided cost is invalid
            if ($cost === null || !is_numeric($cost) || $cost <= 0) {
                // Debug the source cost values
                $this->debugCostValues($item, 'setItemAndCost-before');
                
                // Use the item's cost_price directly rather than a hardcoded fallback
                $cost = $item->cost_price;
                
                $this->debugCostValues($item, 'setItemAndCost-after', $cost);
            }
            
            // Set the cost directly
            $this->newItem['cost'] = $cost !== null ? $cost : $item->cost_price;
            
            // Store additional useful information
            $this->newItem['unit'] = $item->unit ?? '';
            $this->current_stock = $this->getItemStock($item->id);
            
            // Tell the browser the item changed
            $this->dispatch('itemChanged', [
                'item_id' => $item->id,
                'cost' => $cost
            ]);
            
            return true;
        }
        
        return false;
    }

    // Handle the JavaScript dispatch event
    public function handleSetItemAndCost($data)
    {
        // Handle both possible data structures
        $itemId = $data['itemId'] ?? $data['id'] ?? null;
        $cost = isset($data['cost']) ? floatval($data['cost']) : 
                (isset($data['cost_price']) ? floatval($data['cost_price']) : null);
        
        if ($itemId) {
            $this->setItemAndCost($itemId, $cost);
        }
    }

    /**
     * Skip add item validation by clearing form and errors
     */
    protected function skipAddItemValidation()
    {
        // Clear the add item form to prevent validation
        $this->newItem = [
            'item_id' => '',
            'quantity' => 1,
            'cost' => 0,
            'notes' => '',
            'unit' => '',
        ];
        
        // Clear any validation errors from the add item form
        $this->resetErrorBag([
            'newItem.item_id',
            'newItem.quantity',
            'newItem.cost'
        ]);
    }

    /**
     * Handle the form submission without triggering add item validation
     */
    public function submitForm($params = [])
    {
        // Dump state first
        $this->dumpState();
        
        // Check if we received explicit parameters from JavaScript
        if (!empty($params) && is_array($params)) {
            \Log::info('Received explicit parameters from event', [
                'has_form' => isset($params['form']),
                'has_items' => isset($params['items']),
                'item_count' => isset($params['items']) ? count($params['items']) : 0
            ]);
            
            // Update our component state with the JavaScript state if provided
            if (isset($params['form']) && is_array($params['form'])) {
                \Log::info('Updating form data from event parameters');
                
                // Ensure the form has the correct structure
                $this->form = array_merge($this->form, $params['form']);
                
                // Type conversion for numeric fields
            $this->form['supplier_id'] = (int)$this->form['supplier_id'];
            $this->form['branch_id'] = (int)($this->form['branch_id'] ?? 0);
                $this->form['discount'] = (float)$this->form['discount'];
                $this->form['tax'] = (float)$this->form['tax'];
                $this->form['advance_amount'] = (float)$this->form['advance_amount'];
                
                \Log::info('Form data after processing', ['form' => $this->form]);
            }
            
            if (isset($params['items']) && is_array($params['items'])) {
                \Log::info('Updating items data from event parameters', [
                    'item_count' => count($params['items']),
                    'first_item' => isset($params['items'][0]) ? json_encode($params['items'][0]) : 'none'
                ]);
                
                // Normalize the items array to ensure consistent structure
                $normalizedItems = $this->normalizeItemsArray($params['items']);
                
                if (!empty($normalizedItems)) {
                    $this->items = $normalizedItems;
                } else {
                    // Use the original array if normalization doesn't produce results
                    $this->items = $params['items'];
                    
                    // Ensure each item has the correct data types
                    foreach ($this->items as $key => $item) {
                        if (is_array($item)) {
                            $this->items[$key]['item_id'] = (int)$item['item_id'];
                            $this->items[$key]['quantity'] = (float)($item['quantity'] ?? 0);
                            $this->items[$key]['cost'] = (float)($item['cost'] ?? 0);
                            $this->items[$key]['unit_cost'] = (float)($item['unit_cost'] ?? $item['cost'] ?? 0);
                            $this->items[$key]['discount'] = (float)($item['discount'] ?? 0);
                            $this->items[$key]['subtotal'] = (float)($item['subtotal'] ?? 0);
                        }
                    }
                }
                
                \Log::info('Items after processing', [
                    'count' => count($this->items),
                    'first_item' => isset($this->items[0]) ? json_encode($this->items[0]) : 'none'
                ]);
            }
        }
        
        // Debug information
        \Log::info('submitForm method called', [
            'items_count' => count($this->items),
            'user' => auth()->id(),
            'form_data' => $this->form,
            'request_data' => request()->all()
        ]);

        // Enhanced validation: Check required fields explicitly before proceeding
        $validationErrors = [];
        
        // Debug the actual values received
        \Log::info('Form values for validation:', [
            'supplier_id' => $this->form['supplier_id'] ?? 'not set',
            'supplier_id_type' => isset($this->form['supplier_id']) ? gettype($this->form['supplier_id']) : 'n/a',
            'branch_id' => $this->form['branch_id'] ?? 'not set',
            'branch_id_type' => isset($this->form['branch_id']) ? gettype($this->form['branch_id']) : 'n/a',
            'items_count' => count($this->items),
        ]);

        // Ensure values are properly converted to their intended types
        $supplierId = isset($this->form['supplier_id']) ? (int)$this->form['supplier_id'] : null;
        $branchIdForValidation = isset($this->form['branch_id']) ? (int)$this->form['branch_id'] : null;

        // Check supplier_id - consider 0 as invalid but handle both empty string and null
        if (empty($supplierId) && $supplierId !== 0) {
            $validationErrors['form.supplier_id'] = 'Supplier is required';
            \Log::warning('Supplier validation failed', ['value' => $supplierId]);
        }

        // Check branch_id
        if (empty($branchIdForValidation) && $branchIdForValidation !== 0) {
            $validationErrors['form.branch_id'] = 'Branch is required';
            \Log::warning('Branch validation failed', ['value' => $branchIdForValidation]);
        }

        // Check items array - be explicit about the check
        if (!is_array($this->items) || count($this->items) === 0) {
            $validationErrors['items'] = 'At least one item must be added to the purchase';
            $this->notify('Please add at least one item to the purchase before saving.', 'error');
            \Log::warning('Items validation failed', ['count' => count($this->items)]);
        } else {
            \Log::info('Items validation passed', ['count' => count($this->items)]);
        }

        // If validation errors exist, add them and return
        if (!empty($validationErrors)) {
            foreach ($validationErrors as $field => $message) {
                $this->addError($field, $message);
            }
            
            \Log::warning('Purchase submission failed validation', [
                'errors' => $validationErrors,
                'form_data' => $this->form
            ]);
            
            // Send a comprehensive error message
            $this->notify('Please fill in all required fields and add at least one item before saving.', 'error');
            
            // Dispatch a JS event to highlight errors
            $this->dispatch('purchase-validation-failed', ['errors' => $validationErrors]);
            
            return false;
        }
        
        // First, completely skip the add item validation
        $this->skipAddItemValidation();
        \Log::info('Item validation skipped');
        
        // Fix potential form data structure issues
        if (is_array($this->form) && isset($this->form[0]) && is_array($this->form[0])) {
            \Log::info('Detected nested form structure, fixing it', [
                'original_form' => $this->form
            ]);
            $this->form = $this->form[0];
            \Log::info('Fixed form structure', [
                'new_form' => $this->form
            ]);
        }
        
        // Check if there are items to save - log the actual items array structure
        \Log::info('Items structure before check', [
            'items' => $this->items,
            'is_array' => is_array($this->items),
            'count' => count($this->items),
            'empty_check' => empty($this->items)
        ]);
        
        if (empty($this->items) || count($this->items) === 0) {
            \Log::warning('No items to save in purchase');
            $this->notify('Please add at least one item to the purchase before saving.', 'error');
            return false;
        }
        
        // Clear any validation errors that might exist
        $this->resetErrorBag();
        \Log::info('Error bag reset');
        
        // Perform validation for the main form only
        try {
            \Log::info('Starting form validation');
            
            // Always generate a new unique reference number - no user input needed
            $this->form['reference_no'] = $this->generateUniqueReferenceNumber();
            \Log::info('Generated reference number', ['ref' => $this->form['reference_no']]);
            
            // Skip the unique reference number validation since we just generated a unique one
            $this->validate([
                'form.supplier_id' => 'required|exists:suppliers,id',
                'form.branch_id' => 'required|exists:branches,id',
                'form.purchase_date' => 'required|date',
                'form.payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            ]);
            
            // Log before saving
            \Log::info('Validation passed, calling save method with items:', [
                'items_count' => count($this->items),
                'first_item' => $this->items[0] ?? null
            ]);
            
            // Now call the save method directly
            try {
                $result = $this->save();
                
                // Log after save attempt
                \Log::info('Save method called, result:', ['result' => $result ? 'success' : 'failed']);
                
                return $result;
            } catch (\Exception $saveError) {
                \Log::error('Error in save method:', [
                    'exception' => get_class($saveError),
                    'message' => $saveError->getMessage(),
                    'file' => $saveError->getFile(),
                    'line' => $saveError->getLine(),
                    'trace' => $saveError->getTraceAsString()
                ]);
                
                $this->notify('Error saving purchase: ' . $saveError->getMessage(), 'error');
                return false;
            }
        } catch (\Exception $e) {
            // Handle validation or other errors
            \Log::error('Validation error in submitForm:', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'validation_errors' => $this->getErrorBag()
            ]);
            
            $this->notify('Error: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    // Add a dump method for debugging
    public function dumpState()
    {
        \Log::info('Current component state', [
            'form' => $this->form,
            'items' => $this->items,
            'subtotal' => $this->subtotal,
            'totalAmount' => $this->totalAmount,
            'taxAmount' => $this->taxAmount
        ]);
        
        $this->notify('State dumped to logs', 'info');
    }

    /**
     * Helper method to normalize array structures
     * This flattens nested arrays and ensures consistent data format
     */
    private function normalizeItemsArray($items)
    {
        \Log::info('Normalizing items array', [
            'initial_count' => is_array($items) ? count($items) : 0,
            'type' => gettype($items)
        ]);
        
        if (!is_array($items)) {
            return [];
        }
        
        $result = [];
        
        // Function to recursively process items
        $processItem = function($item) use (&$result) {
            if (is_array($item)) {
                // Check if this is an actual item with item_id
                if (isset($item['item_id'])) {
                    // This is a valid item, add it to results
                    $result[] = [
                        'item_id' => (int)$item['item_id'],
                        'name' => $item['name'] ?? '',
                        'sku' => $item['sku'] ?? '',
                        'unit' => $item['unit'] ?? '',
                        'quantity' => (float)($item['quantity'] ?? 0),
                        'cost' => (float)($item['cost'] ?? 0),
                        'unit_cost' => (float)($item['unit_cost'] ?? $item['cost'] ?? 0),
                        'subtotal' => (float)($item['subtotal'] ?? 0),
                        'notes' => $item['notes'] ?? null
                    ];
                } else {
                    // This might be a nested array, check each element
                    foreach ($item as $subItem) {
                        $this->normalizeItemsArray([$subItem]);
                    }
                }
            }
        };
        
        // Process each item in the array
        foreach ($items as $item) {
            $processItem($item);
        }
        
        \Log::info('Normalized items array', [
            'final_count' => count($result)
        ]);
        
        return $result;
    }

    // Add watcher for tax changes
    public function updatedFormTax()
    {
        $this->updateTotals();
    }

    /**
     * Explicitly set the supplier ID (triggered from JavaScript)
     */
    public function setSupplierManually($supplierId = null)
    {
        // Convert to integer if provided
        $supplierId = $supplierId ? (int)$supplierId : null;
        
        if ($supplierId) {
            // Set the supplier ID directly in the form data
            $this->form['supplier_id'] = $supplierId;
            
            // Log this action
            \Log::info('Supplier ID manually set', [
                'supplier_id' => $supplierId,
                'from_js' => true
            ]);
        }
    }

    /**
     * Updates the stock level for an item in a warehouse
     * 
     * @param int $warehouseId The warehouse ID
     * @param int $itemId The item ID
     * @param float $quantity The quantity to add to stock
     * @param int $purchaseId The purchase ID for reference
     * @return void
     */
    private function updateStock($warehouseId, $itemId, $quantity, $purchaseId = null)
    {
        try {
            \Log::info('Updating stock', [
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'quantity' => $quantity
            ]);
            
            // Find existing stock record or create a new one (warehouse-only, no branch_id)
            $stock = Stock::firstOrNew([
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'branch_id' => null  // Explicitly ensure warehouse stock has no branch assignment
            ]);
            
            // If it's a new record, set the initial quantity to 0
            if (!$stock->exists) {
                $stock->quantity = 0;
            }
            
            // Get the original quantity for logging
            $originalQuantity = $stock->quantity;
            
            // Add the purchase quantity
            $stock->quantity += $quantity;
            
            // Save the stock record
            $stock->save();
            
            \Log::info('Stock updated successfully', [
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'previous_quantity' => $originalQuantity,
                'added_quantity' => $quantity,
                'new_quantity' => $stock->quantity
            ]);
            
            // Create a stock history record for tracking
            \App\Models\StockHistory::create([
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'quantity_before' => $originalQuantity,
                'quantity_after' => $stock->quantity,
                'quantity_change' => $quantity,
                'reference_type' => 'purchase',
                'reference_id' => $purchaseId,
                'description' => 'Stock added from purchase: ' . ($this->form['reference_no'] ?? 'N/A'),
                'user_id' => auth()->id()
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Error updating stock', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'quantity' => $quantity
            ]);
            
            return false;
        }
    }

    private function resolveWarehouseIdForBranch(int $branchId): int
    {
        $branch = Branch::with('warehouses')->find($branchId);
        if ($branch && $branch->warehouses->isNotEmpty()) {
            return (int)$branch->warehouses->first()->id;
        }
        // Create default warehouse and attach
        $code = 'WH-BR-' . $branchId;
        $name = 'Default Warehouse - ' . ($branch?->name ?? ('Branch ' . $branchId));
        $warehouse = Warehouse::firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'address' => $branch?->address,
            ]
        );
        $warehouse->branches()->syncWithoutDetaching([$branchId]);
        return (int)$warehouse->id;
    }

    /**
     * Special handler for cash payments which are simpler to process
     */
    #[On('cashPayment')]
    public function handleCashPayment($params = [])
    {
        \Log::info('Received cash payment submission', [
            'params' => $params,
            'has_form' => isset($params['form']),
            'has_items' => isset($params['items']),
        ]);
        
        // For cash payments, we simplify the process with only essential fields
        if (isset($params['form']) && is_array($params['form'])) {
            // Set the form directly with required values for cash
            $this->form['purchase_date'] = $params['form']['purchase_date'] ?? date('Y-m-d');
            // Always generate a new reference number, don't use any provided one
            $this->form['reference_no'] = $this->generateUniqueReferenceNumber();
            $this->form['supplier_id'] = (int)($params['form']['supplier_id'] ?? 0);
            $this->form['branch_id'] = (int)($params['form']['branch_id'] ?? 0);
            $this->form['payment_method'] = PaymentMethod::CASH->value;
            $this->form['payment_status'] = PaymentStatus::PAID->value;
            $this->form['tax'] = (float)($params['form']['tax'] ?? 0);
            $this->form['notes'] = $params['form']['notes'] ?? '';
        }
        
        // Set items if provided
        if (isset($params['items']) && is_array($params['items'])) {
            $this->items = array_map(function($item) {
                // Ensure proper data types for each field
                return [
                    'item_id' => (int)($item['item_id'] ?? 0),
                    'name' => $item['name'] ?? '',
                    'sku' => $item['sku'] ?? '',
                    'unit' => $item['unit'] ?? 'pcs',
                    'quantity' => (float)($item['quantity'] ?? 0),
                    'cost' => (float)($item['cost'] ?? 0),
                    'subtotal' => (float)($item['subtotal'] ?? 0),
                    'notes' => $item['notes'] ?? null,
                ];
            }, $params['items']);
        }
        
        // Update totals
        $this->updateTotals();
        
        // Call save directly
        \Log::info('Proceeding with cash payment save', [
            'items_count' => count($this->items),
            'supplier' => $this->form['supplier_id'],
            'branch' => $this->form['branch_id']
        ]);
        
        return $this->save();
    }

    /**
     * Generate a default reference number format
     */
    private function getDefaultReferenceNumber()
    {
        // make it unique and 10 characters long

        return 'PO-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate a unique reference number with high entropy
     */
    private function generateUniqueReferenceNumber()
    {
        do {
        // Prefix based on date
        $datePrefix = 'PO-' . now()->format('Ymd');
        
            // Generate a more unique identifier using multiple sources
            $microtime = str_replace('.', '', microtime(true));
            $randomBytes = bin2hex(random_bytes(6)); // Increased from 4 to 6 bytes
            $userId = auth()->id() ?? 0;
            $randomNumber = mt_rand(1000, 9999);
            
            // Combine all sources for maximum uniqueness
            $unique = substr($microtime . $randomBytes . $userId . $randomNumber, 0, 12);
            
            $referenceNo = $datePrefix . '-' . $unique;
            
            // Check if this reference number already exists in the database
            $exists = \App\Models\Purchase::where('reference_no', $referenceNo)->exists();
            
        } while ($exists); // Keep generating until we get a unique one
        
        return $referenceNo;
    }

    /**
     * Clear all items from the cart
     */
    public function clearCart()
    {
        $itemCount = count($this->items);
        
        if ($itemCount === 0) {
            $this->notify('ℹ️ Cart is already empty', 'info');
            return;
        }

        $this->items = [];
        $this->selectedItem = null;
        $this->editingItemIndex = null;
        $this->newItem = [
            'item_id' => '',
            'quantity' => 1,
            'cost' => 0,
            'notes' => '',
            'unit' => '',
        ];
        
        // Clear search and selection state
        $this->itemSearch = '';
        $this->current_stock = 0;
        
        // Reset validation errors
        $this->resetValidation([
            'newItem.item_id',
            'newItem.quantity', 
            'newItem.cost',
            'newItem.notes'
        ]);
        
        // Reload item options to make all items available again
        $this->loadItems();
        
        // Update totals
        $this->updateTotals();
        
        // Dispatch event for frontend cleanup
        $this->dispatch('cartCleared');
        
        $this->notify("✓ Cart cleared ({$itemCount} items removed)", 'success');
    }

    /**
     * Edit an existing item in the cart
     */
    public function editItem($index)
    {
        if (!isset($this->items[$index])) {
            $this->notify('Item not found', 'error');
            return;
        }

        $item = $this->items[$index];
        
        // Set editing state
        $this->editingItemIndex = $index;
        
        // Populate newItem with existing data
        $this->newItem = [
            'item_id' => $item['item_id'],
            'quantity' => $item['quantity'],
            'cost' => $item['cost'],
            'notes' => $item['notes'] ?? '',
            'unit' => $item['unit'] ?? '',
        ];
        
        // Set selected item for display
        $this->selectedItem = [
            'id' => $item['item_id'],
            'name' => $item['name'],
            'sku' => $item['sku'],
            'unit' => $item['unit'] ?? '',
            'unit_quantity' => $item['unit_quantity'] ?? 1,
            'item_unit' => $item['item_unit'] ?? ''
        ];
    }

    /**
     * Cancel editing an item
     */
    public function cancelEdit()
    {
        $this->editingItemIndex = null;
        $this->clearSelectedItem();
        
        // Reset new item form
        $this->newItem = [
            'item_id' => '',
            'quantity' => 1,
            'cost' => 0,
            'notes' => '',
            'unit' => '',
        ];
    }

    /**
     * Update an existing item in the cart
     */
    public function updateExistingItem()
    {
        // Validate the item data
        $this->validate([
            'newItem.item_id' => 'required|exists:items,id',
            'newItem.quantity' => 'required|numeric|min:0.01',
            'newItem.cost' => 'required|numeric|min:0.01',
        ]);
        
        if ($this->editingItemIndex === null || !isset($this->items[$this->editingItemIndex])) {
            $this->notify('No item selected for editing', 'error');
            return;
        }
        
        $item = Item::find($this->newItem['item_id']);
        if (!$item) {
            $this->notify('Item not found', 'error');
            return;
        }
        
        // Calculate subtotal
        $subtotal = $this->newItem['quantity'] * $this->newItem['cost'];
        
        // Update the existing item
        $this->items[$this->editingItemIndex] = [
            'item_id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'unit' => $item->unit,
            'unit_quantity' => $item->unit_quantity ?? 1,
            'item_unit' => $item->item_unit ?? '',
            'quantity' => $this->newItem['quantity'],
            'cost' => $this->newItem['cost'],
            'subtotal' => $subtotal,
            'notes' => $this->newItem['notes'] ?? null,
        ];
        
        // Reset editing state
        $this->cancelEdit();
        
        // Update totals
        $this->updateTotals();
        
        $this->notify('Item updated successfully', 'success');
    }

    /**
     * Clear the selected item
     */
    public function clearSelectedItem()
    {
        $this->selectedItem = null;
        $this->newItem['item_id'] = '';
        $this->newItem['cost'] = 0;
        $this->newItem['unit'] = '';
        $this->current_stock = 0;
        $this->itemSearch = '';
    }

    /**
     * Select an item for adding to the cart
     */
    public function selectItem($itemId)
    {
        $item = collect($this->itemOptions)->firstWhere('id', $itemId);
        if ($item) {
            $this->selectedItem = $item;
            $this->newItem['item_id'] = $itemId;
            $this->newItem['cost'] = $item['cost'] ?? 0;
            $this->newItem['unit'] = $item['unit'] ?? '';
            $this->current_stock = $this->getItemStock($itemId);
            $this->itemSearch = '';
        }
    }

    /**
     * Get filtered item options for search - excludes items already in cart
     */
    public function getFilteredItemOptionsProperty()
    {
        // Get items already in cart
        $addedItemIds = collect($this->items)->pluck('item_id')->toArray();
        
        // Filter out added items from available options
        $availableItems = collect($this->itemOptions)
            ->reject(function ($item) use ($addedItemIds) {
                return in_array($item['id'], $addedItemIds);
            });

        if (empty($this->itemSearch)) {
            return $availableItems->take(10)->toArray();
        }
        
        $search = strtolower($this->itemSearch);
        return $availableItems
            ->filter(function ($item) use ($search) {
                return str_contains(strtolower($item['name']), $search) ||
                       str_contains(strtolower($item['sku'] ?? ''), $search);
            })
            ->take(10)
            ->toArray();
    }

    /**
     * Get filtered suppliers for search
     */
    public function getFilteredSuppliersProperty()
    {
        if (empty($this->supplierSearch)) {
            return $this->suppliers->take(10);
        }
        
        $search = strtolower($this->supplierSearch);
        return $this->suppliers
            ->filter(function ($supplier) use ($search) {
                return str_contains(strtolower($supplier->name), $search) ||
                       str_contains(strtolower($supplier->phone ?? ''), $search) ||
                       str_contains(strtolower($supplier->email ?? ''), $search);
            })
            ->take(10);
    }

    /**
     * Validate form and show confirmation modal if valid
     */
    public function validateAndShowModal()
    {
        // Clear any previous validation errors
        $this->resetErrorBag();
        
        // Basic validation first - collect errors instead of throwing exceptions
        $validationErrors = [];
        
        if (empty($this->items) || count($this->items) === 0) {
            $validationErrors['items'] = 'No items found for this purchase. Please add at least one item.';
        }

        if (empty($this->form['supplier_id'])) {
            $validationErrors['form.supplier_id'] = 'Please select a supplier.';
        }

        if (empty($this->form['branch_id'])) {
            $validationErrors['form.branch_id'] = 'Please select a branch.';
        }

        // Check payment method specific validations
        if ($this->form['payment_method'] === 'telebirr' && empty($this->form['transaction_number'])) {
            $validationErrors['form.transaction_number'] = 'Transaction number is required for Telebirr payments.';
        }

        if ($this->form['payment_method'] === 'bank_transfer' && empty($this->form['bank_account_id'])) {
            $validationErrors['form.bank_account_id'] = 'Bank account is required for bank transfer payments.';
        }

        if ($this->form['payment_method'] === 'credit_advance') {
            if (empty($this->form['advance_amount']) || $this->form['advance_amount'] <= 0) {
                $validationErrors['form.advance_amount'] = 'Advance amount is required and must be greater than zero.';
            } elseif ($this->form['advance_amount'] > $this->totalAmount) {
                $validationErrors['form.advance_amount'] = 'Advance amount cannot exceed the total amount.';
            }
        }

        // If there are validation errors, add them and return
        if (!empty($validationErrors)) {
            foreach ($validationErrors as $field => $message) {
                $this->addError($field, $message);
            }
            
            $this->notify('❌ Please fix the validation errors before proceeding.', 'error');
            
            // Scroll to the first error
            $this->dispatch('scrollToFirstError');
            
            return false;
        }

        // If validation passes, show the modal
        $this->dispatch('showConfirmationModal');
        
        return true;
    }

    /**
     * Confirm purchase - handles modal dismissal and form submission
     */
    public function confirmPurchase()
    {
        // Clear any previous validation errors
        $this->resetErrorBag();
        
        // First validate basic requirements
        $validationErrors = [];
        
        if (empty($this->items) || count($this->items) === 0) {
            $validationErrors['items'] = 'Cannot create purchase: No items added';
        }

        if (empty($this->form['supplier_id'])) {
            $validationErrors['form.supplier_id'] = 'Cannot create purchase: Please select a supplier';
        }

        if (empty($this->form['branch_id'])) {
            $validationErrors['form.branch_id'] = 'Cannot create purchase: Please select a branch';
        }

        // Check payment method specific validations
        if ($this->form['payment_method'] === 'telebirr' && empty($this->form['transaction_number'])) {
            $validationErrors['form.transaction_number'] = 'Transaction number is required for Telebirr payments';
        }

        if ($this->form['payment_method'] === 'bank_transfer' && empty($this->form['bank_account_id'])) {
            $validationErrors['form.bank_account_id'] = 'Bank account is required for bank transfer payments';
        }

        if ($this->form['payment_method'] === 'credit_advance') {
            if (empty($this->form['advance_amount']) || $this->form['advance_amount'] <= 0) {
                $validationErrors['form.advance_amount'] = 'Advance amount is required and must be greater than zero';
            } elseif ($this->form['advance_amount'] > $this->totalAmount) {
                $validationErrors['form.advance_amount'] = 'Advance amount cannot exceed the total amount';
            }
        }

        // If there are validation errors, add them and return
        if (!empty($validationErrors)) {
            foreach ($validationErrors as $field => $message) {
                $this->addError($field, $message);
            }
            
            $this->notify('❌ Please fix the errors before creating the purchase.', 'error');
            
            // Close the modal and show errors on the form
            $this->dispatch('closePurchaseModal');
            $this->dispatch('scrollToFirstError');
            
            return false;
        }

        // Show loading state
        $this->notify('💾 Creating purchase...', 'info');

        // Call the save method directly instead of submitForm
        $result = $this->save();
        
        if ($result !== false) {
            // If successful, dispatch event to close modal
            $this->dispatch('closePurchaseModal');
            $this->dispatch('purchaseCompleted');
            
            // Show success message with more details
            $totalAmount = number_format($this->totalAmount, 2);
            $itemCount = count($this->items);
            $this->notify("✅ Purchase created successfully! {$itemCount} items, ETB {$totalAmount}", 'success');
            
            // Redirect to purchases index
            return redirect()->route('admin.purchases.index')
                ->with('success', 'Purchase created successfully!');
        } else {
            // The save method already handles validation errors and notifications
            // Close the modal to show errors on the form
            $this->dispatch('closePurchaseModal');
            
            // Show a general error message if no specific errors were set
            if ($this->getErrorBag()->isEmpty()) {
                $this->addError('general', 'An error occurred while creating the purchase. Please try again.');
                $this->notify('❌ Failed to create purchase. Please check the form and try again.', 'error');
            }
            
            return false;
        }
    }

    /**
     * Handle quantity changes for auto-calculation
     */
    public function updatedNewItemQuantity($value)
    {
        // Auto-calculate totals when quantity changes
        if ($this->selectedItem && $value > 0) {
            $this->newItem['quantity'] = (int)$value;
            // The total calculation will be handled by the view
        }
    }

    /**
     * Calculate total cost per piece (cost × unit_quantity)
     */
    public function getTotalCostPerPieceProperty()
    {
        if (!$this->selectedItem) {
            return 0;
        }
        
        $cost = (float)($this->newItem['cost'] ?? 0);
        $unitQuantity = (int)($this->selectedItem['unit_quantity'] ?? 1);
        
        return round($cost * $unitQuantity, 2);
    }

    /**
     * Handle unit cost changes for auto-calculation
     */
    public function updatedNewItemUnitCost($value)
    {
        // Calculate piece cost based on unit cost and item's unit quantity
        if ($this->selectedItem && isset($this->selectedItem['unit_quantity'])) {
            $this->newItem['cost'] = (float)$value * (int)($this->selectedItem['unit_quantity'] ?? 1);
        }
        
        // Update totals when cost changes
        $this->updateTotals();
    }

    /**
     * Handle cost changes for auto-calculation
     */
    public function updatedNewItemCost($value)
    {
        // Auto-calculate totals when cost changes
        if ($this->selectedItem && $value > 0) {
            $this->newItem['cost'] = (float)$value;
        }
    }
}
