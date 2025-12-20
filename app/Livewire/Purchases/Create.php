<?php

namespace App\Livewire\Purchases;

use App\Models\BankAccount;
use App\Models\Supplier;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\Stock;
use App\Models\CreditPayment;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
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
        'setSupplierManually' => 'setSupplierManually',
        'itemCreated' => 'handleItemCreated'
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

        if (in_array($this->form['payment_method'], [PaymentMethod::TELEBIRR->value, PaymentMethod::BANK_TRANSFER->value], true)) {
            $rules['form.transaction_number'] = [
                'required',
                'string',
                'min:5',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($this->transactionNumberExists((string) $value)) {
                        $fail('This transaction number has already been used.');
                    }
                },
            ];
        }

        if ($this->form['payment_method'] === PaymentMethod::TELEBIRR->value) {
            $rules['form.receiver_account_holder'] = 'required|string|max:255';
        }

        if ($this->form['payment_method'] === PaymentMethod::BANK_TRANSFER->value) {
            $rules['form.receiver_bank_name'] = 'required|string|max:255';
            $rules['form.receiver_account_holder'] = 'required|string|max:255';
            $rules['form.receiver_account_number'] = 'required|string|max:255';
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
        'form.transaction_number.required' => 'Transaction number is required for Telebirr or bank transfer payments.',
        'form.transaction_number.min' => 'Transaction number must be at least 5 characters.',
        'form.receiver_account_holder.required' => 'Account holder name is required.',
        'form.receiver_bank_name.required' => 'Please choose a bank.',
        'form.receiver_account_number.required' => 'Receiver account number is required.',
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
        'receiver_bank_name' => '',
        'receiver_account_holder' => '',
        'receiver_account_number' => '',
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
        // Component initialization
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
            'receiver_bank_name' => '',
            'receiver_account_holder' => '',
            'receiver_account_number' => '',
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
     * Get branches that the current user can access based on their role and assignment
     */
    private function getAccessibleBranches()
    {
        $user = auth()->user();
        
        // SuperAdmin and GeneralManager can access all branches
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return Branch::where('is_active', true)->orderBy('name')->get();
        }
        
        // BranchManager can ONLY access their own branch for purchases
        if ($user->isBranchManager()) {
            return Branch::where('id', $user->branch_id)->where('is_active', true)->get();
        }
        
        // Users assigned to a specific branch
        if ($user->branch_id) {
            return Branch::where('id', $user->branch_id)->where('is_active', true)->get();
        }
        
        // Fallback: return all active branches if no specific assignment
        return Branch::where('is_active', true)->orderBy('name')->get();
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
        // For purchases: Show ALL active items regardless of stock (purchases add stock)
        // Allow adding same item multiple times for different prices/conditions
        $this->itemOptions = Item::where('is_active', true)
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
                $costPerPiece = $item->cost_price ?? (($item->cost_price_per_unit ?? 0) * ($item->unit_quantity ?? 1));
                $costPerUnit = $item->cost_price_per_unit ?? ($costPerPiece / ($item->unit_quantity ?? 1));
                
                $this->newItem['unit_cost'] = $costPerUnit;
                $this->newItem['cost'] = $costPerPiece;
                $this->newItem['unit'] = $item->unit ?? '';
                $this->current_stock = $this->getItemStock($value);

                $this->selectedItem = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'unit' => $item->unit ?? 'pcs',
                    'unit_quantity' => $item->unit_quantity ?? 1,
                    'item_unit' => $item->item_unit ?? 'piece',
                    'cost_price' => $costPerPiece,
                    'cost_price_per_unit' => $costPerUnit,
                    'description' => $item->description,
                ];
            }
        } else {
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
        
        // Convert values to proper floating point numbers
        $cost = round(floatval($this->newItem['cost']), 2);
        $quantity = floatval($this->newItem['quantity']);
        
        return $this->processAddItem();
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
        $this->form['receiver_bank_name'] = '';
        $this->form['receiver_account_holder'] = '';
        $this->form['receiver_account_number'] = '';
        $this->form['receipt_url'] = '';
        $this->form['receipt_image'] = '';
        $this->form['advance_amount'] = 0;

        // Set payment status based on payment method
        if (in_array($value, [PaymentMethod::CASH->value, PaymentMethod::BANK_TRANSFER->value, PaymentMethod::TELEBIRR->value], true)) {
            $this->form['payment_status'] = PaymentStatus::PAID->value;
        } elseif ($value === PaymentMethod::CREDIT_ADVANCE->value) {
            $this->form['payment_status'] = PaymentStatus::PARTIAL->value;
            if ($this->totalAmount > 0) {
                $this->form['advance_amount'] = round($this->totalAmount * 0.2, 2);
            }
        } elseif ($value === PaymentMethod::FULL_CREDIT->value) {
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
        if ($this->form['payment_method'] === PaymentMethod::TELEBIRR->value) {
            if (empty($this->form['transaction_number'])) {
                $validationErrors['form.transaction_number'] = 'Transaction number is required for Telebirr payments.';
            } elseif (strlen((string) $this->form['transaction_number']) < 5) {
                $validationErrors['form.transaction_number'] = 'Transaction number must be at least 5 characters.';
            }
            if (empty($this->form['receiver_account_holder'])) {
                $validationErrors['form.receiver_account_holder'] = 'Account holder name is required for Telebirr payments.';
            }
        }

        if ($this->form['payment_method'] === PaymentMethod::BANK_TRANSFER->value) {
            if (empty($this->form['transaction_number'])) {
                $validationErrors['form.transaction_number'] = 'Transaction number is required for bank transfer payments.';
            } elseif (strlen((string) $this->form['transaction_number']) < 5) {
                $validationErrors['form.transaction_number'] = 'Transaction number must be at least 5 characters.';
            }
            if (empty($this->form['receiver_bank_name'])) {
                $validationErrors['form.receiver_bank_name'] = 'Please choose a bank for bank transfer payments.';
            }
            if (empty($this->form['receiver_account_holder'])) {
                $validationErrors['form.receiver_account_holder'] = 'Account holder name is required for bank transfer payments.';
            }
            if (empty($this->form['receiver_account_number'])) {
                $validationErrors['form.receiver_account_number'] = 'Account number is required for bank transfer payments.';
            }
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
        
            // Generate unique reference number
        $referenceNo = $this->generateUniqueReferenceNumber();
            
        // Start a transaction to ensure all related operations succeed or fail together
        \DB::beginTransaction();
            
        try {
            // Create the purchase record
            $branchId = (int)($this->form['branch_id'] ?? 0);
            if ($branchId <= 0) {
                $branchId = auth()->user()->branch_id ?? (Branch::value('id') ?? 1);
            }
            
            try {
                $warehouseId = $this->resolveWarehouseIdForBranch($branchId);
            } catch (\Exception $e) {
                throw new \Exception('Failed to resolve warehouse for branch: ' . $e->getMessage());
            }
            
            $purchase = new Purchase();
            $purchase->reference_no = $referenceNo;
            $purchase->user_id = auth()->id();
            $purchase->branch_id = $branchId;
            $purchase->supplier_id = $this->form['supplier_id'];
            $purchase->warehouse_id = $warehouseId; // internal detail
            $purchase->purchase_date = $this->form['purchase_date'];
            $purchase->payment_method = $this->form['payment_method'];
            $purchase->payment_status = $this->form['payment_status'];
            $purchase->status = 'pending'; // Use valid database enum value
            $purchase->discount = 0;
            $purchase->tax = $this->taxAmount;
            $purchase->total_amount = $this->totalAmount;
            // Set payment amounts based on payment method
            if ($this->form['payment_method'] === PaymentMethod::FULL_CREDIT->value) {
                $purchase->paid_amount = 0;
                $purchase->due_amount = $this->totalAmount;
                $purchase->payment_status = PaymentStatus::DUE->value;
            } elseif ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value) {
                $purchase->paid_amount = $this->form['advance_amount'];
                $purchase->due_amount = $this->totalAmount - $this->form['advance_amount'];
                $purchase->payment_status = PaymentStatus::PARTIAL->value;
            } else {
                // Cash, Bank Transfer, Telebirr - fully paid
                $purchase->paid_amount = $this->totalAmount;
                $purchase->due_amount = 0;
                $purchase->payment_status = PaymentStatus::PAID->value;
            }
            $purchase->notes = $this->form['notes'];
            
            // Handle payment type specific fields
            if (in_array($this->form['payment_method'], [PaymentMethod::BANK_TRANSFER->value, PaymentMethod::TELEBIRR->value], true)) {
                $purchase->transaction_number = $this->form['transaction_number'] ?? null;
            }

            if ($this->form['payment_method'] === PaymentMethod::TELEBIRR->value) {
                $purchase->receiver_account_holder = $this->form['receiver_account_holder'] ?? null;
            }

            if ($this->form['payment_method'] === PaymentMethod::BANK_TRANSFER->value) {
                $purchase->receiver_bank_name = $this->form['receiver_bank_name'] ?? null;
                $purchase->receiver_account_holder = $this->form['receiver_account_holder'] ?? null;
                $purchase->receiver_account_number = $this->form['receiver_account_number'] ?? null;
            }

            
            try {
                $purchase->save();
            } catch (\Exception $e) {
                $this->addError('general', 'Failed to save purchase record: ' . $e->getMessage());
                $this->notify('❌ Failed to save purchase record.', 'error');
                \DB::rollBack();
                return false;
            }
            
            // Now create the purchase items
            $itemTotal = 0;
            foreach ($this->items as $index => $item) {
                $itemId = intval($item['item_id'] ?? 0);
                $quantity = floatval($item['quantity'] ?? 0);
                $cost = floatval($item['cost'] ?? 0);
                $subtotal = floatval($item['subtotal'] ?? ($quantity * $cost));
                
                if ($itemId <= 0 || $quantity <= 0 || $cost <= 0) {
                    continue;
                }
                
                $itemRecord = Item::find($itemId);
                if (!$itemRecord) {
                    continue;
                }
                
                $purchaseItem = new PurchaseItem();
                $purchaseItem->purchase_id = $purchase->id;
                $purchaseItem->item_id = $itemId;
                $purchaseItem->quantity = $quantity;
                $purchaseItem->unit_cost = $cost;
                $purchaseItem->discount = 0;
                $purchaseItem->subtotal = $subtotal;
                
                if (!empty($item['notes'])) {
                    $purchaseItem->notes = $item['notes'];
                }
                
                try {
                    $purchaseItem->save();
                } catch (\Exception $e) {
                    $this->addError('items', "Failed to save purchase item {$index}: " . $e->getMessage());
                    $this->notify('❌ Failed to save purchase item.', 'error');
                    \DB::rollBack();
                    return false;
                }
                
                $itemTotal += $subtotal;
                
                $this->updateStock($purchase->warehouse_id, $itemId, $quantity, $purchase->id);
                
                if ($cost > 0) {
                    $unitQuantity = $itemRecord->unit_quantity ?? 1;
                    $costPerUnit = $cost / $unitQuantity;
                    
                    $itemRecord->cost_price = $cost;
                    $itemRecord->cost_price_per_unit = $costPerUnit;
                    $itemRecord->save();
                }
            }
            
            // Create credit record for credit-type payments
            if (in_array($this->form['payment_method'], [PaymentMethod::FULL_CREDIT->value, PaymentMethod::CREDIT_ADVANCE->value], true)) {
                if ($this->form['payment_method'] === PaymentMethod::FULL_CREDIT->value) {
                    // Full Credit: entire amount becomes credit
                    $credit = \App\Models\Credit::create([
                        'supplier_id' => $purchase->supplier_id,
                        'amount' => $this->totalAmount,
                        'paid_amount' => 0,
                        'balance' => $this->totalAmount,
                        'reference_no' => $purchase->reference_no,
                        'reference_type' => 'purchase',
                        'reference_id' => $purchase->id,
                        'credit_type' => 'payable',
                        'description' => 'Full credit for purchase #' . $purchase->reference_no,
                        'credit_date' => $purchase->purchase_date,
                        'due_date' => now()->addDays(30),
                        'status' => 'active',
                        'user_id' => auth()->id(),
                        'branch_id' => $branchId,
                        'warehouse_id' => $purchase->warehouse_id,
                    ]);
                } elseif ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value) {
                    // Credit with Advance: remaining amount becomes credit
                    $dueAmount = $this->totalAmount - $this->form['advance_amount'];
                    if ($dueAmount > 0) {
                        $credit = \App\Models\Credit::create([
                            'supplier_id' => $purchase->supplier_id,
                            'amount' => $this->totalAmount,
                            'paid_amount' => $this->form['advance_amount'],
                            'balance' => $dueAmount,
                            'reference_no' => $purchase->reference_no,
                            'reference_type' => 'purchase',
                            'reference_id' => $purchase->id,
                            'credit_type' => 'payable',
                            'description' => 'Credit with advance for purchase #' . $purchase->reference_no,
                            'credit_date' => $purchase->purchase_date,
                            'due_date' => now()->addDays(30),
                            'status' => 'partial',
                            'user_id' => auth()->id(),
                            'branch_id' => $branchId,
                            'warehouse_id' => $purchase->warehouse_id,
                        ]);
                    }
                }
            }
            \DB::commit();
            
            $this->notify('✅ Purchase created successfully!', 'success');
            
            // Redirect to purchases index
            return redirect()->route('admin.purchases.index')
                ->with('success', 'Purchase created successfully!');
            
        } catch (\Exception $e) {
            \DB::rollBack();
            
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

    public function getBanksProperty()
    {
        $bankService = new \App\Services\BankService();
        return $bankService->getEthiopianBanks();
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
        if (!$supplier) {
            $this->form['supplier_id'] = '';
            return;
        }

        if (is_array($supplier) && isset($supplier['id'])) {
            $this->form['supplier_id'] = (int)$supplier['id'];
            $this->selectedSupplier = $supplier;
            
            $this->dispatch('createHiddenSupplierField', [
                'id' => $this->form['supplier_id'],
                'name' => $supplier['name'] ?? 'unknown'
            ]);
        } elseif (is_numeric($supplier)) {
            $this->form['supplier_id'] = (int)$supplier;
            
            $supplierModel = \App\Models\Supplier::find($this->form['supplier_id']);
            if ($supplierModel) {
                $this->selectedSupplier = [
                    'id' => $supplierModel->id,
                    'name' => $supplierModel->name,
                    'phone' => $supplierModel->phone
                ];
            }
            
            $supplierName = $supplierModel ? $supplierModel->name : 'unknown';
            
            $this->dispatch('createHiddenSupplierField', [
                'id' => $this->form['supplier_id'],
                'name' => $supplierName
            ]);
        }
    }

    public function itemSelected($itemData)
    {
        if (is_array($itemData)) {
            $itemId = $itemData['id'] ?? null;
            $costPrice = $itemData['cost_price'] ?? 0;
            $costPricePerUnit = $itemData['cost_price_per_unit'] ?? 0;
            $unit = $itemData['unit'] ?? '';
            $currentStock = $itemData['current_stock'] ?? 0;
            $unitQuantity = $itemData['unit_quantity'] ?? 1;
            $itemUnit = $itemData['item_unit'] ?? 'piece';
        } else {
            return;
        }
        
        if (!$itemId) {
            return;
        }
        
        $this->newItem['item_id'] = $itemId;
        $this->newItem['unit_cost'] = $costPricePerUnit;
        $this->newItem['cost'] = $costPricePerUnit * $unitQuantity;
        $this->newItem['unit'] = $unit;
        
        $this->current_stock = $currentStock;
        
        $this->selectedItem = [
            'id' => $itemId,
            'name' => $itemData['name'] ?? '',
            'cost_price_per_unit' => $costPricePerUnit,
            'unit_quantity' => $unitQuantity,
            'item_unit' => $itemUnit,
        ];
    }

    public function updatedFormSupplierId($value)
    {
        $this->form['supplier_id'] = (int)$value;
        
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

    public function updatedFormBranchId($value)
    {
        $this->form['branch_id'] = (int)$value;
        
        if (!empty($this->form['branch_id']) && !empty($this->newItem['item_id'])) {
            $this->current_stock = $this->getItemStock($this->newItem['item_id']);
        }
        
        if (count($this->items) > 0) {
            $this->notify('Branch changed. Please verify items and quantities for the new branch.', 'warning');
        }
    }



    /**
     * Show a notification to the user
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
        $this->dispatch('notify', type: $type, message: $message);
    }

    public function setItemAndCost($itemId, $cost = null)
    {
        $item = null;
        if (!empty($itemId)) {
            $item = Item::find($itemId);
        }
        
        if ($item) {
            $this->newItem['item_id'] = $item->id;
            
            if ($cost === null || !is_numeric($cost) || $cost <= 0) {
                $cost = $item->cost_price;
            }
            
            $this->newItem['cost'] = $cost !== null ? $cost : $item->cost_price;
            $this->newItem['unit'] = $item->unit ?? '';
            $this->current_stock = $this->getItemStock($item->id);
            
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







    // Add watcher for tax changes
    public function updatedFormTax()
    {
        $this->updateTotals();
    }

    public function setSupplierManually($supplierId = null)
    {
        $supplierId = $supplierId ? (int)$supplierId : null;
        
        if ($supplierId) {
            $this->form['supplier_id'] = $supplierId;
        }
    }

    private function updateStock($warehouseId, $itemId, $quantity, $purchaseId = null)
    {
        try {
            $item = Item::find($itemId);
            if (!$item) {
                throw new \Exception("Item not found: {$itemId}");
            }
            
            $unitCapacity = $item->unit_quantity ?? 1;
            
            $stock = Stock::firstOrCreate(
                [
                    'warehouse_id' => $warehouseId,
                    'item_id' => $itemId
                ],
                [
                    'quantity' => 0,
                    'piece_count' => 0,
                    'total_units' => 0,
                    'current_piece_units' => $unitCapacity,
                    'created_by' => auth()->id()
                ]
            );
            
            $originalPieces = $stock->piece_count ?? 0;
            $originalQuantity = $stock->quantity ?? 0;
            $originalUnits = $stock->total_units ?? 0;
            
            $addedPieces = (int)$quantity;
            $stock->piece_count = $originalPieces + $addedPieces;
            $stock->quantity = $stock->piece_count;
            $stock->total_units = $originalUnits + ($addedPieces * $unitCapacity);
            $stock->updated_by = auth()->id();
            
            if ($stock->current_piece_units === null) {
                $stock->current_piece_units = $unitCapacity;
            }
            
            $stock->save();
            
            \App\Models\StockHistory::create([
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'quantity_before' => $originalQuantity,
                'quantity_after' => $stock->quantity,
                'quantity_change' => $addedPieces,
                'units_before' => $originalUnits,
                'units_after' => $stock->total_units,
                'units_change' => ($addedPieces * $unitCapacity),
                'reference_type' => 'purchase',
                'reference_id' => $purchaseId,
                'description' => 'Stock added from purchase: ' . ($this->form['reference_no'] ?? 'N/A'),
                'user_id' => auth()->id(),
            ]);
            
            return true;
        } catch (\Exception $e) {
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
            'item_unit' => $item->item_unit ?? 'piece',
            'quantity' => $this->newItem['quantity'], // Pieces that will become stock
            'cost' => $this->newItem['cost'], // Cost per piece
            'unit_cost' => $this->newItem['unit_cost'] ?? ($this->newItem['cost'] / ($item->unit_quantity ?? 1)), // Cost per unit
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
     * Handle item created from modal
     */
    public function handleItemCreated($itemData)
    {
        // Reload items to include the new item
        $this->loadItems();
        
        // Auto-select the newly created item
        $this->newItem['item_id'] = $itemData['id'];
        $this->newItem['unit_cost'] = $itemData['cost_price_per_unit'];
        $this->newItem['cost'] = $itemData['cost_price_per_unit'] * $itemData['unit_quantity'];
        
        $this->selectedItem = [
            'id' => $itemData['id'],
            'name' => $itemData['name'],
            'sku' => $itemData['sku'],
            'unit_quantity' => $itemData['unit_quantity'],
            'item_unit' => $itemData['item_unit'],
        ];
        
        $this->notify('✅ Item created and selected successfully!', 'success');
    }

    /**
     * Select an item for adding to the cart
     */
    public function selectItem($itemId)
    {
        $item = collect($this->itemOptions)->firstWhere('id', $itemId);
        if ($item) {
            $this->selectedItem = [
                'id' => $item['id'],
                'name' => $item['name'],
                'sku' => $item['sku'],
                'unit_quantity' => $item['unit_quantity'],
                'item_unit' => $item['item_unit'],
            ];
            $this->newItem['item_id'] = $itemId;
            $this->newItem['unit_cost'] = $item['cost_price_per_unit'] ?? 0;
            $this->newItem['cost'] = ($item['cost_price_per_unit'] ?? 0) * ($item['unit_quantity'] ?? 1);
            $this->newItem['unit'] = $item['unit'] ?? '';
            $this->current_stock = $this->getItemStock($itemId);
            $this->itemSearch = '';
        }
    }

    /**
     * Get filtered item options for search - shows all active items for purchases
     */
    public function getFilteredItemOptionsProperty()
    {
        if (empty($this->itemSearch) || strlen(trim($this->itemSearch)) < 2) {
            return [];
        }
        
        $search = strtolower(trim($this->itemSearch));
        
        // For purchases: Show ALL active items regardless of stock (purchases add stock)
        $items = Item::where('is_active', true)
            ->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $search . '%'])
                      ->orWhereRaw('LOWER(sku) LIKE ?', ['%' . $search . '%'])
                      ->orWhereRaw('LOWER(barcode) LIKE ?', ['%' . $search . '%']);
            })
            ->orderBy('name')
            ->take(15)
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
            })
            ->toArray();
            
        return $items;
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
        if ($this->form['payment_method'] === PaymentMethod::TELEBIRR->value) {
            if (empty($this->form['transaction_number'])) {
                $validationErrors['form.transaction_number'] = 'Transaction number is required for Telebirr payments.';
            } elseif (strlen((string) $this->form['transaction_number']) < 5) {
                $validationErrors['form.transaction_number'] = 'Transaction number must be at least 5 characters.';
            }
            if (empty($this->form['receiver_account_holder'])) {
                $validationErrors['form.receiver_account_holder'] = 'Account holder name is required for Telebirr payments.';
            }
        }

        if ($this->form['payment_method'] === PaymentMethod::BANK_TRANSFER->value) {
            if (empty($this->form['transaction_number'])) {
                $validationErrors['form.transaction_number'] = 'Transaction number is required for bank transfer payments.';
            } elseif (strlen((string) $this->form['transaction_number']) < 5) {
                $validationErrors['form.transaction_number'] = 'Transaction number must be at least 5 characters.';
            }
            if (empty($this->form['receiver_bank_name'])) {
                $validationErrors['form.receiver_bank_name'] = 'Please choose a bank for bank transfer payments.';
            }
            if (empty($this->form['receiver_account_holder'])) {
                $validationErrors['form.receiver_account_holder'] = 'Account holder name is required for bank transfer payments.';
            }
            if (empty($this->form['receiver_account_number'])) {
                $validationErrors['form.receiver_account_number'] = 'Account number is required for bank transfer payments.';
            }
        }

        if ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value) {
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
        if ($this->form['payment_method'] === PaymentMethod::TELEBIRR->value) {
            if (empty($this->form['transaction_number'])) {
                $validationErrors['form.transaction_number'] = 'Transaction number is required for Telebirr payments.';
            } elseif (strlen((string) $this->form['transaction_number']) < 5) {
                $validationErrors['form.transaction_number'] = 'Transaction number must be at least 5 characters.';
            }
            if (empty($this->form['receiver_account_holder'])) {
                $validationErrors['form.receiver_account_holder'] = 'Account holder name is required for Telebirr payments.';
            }
        }

        if ($this->form['payment_method'] === PaymentMethod::BANK_TRANSFER->value) {
            if (empty($this->form['transaction_number'])) {
                $validationErrors['form.transaction_number'] = 'Transaction number is required for bank transfer payments.';
            } elseif (strlen((string) $this->form['transaction_number']) < 5) {
                $validationErrors['form.transaction_number'] = 'Transaction number must be at least 5 characters.';
            }
            if (empty($this->form['receiver_bank_name'])) {
                $validationErrors['form.receiver_bank_name'] = 'Please choose a bank for bank transfer payments.';
            }
            if (empty($this->form['receiver_account_holder'])) {
                $validationErrors['form.receiver_account_holder'] = 'Account holder name is required for bank transfer payments.';
            }
            if (empty($this->form['receiver_account_number'])) {
                $validationErrors['form.receiver_account_number'] = 'Account number is required for bank transfer payments.';
            }
        }

        if ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value) {
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

    public function updatedNewItemUnitCost($value)
    {
        if ($this->selectedItem && isset($this->selectedItem['unit_quantity'])) {
            $unitQuantity = (int)($this->selectedItem['unit_quantity'] ?? 1);
            $this->newItem['cost'] = (float)$value * $unitQuantity;
        }
        
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
    

    
    /**
     * Process adding item to cart (extracted from addItem)
     */
    private function processAddItem()
    {
        $item = Item::find($this->newItem['item_id']);
        if (!$item) {
            $this->notify('❌ Item not found in database', 'error');
            return false;
        }
        
        // Convert values to proper floating point numbers
        $cost = round(floatval($this->newItem['cost']), 2);
        $quantity = floatval($this->newItem['quantity']);

        // Calculate subtotal
        $subtotal = $cost * $quantity;

        // Add as a new item (allow duplicates for different prices/conditions)
        $this->items[] = [
            'item_id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'unit' => $item->unit ?? '',
            'unit_quantity' => $item->unit_quantity ?? 1,
            'item_unit' => $item->item_unit ?? 'piece',
            'quantity' => $quantity, // This represents pieces that will become stock
            'cost' => $cost, // This is cost per piece
            'unit_cost' => $this->newItem['unit_cost'] ?? ($cost / ($item->unit_quantity ?? 1)), // Cost per individual unit
            'subtotal' => $subtotal,
            'notes' => $this->newItem['notes'] ?? null,
        ];

        // Update totals
        $this->updateTotals();
        
        // Reset form for next item (this will show success message)
        $this->resetItemFields();
        
        return true;
    }

    /**
     * Check if a transaction number already exists across sales, purchases, sale payments, or credit payments.
     */
    private function transactionNumberExists(string $transactionNumber): bool
    {
        if ($transactionNumber === '') {
            return false;
        }

        if (Purchase::where('transaction_number', $transactionNumber)->exists()) {
            return true;
        }

        if (Sale::where('transaction_number', $transactionNumber)->exists()) {
            return true;
        }

        $salePaymentExists = class_exists(SalePayment::class)
            ? SalePayment::where('transaction_number', $transactionNumber)->exists()
            : false;
        if ($salePaymentExists) {
            return true;
        }

        $creditPaymentExists = class_exists(CreditPayment::class)
            ? CreditPayment::where('reference_no', $transactionNumber)->exists()
            : false;

        return $creditPaymentExists;
    }
}
