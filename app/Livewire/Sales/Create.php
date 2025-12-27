<?php

namespace App\Livewire\Sales;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Purchase;
use App\Models\Warehouse;
use App\Models\Stock;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Credit;
use App\Models\StockHistory;
use App\Models\CreditPayment;
use App\Facades\UserHelperFacade as UserHelper;
use App\Traits\HasFlashMessages;
use App\Traits\HasItemSelection;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

#[Layout('layouts.app')]

class Create extends Component
{
    use HasFlashMessages, HasItemSelection;
    protected $listeners = [
        'customerSelected',
        'itemSelected',
    ];

    // Properties to store calculated totals
    public $subtotal = 0;
    public $taxAmount = 0;
    public $totalAmount = 0;
    public $shippingAmount = 0;

    // Form data
    public $form = [
        'reference_no' => '',
        'customer_id' => '',
        'is_walking_customer' => false,
        'warehouse_id' => '',
        'branch_id' => '',
        'sale_date' => '',
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'transaction_number' => '',
        'bank_account_id' => '',
        'receiver_account_holder' => '',
        'receipt_url' => '',
        'advance_amount' => 0,
        'notes' => '',
        'tax' => 0,
        'shipping' => 0,
    ];

    // Collections
    public $items = [];
    public $customers = [];
    public $warehouses = [];
    public $branches = [];
    public $bankAccounts = [];

    // Item being added (extended from trait)
    public $newItem = [
        'item_id' => '',
        'quantity' => 1,
        'sale_method' => 'piece', // 'piece' or specific unit like 'kg', 'g', etc.
        'sale_unit' => 'each', // The specific unit being sold (each, kg, g, 500g, etc.)
        'unit_price' => 0, // Price per sale unit
        'price' => 0, // Calculated price per piece
        'notes' => '',
    ];

    // Search and selection
    public $customerSearch = '';
    public $selectedCustomer = null;
    public $itemSearch = '';
    public $selectedItem = null;
    public $availableStock = 0;
    public $editingItemIndex = null;
    
    // UI state
    public $showConfirmModal = false;
    public $stockWarningType = null;
    public $stockWarningItem = null;

    // Add a property to track the user's location type
    public $userLocationType = null;
    public $userLocationId = null;
    
    // Add property for location selection dropdown
    public $locationSelection = '';

    protected function rules()
    {
        $rules = [
            'form.sale_date' => 'required|date',
            'form.customer_id' => $this->form['is_walking_customer'] ? 'nullable' : 'required|exists:customers,id',
            'form.is_walking_customer' => 'boolean',
            'form.payment_method' => ['required', \Illuminate\Validation\Rule::enum(PaymentMethod::class)],
            'form.tax' => 'nullable|numeric|min:0|max:100',
            'form.shipping' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
        ];

        // Location validation - either branch OR warehouse, not both
        if (!auth()->user()->branch_id && !auth()->user()->warehouse_id) {
            $rules['form.branch_id'] = 'required_without:form.warehouse_id|nullable|exists:branches,id';
            $rules['form.warehouse_id'] = 'required_without:form.branch_id|nullable|exists:warehouses,id';
        }

        // Enforce warehouse access for non-admin users only
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->isGeneralManager()) {
            $rules['form.warehouse_id'] = [
                $rules['form.warehouse_id'] ?? 'nullable',
                function ($attribute, $value, $fail) {
                    if (!empty($value) && !\App\Facades\UserHelperFacade::hasAccessToWarehouse((int) $value)) {
                        $fail('You do not have permission to access this warehouse.');
                    }
                }
            ];
        }

        // Payment method specific validations
        if (in_array($this->form['payment_method'], ['telebirr', 'bank_transfer'])) {
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

        if ($this->form['payment_method'] === 'bank_transfer') {
            $rules['form.bank_account_id'] = 'required|exists:bank_accounts,id';
        }

        if ($this->form['payment_method'] === 'credit_advance') {
            $rules['form.advance_amount'] = 'required|numeric|min:0.01|lt:' . $this->totalAmount;
        }

        return $rules;
    }

    protected $messages = [
        'form.customer_id.required' => 'Please select a customer or check walking customer.',
        'form.branch_id.required_without' => 'Please select either a branch or warehouse.',
        'form.warehouse_id.required_without' => 'Please select either a branch or warehouse.',
        'items.required' => 'Please add at least one item to the sale.',
        'items.min' => 'Please add at least one item to the sale.',
        'form.transaction_number.required' => 'Transaction number is required for this payment method.',
        'form.transaction_number.min' => 'Transaction number must be at least 5 characters.',
        'form.bank_account_id.required' => 'Please select a bank account.',
        'form.bank_account_id.exists' => 'Selected bank account is invalid.',
        'form.advance_amount.required' => 'Please enter an advance amount.',
        'form.advance_amount.lt' => 'Advance amount must be less than the total amount.',
    ];

    public function mount(): void
    {
        // Initialize items as an empty array if not already set
        $this->items = is_array($this->items) ? $this->items : [];
        
        $this->form = [
            'sale_date' => date('Y-m-d'),
            'reference_no' => $this->generateReferenceNumber(),
            'customer_id' => '',
            'is_walking_customer' => false,
            'warehouse_id' => '',
            'branch_id' => '',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'tax' => 0,
            'shipping' => 0,
            'transaction_number' => '',
            'receiver_bank_name' => '',
            'receiver_account_holder' => '',
            'receiver_account_number' => '',
            'advance_amount' => 0,
            'notes' => '',
        ];

        // Initialize newItem with sale_unit
        $this->newItem = [
            'item_id' => '',
            'quantity' => 1,
            'sale_method' => 'piece',
            'sale_unit' => 'each',
            'unit_price' => 0,
            'price' => 0,
            'notes' => '',
        ];

        // Load initial data
        $this->loadCustomers();
        $this->loadLocations();
        
        // Ensure bankAccounts is properly initialized
        try {
            $this->bankAccounts = BankAccount::where('is_active', true)->orderBy('account_name')->get();
        } catch (\Exception $e) {
            $this->bankAccounts = collect([]);
        }
        
        // Auto-set location based on user assignment
        $user = auth()->user();
        
        if ($user->warehouse_id) {
            // Warehouse user - auto-select their warehouse
            $this->form['warehouse_id'] = $user->warehouse_id;
            $this->userLocationType = 'warehouse';
            $this->userLocationId = $user->warehouse_id;
            $this->loadAvailableItems();
        } elseif ($user->isBranchManager() && $user->branch_id) {
            // Branch manager - auto-select first warehouse in their branch
            $branchWarehouse = Warehouse::whereHas('branches', function($q) use ($user) {
                $q->where('branches.id', $user->branch_id);
            })->first();
            
            if ($branchWarehouse) {
                $this->form['warehouse_id'] = $branchWarehouse->id;
                $this->loadAvailableItems();
            }
        } elseif ($user->branch_id) {
            // Other branch users - set branch
            $this->form['branch_id'] = $user->branch_id;
            $this->userLocationType = 'branch';
            $this->userLocationId = $user->branch_id;
            $this->loadAvailableItems();
        } else {
            // Auto-select warehouse with most stock
            $warehouseWithStock = Warehouse::select('warehouses.*')
                ->join('stocks', 'warehouses.id', '=', 'stocks.warehouse_id')
                ->where('stocks.quantity', '>', 0)
                ->groupBy('warehouses.id')
                ->orderByRaw('SUM(stocks.quantity) DESC')
                ->first();
                
            if ($warehouseWithStock) {
                $this->form['warehouse_id'] = $warehouseWithStock->id;
                $this->loadAvailableItems();
            } else {
                // Fallback to first warehouse
                $firstWarehouse = Warehouse::first();
                if ($firstWarehouse) {
                    $this->form['warehouse_id'] = $firstWarehouse->id;
                    $this->loadAvailableItems();
                }
            }
        }
    }

    private function loadCustomers(): void
    {
            $this->customers = Customer::where('is_active', true)
            ->when($this->customerSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->customerSearch . '%')
                      ->orWhere('phone', 'like', '%' . $this->customerSearch . '%')
                      ->orWhere('email', 'like', '%' . $this->customerSearch . '%');
                });
            })
                ->orderBy('name')
            ->limit(50)
                ->get();
    }

    private function loadLocations(): void
    {
        $user = auth()->user();
        
        // SuperAdmin and GeneralManager see all locations
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            $this->branches = Branch::orderBy('name')->get();
            $this->warehouses = Warehouse::orderBy('name')->get();
        }
        // Branch Manager sees warehouses in their branch
        elseif ($user->isBranchManager() && $user->branch_id) {
            $this->branches = Branch::where('id', $user->branch_id)->get();
            $this->warehouses = Warehouse::whereHas('branches', function($q) use ($user) {
                $q->where('branches.id', $user->branch_id);
            })->orderBy('name')->get();
        }
        // Users not assigned to specific location see all
        elseif (!$user->branch_id && !$user->warehouse_id) {
            $this->branches = Branch::orderBy('name')->get();
            $this->warehouses = Warehouse::orderBy('name')->get();
        }
    }

    private function loadAvailableItems(): void
    {
        // This method is called when warehouse/branch selection changes
        // Items are loaded dynamically via getFilteredItemOptionsProperty
    }

    /**
     * Implementation of abstract method from HasItemSelection trait
     */
    protected function loadItemsForLocation()
    {
        // Items are loaded dynamically via computed property getFilteredItemOptionsProperty
        // This method satisfies the trait requirement but actual loading is handled elsewhere
    }

    public function updatedCustomerSearch(): void
    {
        $this->loadCustomers();
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $this->form['customer_id'] = $customer->id;
            $this->selectedCustomer = [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ];
            $this->customerSearch = '';
        }
    }

    public function clearCustomer(): void
    {
        $this->form['customer_id'] = '';
        $this->selectedCustomer = null;
        $this->customerSearch = '';
        $this->loadCustomers();
    }

    public function updatedFormIsWalkingCustomer($value): void
    {
        if ($value) {
            // Clear customer selection when walking customer is checked
            $this->form['customer_id'] = '';
            $this->selectedCustomer = null;
            $this->customerSearch = '';
            
            // Force cash payment for walking customers if current method is credit
            if (in_array($this->form['payment_method'], ['full_credit', 'credit_advance'])) {
                $this->form['payment_method'] = 'cash';
                $this->updatePaymentStatus();
            }
        }
    }

    public function updatedFormBranchId($value): void
    {
        if ($value) {
            $this->form['warehouse_id'] = ''; // Clear warehouse when branch is selected
            $this->loadAvailableItems();
        }
    }

    public function updatedFormWarehouseId($value): void
    {
        if ($value) {
            $this->form['branch_id'] = ''; // Clear branch when warehouse is selected
            $this->loadAvailableItems();
        }
    }

    public function updatedLocationSelection($value)
    {
        if ($value) {
            if (str_starts_with($value, 'branch_')) {
                $this->form['branch_id'] = str_replace('branch_', '', $value);
                $this->form['warehouse_id'] = '';
            } elseif (str_starts_with($value, 'warehouse_')) {
                $this->form['warehouse_id'] = str_replace('warehouse_', '', $value);
                $this->form['branch_id'] = '';
            }
            $this->loadAvailableItems();
        }
    }

    public function selectItem(int $itemId): void
    {
        $item = Item::find($itemId);
        
        if ($item) {
            // Get or create stock record
            $stock = Stock::firstOrCreate(
                [
                    'warehouse_id' => $this->form['warehouse_id'],
                    'item_id' => $item->id
                ],
                [
                    'quantity' => 0,
                    'piece_count' => 0,
                    'total_units' => 0,
                    'current_piece_units' => $item->unit_quantity ?? 1
                ]
            );
            
            $this->selectedItem = [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'selling_price' => $item->selling_price ?? 0,
                'selling_price_per_unit' => $item->selling_price_per_unit ?? 0,
                'unit_quantity' => $item->unit_quantity ?? 1,
                'item_unit' => $item->item_unit ?? 'piece',
            ];
            $this->newItem['item_id'] = $item->id;
            
            // Calculate available stock consistently
            $stockValue = max($stock->piece_count ?? 0, $stock->quantity ?? 0);
            
            // If stock is 0, check purchase history and sync
            if ($stockValue <= 0) {
                $totalPurchased = \DB::table('purchase_items')
                    ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                    ->where('purchase_items.item_id', $item->id)
                    ->where('purchases.warehouse_id', $this->form['warehouse_id'])
                    ->sum('purchase_items.quantity');
                
                if ($totalPurchased > 0) {
                    $stock->update([
                        'quantity' => $totalPurchased,
                        'piece_count' => $totalPurchased
                    ]);
                    $stockValue = $totalPurchased;
                }
            }
            
            // Default to each method
            $this->newItem['sale_method'] = 'piece';
            $this->newItem['sale_unit'] = 'each';
            
            // Set pricing and stock
            $this->newItem['unit_price'] = $item->selling_price ?? 0;
            $this->newItem['price'] = $this->newItem['unit_price'];
            $this->availableStock = $stockValue;
            $this->newItem['quantity'] = 1;
            
            $this->itemSearch = '';
            
            // Show warning if stock is zero or negative, but don't prevent selection
            if ($this->availableStock <= 0) {
                $this->stockWarningType = 'out_of_stock';
                $this->stockWarningItem = [
                    'name' => $item->name,
                    'price' => $this->newItem['price'],
                    'stock' => $this->availableStock
                ];
                return;
            }
        }
    }

    public function clearSelectedItem(): void
    {
        $this->newItem = [
            'item_id' => '',
            'quantity' => 1,
            'sale_method' => 'piece',
            'sale_unit' => 'each',
            'unit_price' => 0,
            'price' => 0,
            'notes' => '',
        ];
        $this->selectedItem = null;
        $this->availableStock = 0;
        $this->itemSearch = '';
    }

    public function addItem(): void
    {
        // Basic validation
        $this->validate([
            'newItem.item_id' => 'required|exists:items,id',
            'newItem.quantity' => 'required|numeric|min:1',
            'newItem.price' => 'required|numeric|min:0.01',
        ], [
            'newItem.item_id.required' => 'Please select an item',
            'newItem.quantity.required' => 'Please enter quantity',
            'newItem.quantity.min' => 'Quantity must be at least 1',
            'newItem.price.required' => 'Please enter price',
            'newItem.price.min' => 'Price must be greater than zero',
        ]);
        
        // Validate selling price is not below cost price
        $item = Item::find($this->newItem['item_id']);
        $saleMethod = $this->newItem['sale_method'] ?? 'piece';
        $sellingPrice = (float)$this->newItem['price'];
        
        if ($saleMethod === 'piece') {
            $costPrice = (float)($item->cost_price ?? 0);
            if ($sellingPrice < $costPrice) {
                $this->addError('newItem.price', 'Selling price (' . number_format($sellingPrice, 2) . ') cannot be below cost price (' . number_format($costPrice, 2) . ')');
                return;
            }
        } else {
            $costPricePerUnit = (float)($item->cost_price_per_unit ?? 0);
            if ($sellingPrice < $costPricePerUnit) {
                $this->addError('newItem.price', 'Unit selling price (' . number_format($sellingPrice, 2) . ') cannot be below cost price per unit (' . number_format($costPricePerUnit, 2) . ')');
                return;
            }
        }
        
        $requestedQty = floatval($this->newItem['quantity']);
        
        if ($this->availableStock <= 0) {
            $this->stockWarningType = 'out_of_stock';
            $item = Item::find($this->newItem['item_id']);
            $this->stockWarningItem = [
                'name' => $item->name,
                'price' => $this->newItem['price'],
                'stock' => $this->availableStock
            ];
            return;
        }
        
        if ($requestedQty > $this->availableStock) {
            $this->stockWarningType = 'insufficient';
            $item = Item::find($this->newItem['item_id']);
            $this->stockWarningItem = [
                'name' => $item->name,
                'available' => $this->availableStock,
                'requested' => $requestedQty,
                'deficit' => $requestedQty - $this->availableStock,
                'price' => $this->newItem['price']
            ];
            return;
        }
        
        $this->processAddItem();
    }
    

    


    
    private function processAddItem()
    {
        if (empty($this->newItem['item_id'])) {
            return;
        }
        
        $item = Item::find($this->newItem['item_id']);
        if (!$item) {
            return;
        }

        $quantity = floatval($this->newItem['quantity']);
        $price = floatval($this->newItem['price']);
        $subtotal = $quantity * $price;

        $itemData = [
            'item_id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'unit' => $item->unit ?? '',
            'unit_quantity' => $item->unit_quantity ?? 1,
            'item_unit' => $item->item_unit ?? 'piece',
            'quantity' => $quantity,
            'sale_method' => $this->newItem['sale_method'],
            'price' => $price,
            'subtotal' => $subtotal,
            'notes' => $this->newItem['notes'] ?? null,
        ];

        if ($this->editingItemIndex !== null) {
            $this->items[$this->editingItemIndex] = $itemData;
            $this->editingItemIndex = null;
            $this->notify('Item updated successfully', 'success');
        } else {
            $this->items[] = $itemData;
            $this->notify('Item added successfully', 'success');
        }

        $this->updateTotals();
        $this->clearSelectedItem();
    }
    
    public function editItem(int $index): void
    {
        if (!isset($this->items[$index])) {
            return;
        }
        
        $item = $this->items[$index];
        $this->editingItemIndex = $index;
        
        // Load item data into form
        $this->newItem = [
            'item_id' => $item['item_id'],
            'quantity' => $item['quantity'],
            'sale_method' => $item['sale_method'] ?? 'piece',
            'unit_price' => $item['unit_quantity'] ? $item['price'] / $item['unit_quantity'] : $item['price'],
            'price' => $item['price'],
            'notes' => $item['notes'] ?? '',
        ];
        
        // Set selected item
        $itemModel = Item::find($item['item_id']);
        if ($itemModel) {
        $this->selectedItem = [
                'id' => $itemModel->id,
                'name' => $itemModel->name,
                'sku' => $itemModel->sku,
                'selling_price' => $item['price'],
                'selling_price_per_unit' => $item['unit_quantity'] ? $item['price'] / $item['unit_quantity'] : $item['price'],
                'unit_quantity' => $item['unit_quantity'] ?? 1,
                'item_unit' => $itemModel->item_unit ?? 'piece',
            ];
            
            // Get available stock
            if ($this->form['warehouse_id']) {
                $stock = Stock::where('item_id', $item['item_id'])
                    ->where('warehouse_id', $this->form['warehouse_id'])
                    ->first();
                $this->availableStock = $stock ? $stock->quantity + $item['quantity'] : $item['quantity'];
            } else {
                $warehouseIds = [];
                try {
                    $warehouseIds = DB::table('branch_warehouse')
                        ->where('branch_id', $this->form['branch_id'])
                        ->pluck('warehouse_id')
                        ->toArray();
                } catch (\Exception $e) {
                    // Fallback to empty array
                }
        
                $totalStock = 0;
                if (!empty($warehouseIds)) {
                    try {
                        $totalStock = Stock::where('item_id', $item['item_id'])
                            ->whereIn('warehouse_id', $warehouseIds)
                            ->sum('quantity');
                    } catch (\Exception $e) {
                        // Fallback to 0 stock
                    }
                }
        
                $this->availableStock = $totalStock + $item['quantity'];
            }
        }
    }
    
    public function cancelEdit(): void
    {
        $this->editingItemIndex = null;
        $this->clearSelectedItem();
    }

    public function removeItem(int $index): void
    {
        if (!isset($this->items[$index])) {
            return;
        }

        $removedItem = $this->items[$index];
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        
        $this->updateTotals();
        
        $this->notify($removedItem['name'] . ' removed from cart', 'success');
    }

    public function clearCart(): void
    {
        $this->items = [];
        $this->updateTotals();
        $this->notify('Cart cleared', 'success');
    }

    public function loadItemOptions(): void
    {
        // Items are loaded dynamically via computed property
        $this->notify('Items refreshed successfully', 'success');
    }

    private function updateTotals(): void
    {
        $this->subtotal = 0;
        if (is_array($this->items)) {
            $this->subtotal = collect($this->items)->sum('subtotal');
        }
        
        // Calculate tax
        $taxRate = $this->form['tax'] ?? 0;
        $this->taxAmount = round($this->subtotal * ($taxRate / 100), 2);
        
        // Calculate shipping
        $this->shippingAmount = $this->form['shipping'] ?? 0;
        
        // Calculate total
        $this->totalAmount = round($this->subtotal + $this->taxAmount + $this->shippingAmount, 2);
        
        // Update payment status based on payment method
        $this->updatePaymentStatus();
    }

    public function updatedFormTax(): void
    {
        $this->updateTotals();
    }

    public function updatedFormShipping(): void
    {
        $this->updateTotals();
    }

    public function updatedFormTransactionNumber(): void
    {
        $this->resetErrorBag('form.transaction_number');
    }

    public function updatedFormBankAccountId(): void
    {
        $this->resetErrorBag('form.bank_account_id');
    }

    public function updatedFormCustomerId(): void
    {
        $this->resetErrorBag('form.customer_id');
    }

    public function updatedFormPaymentMethod($value): void
    {
        // Reset payment-specific fields
        $this->form['transaction_number'] = '';
        $this->form['bank_account_id'] = '';
        $this->form['receiver_account_holder'] = '';
        $this->form['receipt_url'] = '';
        $this->form['advance_amount'] = 0;

        // Clear validation errors for payment-specific fields
        $this->resetErrorBag([
            'form.transaction_number',
            'form.bank_account_id',
            'form.advance_amount'
        ]);

        $this->updatePaymentStatus();
        $this->updateTotals();
    }

    private function updatePaymentStatus(): void
    {
        switch ($this->form['payment_method']) {
            case 'cash':
            case 'bank_transfer':
            case 'telebirr':
                $this->form['payment_status'] = 'paid';
                break;
            case 'credit_advance':
                $this->form['payment_status'] = 'partial';
                if ($this->totalAmount > 0 && (empty($this->form['advance_amount']) || $this->form['advance_amount'] == 0)) {
                    $this->form['advance_amount'] = round($this->totalAmount * 0.2, 2); // Default 20%
                }
                break;
            case 'full_credit':
                $this->form['payment_status'] = 'due';
                break;
        }
    }

    public function save()
    {
        try {
            // Validate form
            $this->validate();

            // Additional validation
            if (empty($this->items)) {
                throw new \Exception('Please add at least one item to the sale.');
            }

            // Validate location selection
            if (empty($this->form['warehouse_id'])) {
                throw new \Exception('Please select a warehouse.');
            }
            
            // Resolve branch_id for credit creation
            $branchId = $this->resolveBranchId();
            
            if (!$branchId) {
                throw new \Exception('Unable to determine branch for this sale. Please ensure warehouse is properly assigned to a branch.');
            }

            DB::beginTransaction();

            $branchId = $this->resolveBranchId();
            $sale = new Sale();
            $sale->reference_no = $this->form['reference_no'];
            $sale->customer_id = $this->form['is_walking_customer'] ? null : $this->form['customer_id'];
            $sale->is_walking_customer = $this->form['is_walking_customer'];
            
            if (!empty($this->form['warehouse_id'])) {
                $sale->warehouse_id = $this->form['warehouse_id'];
                $sale->branch_id = null;
            } else {
                $sale->branch_id = $branchId;
                $sale->warehouse_id = null;
            }
            $sale->user_id = auth()->id();
            $sale->sale_date = $this->form['sale_date'];
            $sale->payment_method = $this->form['payment_method'];
            $sale->payment_status = $this->form['payment_status'];
            $sale->status = 'pending';
            $sale->tax = $this->taxAmount;
            $sale->shipping = $this->shippingAmount;
            $sale->discount = 0;
            $sale->total_amount = $this->totalAmount;
            $sale->notes = $this->form['notes'];

            // Set payment-specific fields
            switch ($this->form['payment_method']) {
                case 'cash':
                case 'bank_transfer':
                case 'telebirr':
                    $sale->paid_amount = $this->totalAmount;
                    $sale->due_amount = 0;
                    $sale->payment_status = 'paid';
                    break;
                case 'credit_advance':
                    $sale->paid_amount = $this->form['advance_amount'];
                    $sale->advance_amount = $this->form['advance_amount'];
                    $sale->due_amount = $this->totalAmount - $this->form['advance_amount'];
                    $sale->payment_status = 'partial';
                    break;
                case 'credit_full':
                    $sale->paid_amount = 0;
                    $sale->due_amount = $this->totalAmount;
                    $sale->payment_status = 'due';
                    break;
            }

            if (in_array($this->form['payment_method'], ['telebirr', 'bank_transfer'], true)) {
                $sale->transaction_number = $this->form['transaction_number'];
            }

            if ($this->form['payment_method'] === 'telebirr') {
                $sale->receiver_account_holder = $this->form['receiver_account_holder'] ?? null;
                $sale->receipt_url = $this->form['receipt_url'];
            }

            if ($this->form['payment_method'] === 'bank_transfer') {
                $sale->bank_account_id = $this->form['bank_account_id'] ?? null;
            }


            $sale->save();

            foreach ($this->items as $item) {
                if (empty($item['item_id'])) {
                    throw new \Exception('Invalid item data: missing item ID');
                }
                
                if (empty($item['quantity']) || $item['quantity'] <= 0) {
                    throw new \Exception('Invalid quantity for item. Quantity must be greater than zero.');
                }
                
                if (empty($item['price']) || $item['price'] <= 0) {
                    throw new \Exception('Invalid price for item. Price must be greater than zero.');
                }
                
                $saleItem = new SaleItem();
                $saleItem->sale_id = $sale->id;
                $saleItem->item_id = $item['item_id'];
                $saleItem->quantity = $item['quantity'];
                $saleItem->sale_method = $item['sale_method'] ?? 'piece';
                $saleItem->unit_price = $item['price'];
                $saleItem->subtotal = $item['subtotal'];
                $saleItem->notes = $item['notes'] ?? null;
                $saleItem->save();
            }

            $sale->processSale();

            DB::commit();
            
            return redirect()->route('admin.sales.index')
                ->with('success', 'Sale completed successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->notify('Please fix the validation errors.', 'error');
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('general', 'Sale creation failed: ' . $e->getMessage());
            $this->notify('Error: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    public function cancel()
    {
        return redirect()->route('admin.sales.index');
    }

    private function generateReferenceNumber(): string
    {
        $prefix = 'SALE-';
        $date = date('Ymd');
        $count = Sale::whereDate('created_at', today())->count() + 1;
        return $prefix . $date . '-' . str_pad((string)$count, 5, '0', STR_PAD_LEFT);
    }

    private function notify(string $message, string $type = 'info'): void
    {
        match($type) {
            'success' => $this->flashSuccess($message),
            default => $this->dispatch('notify', ['type' => $type, 'message' => $message])
        };
    }





    // Computed properties
    public function getFilteredCustomersProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->customers;
    }

    public function getFilteredItemOptionsProperty(): array
    {
        if (empty($this->itemSearch) || strlen(trim($this->itemSearch)) < 2) {
            return [];
        }
        
        $warehouseId = $this->form['warehouse_id'];
        if (empty($warehouseId)) {
            return [];
        }
        
        $searchTerm = trim($this->itemSearch);
        
        $results = Item::forUser(auth()->user())
            ->where('is_active', true)
            ->where(function ($query) use ($searchTerm) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                      ->orWhereRaw('LOWER(sku) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                      ->orWhereRaw('LOWER(barcode) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
            })
            ->limit(15)
            ->get()
            ->map(function ($item) use ($warehouseId) {
                $stock = Stock::firstOrCreate(
                    [
                        'warehouse_id' => $warehouseId,
                        'item_id' => $item->id
                    ],
                    [
                        'quantity' => 0,
                        'piece_count' => 0,
                        'total_units' => 0,
                        'current_piece_units' => $item->unit_quantity ?? 1
                    ]
                );
                
                $stockValue = max(
                    $stock->piece_count ?? 0,
                    $stock->quantity ?? 0
                );
                
                if ($stockValue <= 0) {
                    $totalPurchased = \DB::table('purchase_items')
                        ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                        ->where('purchase_items.item_id', $item->id)
                        ->where('purchases.warehouse_id', $warehouseId)
                        ->sum('purchase_items.quantity');
                    
                    if ($totalPurchased > 0) {
                        $stock->update([
                            'quantity' => $totalPurchased,
                            'piece_count' => $totalPurchased
                        ]);
                        $stockValue = $totalPurchased;
                    }
                }
                
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'quantity' => $stockValue,
                    'total_units' => $stockValue * ($item->unit_quantity ?? 1),
                    'selling_price_per_unit' => $item->selling_price_per_unit ?? 0,
                    'unit_quantity' => $item->unit_quantity ?? 1,
                    'item_unit' => $item->item_unit ?? 'piece',
                ];
            })
            ->toArray();
            
        return $results;
    }

    public function isFormReady(): bool
    {
        return !empty($this->form['customer_id']) && 
               !empty($this->items) &&
               ($this->form['branch_id'] || $this->form['warehouse_id']) &&
               $this->isPaymentMethodValid();
    }

    private function isPaymentMethodValid(): bool
    {
        switch ($this->form['payment_method']) {
            case 'telebirr':
                return !empty($this->form['transaction_number']) && 
                       strlen((string) $this->form['transaction_number']) >= 5;
            case 'bank_transfer':
                return !empty($this->form['transaction_number']) && 
                       strlen((string) $this->form['transaction_number']) >= 5 &&
                       !empty($this->form['bank_account_id']);
            case 'credit_advance':
                return $this->form['advance_amount'] > 0 && 
                       $this->form['advance_amount'] < $this->totalAmount;
            default:
                return true;
        }
    }

    /**
     * Validate form and show confirmation modal if valid
     */
    public function validateAndShowModal(): bool
    {
        try {
            $this->validate();
            $this->dispatch('showConfirmationModal');
            return true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Manually add validation errors to the error bag
            foreach ($e->validator->errors()->getMessages() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
            $this->dispatch('scrollToFirstError');
            return false;
        }
    }

    /**
     * Confirm sale - handles modal dismissal and form submission
     */
    public function confirmSale()
    {
        // Clear any previous validation errors
        $this->resetErrorBag();
        
        // First validate basic requirements
        $validationErrors = [];
        
        if (empty($this->items) || count($this->items) === 0) {
            $validationErrors['items'] = 'Cannot create sale: No items added';
        }

        if (!$this->form['is_walking_customer'] && empty($this->form['customer_id'])) {
            $validationErrors['form.customer_id'] = 'Cannot create sale: Please select a customer or check walking customer';
        }

        if (empty($this->form['warehouse_id']) && empty($this->form['branch_id'])) {
            $validationErrors['form.warehouse_id'] = 'Cannot create sale: Please select either a warehouse or branch';
        }

        // Check payment method specific validations

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
            
            $this->notify('❌ Please fix the errors before creating the sale.', 'error');
            
            // Close the modal and show errors on the form
            $this->dispatch('closeSaleModal');
            $this->dispatch('scrollToFirstError');
            
            return false;
        }

        // Show loading state
        $this->notify('💾 Creating sale...', 'info');

        try {
            // Call the save method directly
            $result = $this->save();
            

            
            if ($result === false) {
                $this->dispatch('closeSaleModal');
                
                if ($this->getErrorBag()->isEmpty()) {
                    $this->addError('general', 'Sale creation failed. Please check your input and try again.');
                    $this->notify('❌ Sale creation failed. Please review the form and try again.', 'error');
                }
                
                return false;
            }
            
            $this->dispatch('closeSaleModal');
            $this->dispatch('saleCompleted');
            
            return $result;
            
        } catch (\Exception $e) {
            $this->dispatch('closeSaleModal');
            
            $this->addError('general', 'Sale creation failed: ' . $e->getMessage());
            $this->notify('❌ Sale creation failed: ' . $e->getMessage(), 'error');
            
            return false;
        }
    }

    public function updatedNewItemSaleMethod($value): void
    {
        $this->newItem['quantity'] = $value === 'unit' ? 0.01 : 1;
        
        if ($this->selectedItem && $this->form['warehouse_id']) {
            $stock = Stock::where('warehouse_id', $this->form['warehouse_id'])
                ->where('item_id', $this->selectedItem['id'])
                ->first();
                
            if ($stock) {
                if ($value === 'piece') {
                    // Selling by Each - use piece price
                    $this->newItem['unit_price'] = $this->selectedItem['selling_price'] ?? 0;
                    $this->newItem['price'] = $this->newItem['unit_price'];
                    $this->availableStock = $stock->piece_count ?? $stock->quantity ?? 0;
                } else {
                    // Selling by unit (kg, liter, etc.) - use unit price
                    $this->newItem['unit_price'] = $this->selectedItem['selling_price_per_unit'] ?? 0;
                    $this->newItem['price'] = $this->newItem['unit_price'];
                    // Calculate available units: total pieces * unit_quantity
                    $totalPieces = $stock->piece_count ?? $stock->quantity ?? 0;
                    $unitQuantity = $this->selectedItem['unit_quantity'] ?? 1;
                    $this->availableStock = $totalPieces * $unitQuantity;
                }
            }
        }
    }
    
    public function updatedNewItemUnitPrice($value): void
    {
        $this->newItem['price'] = (float)$value;
    }
    
    public function updatedNewItemQuantity($value): void
    {
        // Recalculate total when quantity changes
        $quantity = (float)($value ?? 0);
        $unitPrice = (float)($this->newItem['unit_price'] ?? 0);
        $this->newItem['price'] = $unitPrice; // Keep unit price, not total
    }
    
    public function updatedNewItemPrice($value): void
    {
        // Update unit price when total price changes
        $this->newItem['unit_price'] = (float)$value;
    }
    
    /**
     * Get available stock for a specific unit
     */
    public function getAvailableStockForUnit($saleUnit): float
    {
        if (!$this->selectedItem || !$this->form['warehouse_id']) {
            return 0.0;
        }
        
        $stock = Stock::where('warehouse_id', $this->form['warehouse_id'])
            ->where('item_id', $this->selectedItem['id'])
            ->first();
            
        if (!$stock) {
            return 0.0;
        }
        
        $totalPieces = (float)max($stock->piece_count ?? 0, $stock->quantity ?? 0);
        
        if ($saleUnit === 'each') {
            return $totalPieces;
        }
        
        $unitQuantity = $this->selectedItem['unit_quantity'] ?? 1;
        $baseUnit = $this->selectedItem['item_unit'] ?? 'each';
        $totalBaseUnits = $totalPieces * $unitQuantity;
        
        // Only allow conversions within the same measurement type
        $weightUnits = ['kg', 'g', 'ton', 'lb', 'oz'];
        $volumeUnits = ['liter', 'ml', 'gallon', 'cup'];
        $lengthUnits = ['meter', 'cm', 'mm', 'inch', 'ft'];
        $areaUnits = ['sqm', 'sqft', 'acre'];
        $packagingUnits = ['pack', 'box', 'case', 'dozen', 'pair', 'set', 'roll', 'sheet', 'bottle', 'can', 'bag', 'sack'];
        
        // Weight conversions - only if base unit is weight
        if (in_array($baseUnit, $weightUnits) && in_array($saleUnit, $weightUnits)) {
            return match($saleUnit) {
                'kg' => $baseUnit === 'kg' ? $totalBaseUnits : ($baseUnit === 'g' ? $totalBaseUnits / 1000 : ($baseUnit === 'ton' ? $totalBaseUnits * 1000 : ($baseUnit === 'lb' ? $totalBaseUnits / 2.20462 : $totalBaseUnits / 35.274))),
                'g' => $baseUnit === 'g' ? $totalBaseUnits : ($baseUnit === 'kg' ? $totalBaseUnits * 1000 : ($baseUnit === 'ton' ? $totalBaseUnits * 1000000 : ($baseUnit === 'lb' ? $totalBaseUnits * 453.592 : $totalBaseUnits * 28.3495))),
                'ton' => $baseUnit === 'ton' ? $totalBaseUnits : ($baseUnit === 'kg' ? $totalBaseUnits / 1000 : ($baseUnit === 'g' ? $totalBaseUnits / 1000000 : ($baseUnit === 'lb' ? $totalBaseUnits / 2204.62 : $totalBaseUnits / 35274))),
                'lb' => $baseUnit === 'lb' ? $totalBaseUnits : ($baseUnit === 'kg' ? $totalBaseUnits * 2.20462 : ($baseUnit === 'g' ? $totalBaseUnits / 453.592 : ($baseUnit === 'ton' ? $totalBaseUnits * 2204.62 : $totalBaseUnits / 16))),
                'oz' => $baseUnit === 'oz' ? $totalBaseUnits : ($baseUnit === 'kg' ? $totalBaseUnits * 35.274 : ($baseUnit === 'g' ? $totalBaseUnits / 28.3495 : ($baseUnit === 'ton' ? $totalBaseUnits * 35274 : $totalBaseUnits * 16))),
                default => $totalPieces
            };
        }
        
        // Volume conversions - only if base unit is volume
        if (in_array($baseUnit, $volumeUnits) && in_array($saleUnit, $volumeUnits)) {
            return match($saleUnit) {
                'liter' => $baseUnit === 'liter' ? $totalBaseUnits : ($baseUnit === 'ml' ? $totalBaseUnits / 1000 : ($baseUnit === 'gallon' ? $totalBaseUnits * 3.78541 : $totalBaseUnits / 4.22675)),
                'ml' => $baseUnit === 'ml' ? $totalBaseUnits : ($baseUnit === 'liter' ? $totalBaseUnits * 1000 : ($baseUnit === 'gallon' ? $totalBaseUnits * 3785.41 : $totalBaseUnits * 236.588)),
                'gallon' => $baseUnit === 'gallon' ? $totalBaseUnits : ($baseUnit === 'liter' ? $totalBaseUnits / 3.78541 : ($baseUnit === 'ml' ? $totalBaseUnits / 3785.41 : $totalBaseUnits / 16)),
                'cup' => $baseUnit === 'cup' ? $totalBaseUnits : ($baseUnit === 'liter' ? $totalBaseUnits * 4.22675 : ($baseUnit === 'ml' ? $totalBaseUnits / 236.588 : $totalBaseUnits * 16)),
                default => $totalPieces
            };
        }
        
        // Length conversions - only if base unit is length
        if (in_array($baseUnit, $lengthUnits) && in_array($saleUnit, $lengthUnits)) {
            return match($saleUnit) {
                'meter' => $baseUnit === 'meter' ? $totalBaseUnits : ($baseUnit === 'cm' ? $totalBaseUnits / 100 : ($baseUnit === 'mm' ? $totalBaseUnits / 1000 : ($baseUnit === 'inch' ? $totalBaseUnits / 39.3701 : $totalBaseUnits / 3.28084))),
                'cm' => $baseUnit === 'cm' ? $totalBaseUnits : ($baseUnit === 'meter' ? $totalBaseUnits * 100 : ($baseUnit === 'mm' ? $totalBaseUnits / 10 : ($baseUnit === 'inch' ? $totalBaseUnits * 2.54 : $totalBaseUnits * 30.48))),
                'mm' => $baseUnit === 'mm' ? $totalBaseUnits : ($baseUnit === 'meter' ? $totalBaseUnits * 1000 : ($baseUnit === 'cm' ? $totalBaseUnits * 10 : ($baseUnit === 'inch' ? $totalBaseUnits * 25.4 : $totalBaseUnits * 304.8))),
                'inch' => $baseUnit === 'inch' ? $totalBaseUnits : ($baseUnit === 'meter' ? $totalBaseUnits * 39.3701 : ($baseUnit === 'cm' ? $totalBaseUnits / 2.54 : ($baseUnit === 'mm' ? $totalBaseUnits / 25.4 : $totalBaseUnits * 12))),
                'ft' => $baseUnit === 'ft' ? $totalBaseUnits : ($baseUnit === 'meter' ? $totalBaseUnits * 3.28084 : ($baseUnit === 'cm' ? $totalBaseUnits / 30.48 : ($baseUnit === 'mm' ? $totalBaseUnits / 304.8 : $totalBaseUnits / 12))),
                default => $totalPieces
            };
        }
        
        // Area conversions - only if base unit is area
        if (in_array($baseUnit, $areaUnits) && in_array($saleUnit, $areaUnits)) {
            return match($saleUnit) {
                'sqm' => $baseUnit === 'sqm' ? $totalBaseUnits : ($baseUnit === 'sqft' ? $totalBaseUnits / 10.7639 : $totalBaseUnits * 4046.86),
                'sqft' => $baseUnit === 'sqft' ? $totalBaseUnits : ($baseUnit === 'sqm' ? $totalBaseUnits * 10.7639 : $totalBaseUnits * 43560),
                'acre' => $baseUnit === 'acre' ? $totalBaseUnits : ($baseUnit === 'sqm' ? $totalBaseUnits / 4046.86 : $totalBaseUnits / 43560),
                default => $totalPieces
            };
        }
        
        // Packaging units - always 1:1 with pieces
        if (in_array($saleUnit, $packagingUnits)) {
            return $totalPieces;
        }
        
        // Default fallback for incompatible conversions
        return $totalPieces;
    }

    /**
     * Update pricing when sale unit changes
     */
    public function updatedNewItemSaleUnit($saleUnit): void
    {
        if (!$this->selectedItem) {
            return;
        }

        if ($saleUnit === 'each') {
            $this->newItem['unit_price'] = $this->selectedItem['selling_price'] ?? 0;
            $this->newItem['quantity'] = 1;
        } else {
            $basePrice = $this->selectedItem['selling_price'] ?? 0;
            $unitQuantity = $this->selectedItem['unit_quantity'] ?? 1;
            $baseUnit = $this->selectedItem['item_unit'] ?? 'each';
            $basePricePerUnit = $basePrice / $unitQuantity;
            
            // Define measurement type groups
            $weightUnits = ['kg', 'g', 'ton', 'lb', 'oz'];
            $volumeUnits = ['liter', 'ml', 'gallon', 'cup'];
            $lengthUnits = ['meter', 'cm', 'mm', 'inch', 'ft'];
            $areaUnits = ['sqm', 'sqft', 'acre'];
            $packagingUnits = ['pack', 'box', 'case', 'dozen', 'pair', 'set', 'roll', 'sheet', 'bottle', 'can', 'bag', 'sack'];
            
            // Only allow conversions within the same measurement type
            if (in_array($baseUnit, $weightUnits) && in_array($saleUnit, $weightUnits)) {
                $this->newItem['unit_price'] = match($saleUnit) {
                    'kg' => $baseUnit === 'kg' ? $basePricePerUnit : ($baseUnit === 'g' ? $basePricePerUnit * 1000 : ($baseUnit === 'ton' ? $basePricePerUnit / 1000 : ($baseUnit === 'lb' ? $basePricePerUnit * 2.20462 : $basePricePerUnit * 35.274))),
                    'g' => $baseUnit === 'g' ? $basePricePerUnit : ($baseUnit === 'kg' ? $basePricePerUnit / 1000 : ($baseUnit === 'ton' ? $basePricePerUnit / 1000000 : ($baseUnit === 'lb' ? $basePricePerUnit / 453.592 : $basePricePerUnit / 28.3495))),
                    'ton' => $baseUnit === 'ton' ? $basePricePerUnit : ($baseUnit === 'kg' ? $basePricePerUnit * 1000 : ($baseUnit === 'g' ? $basePricePerUnit * 1000000 : ($baseUnit === 'lb' ? $basePricePerUnit * 2204.62 : $basePricePerUnit * 35274))),
                    'lb' => $baseUnit === 'lb' ? $basePricePerUnit : ($baseUnit === 'kg' ? $basePricePerUnit / 2.20462 : ($baseUnit === 'g' ? $basePricePerUnit * 453.592 : ($baseUnit === 'ton' ? $basePricePerUnit / 2204.62 : $basePricePerUnit * 16))),
                    'oz' => $baseUnit === 'oz' ? $basePricePerUnit : ($baseUnit === 'kg' ? $basePricePerUnit / 35.274 : ($baseUnit === 'g' ? $basePricePerUnit * 28.3495 : ($baseUnit === 'ton' ? $basePricePerUnit / 35274 : $basePricePerUnit / 16))),
                    default => $basePrice
                };
            }
            elseif (in_array($baseUnit, $volumeUnits) && in_array($saleUnit, $volumeUnits)) {
                $this->newItem['unit_price'] = match($saleUnit) {
                    'liter' => $baseUnit === 'liter' ? $basePricePerUnit : ($baseUnit === 'ml' ? $basePricePerUnit * 1000 : ($baseUnit === 'gallon' ? $basePricePerUnit / 3.78541 : $basePricePerUnit * 4.22675)),
                    'ml' => $baseUnit === 'ml' ? $basePricePerUnit : ($baseUnit === 'liter' ? $basePricePerUnit / 1000 : ($baseUnit === 'gallon' ? $basePricePerUnit / 3785.41 : $basePricePerUnit / 236.588)),
                    'gallon' => $baseUnit === 'gallon' ? $basePricePerUnit : ($baseUnit === 'liter' ? $basePricePerUnit * 3.78541 : ($baseUnit === 'ml' ? $basePricePerUnit * 3785.41 : $basePricePerUnit * 16)),
                    'cup' => $baseUnit === 'cup' ? $basePricePerUnit : ($baseUnit === 'liter' ? $basePricePerUnit / 4.22675 : ($baseUnit === 'ml' ? $basePricePerUnit * 236.588 : $basePricePerUnit / 16)),
                    default => $basePrice
                };
            }
            elseif (in_array($baseUnit, $lengthUnits) && in_array($saleUnit, $lengthUnits)) {
                $this->newItem['unit_price'] = match($saleUnit) {
                    'meter' => $baseUnit === 'meter' ? $basePricePerUnit : ($baseUnit === 'cm' ? $basePricePerUnit * 100 : ($baseUnit === 'mm' ? $basePricePerUnit * 1000 : ($baseUnit === 'inch' ? $basePricePerUnit * 39.3701 : $basePricePerUnit * 3.28084))),
                    'cm' => $baseUnit === 'cm' ? $basePricePerUnit : ($baseUnit === 'meter' ? $basePricePerUnit / 100 : ($baseUnit === 'mm' ? $basePricePerUnit * 10 : ($baseUnit === 'inch' ? $basePricePerUnit / 2.54 : $basePricePerUnit / 30.48))),
                    'mm' => $baseUnit === 'mm' ? $basePricePerUnit : ($baseUnit === 'meter' ? $basePricePerUnit / 1000 : ($baseUnit === 'cm' ? $basePricePerUnit / 10 : ($baseUnit === 'inch' ? $basePricePerUnit / 25.4 : $basePricePerUnit / 304.8))),
                    'inch' => $baseUnit === 'inch' ? $basePricePerUnit : ($baseUnit === 'meter' ? $basePricePerUnit / 39.3701 : ($baseUnit === 'cm' ? $basePricePerUnit * 2.54 : ($baseUnit === 'mm' ? $basePricePerUnit * 25.4 : $basePricePerUnit / 12))),
                    'ft' => $baseUnit === 'ft' ? $basePricePerUnit : ($baseUnit === 'meter' ? $basePricePerUnit / 3.28084 : ($baseUnit === 'cm' ? $basePricePerUnit * 30.48 : ($baseUnit === 'mm' ? $basePricePerUnit * 304.8 : $basePricePerUnit * 12))),
                    default => $basePrice
                };
            }
            elseif (in_array($baseUnit, $areaUnits) && in_array($saleUnit, $areaUnits)) {
                $this->newItem['unit_price'] = match($saleUnit) {
                    'sqm' => $baseUnit === 'sqm' ? $basePricePerUnit : ($baseUnit === 'sqft' ? $basePricePerUnit * 10.7639 : $basePricePerUnit / 4046.86),
                    'sqft' => $baseUnit === 'sqft' ? $basePricePerUnit : ($baseUnit === 'sqm' ? $basePricePerUnit / 10.7639 : $basePricePerUnit / 43560),
                    'acre' => $baseUnit === 'acre' ? $basePricePerUnit : ($baseUnit === 'sqm' ? $basePricePerUnit * 4046.86 : $basePricePerUnit * 43560),
                    default => $basePrice
                };
            }
            elseif (in_array($saleUnit, $packagingUnits)) {
                // Packaging units use base price (1:1 with pieces)
                $this->newItem['unit_price'] = $basePrice;
            }
            else {
                // Incompatible conversion - use base price
                $this->newItem['unit_price'] = $basePrice;
            }
            
            // Set appropriate default quantity
            $this->newItem['quantity'] = match($saleUnit) {
                'g', 'ml', 'mm', 'cm' => 100,
                'oz', 'cup', 'inch' => 10,
                'ton', 'gallon', 'acre' => 0.1,
                default => 1
            };
        }
        
        $this->newItem['price'] = $this->newItem['unit_price'];
    }

    /**
     * Check if the selected item can be sold by unit
     */
    public function canSellByUnit(): bool
    {
        if (!$this->selectedItem) {
            return false;
        }

        $hasValidUnit = !empty($this->selectedItem['item_unit']) && 
                       in_array($this->selectedItem['item_unit'], ['kg', 'liter', 'gram', 'ml']);
        $hasUnitQuantity = ($this->selectedItem['unit_quantity'] ?? 1) > 1;

        return $hasValidUnit && $hasUnitQuantity;
    }

    /**
     * Get available sale units for the selected item
     */
    public function getAvailableSaleUnits(): array
    {
        if (!$this->selectedItem || !$this->canSellByUnit()) {
            return ['each' => 'Each'];
        }

        $baseUnit = $this->selectedItem['item_unit'] ?? 'piece';
        
        try {
            $availableUnits = \App\Services\UnitConversionService::getAvailableSaleUnits($baseUnit);
            
            // Always include "Each" option
            return array_merge(['each' => 'Each'], $availableUnits);
        } catch (\Exception $e) {
            // Fallback to just "Each" if there's an error
            return ['each' => 'Each'];
        }
    }



    public function proceedWithWarning(): void
    {
        $this->processAddItem();
        
        $this->stockWarningType = null;
        $this->stockWarningItem = null;
    }
    
    public function cancelStockWarning(): void
    {
        $this->stockWarningType = null;
        $this->stockWarningItem = null;
    }



    private function resolveBranchId(): ?int
    {
        if (!empty($this->form['branch_id'])) {
            return (int)$this->form['branch_id'];
        }
        
        if (!empty($this->form['warehouse_id'])) {
            $warehouse = Warehouse::with('branches')->find($this->form['warehouse_id']);
            if ($warehouse && $warehouse->branches->isNotEmpty()) {
                return (int)$warehouse->branches->first()->id;
            }
        }
        
        if (auth()->user()->branch_id) {
            return (int)auth()->user()->branch_id;
        }
        
        if (auth()->user()->warehouse_id) {
            $warehouse = Warehouse::with('branches')->find(auth()->user()->warehouse_id);
            if ($warehouse && $warehouse->branches->isNotEmpty()) {
                return (int)$warehouse->branches->first()->id;
            }
        }
        
        $firstBranch = Branch::where('is_active', true)->first();
        if ($firstBranch) {
            return (int)$firstBranch->id;
        }
        
        return null;
    }

    /**
     * Check if a transaction number exists anywhere in the system.
     */
    private function transactionNumberExists(string $transactionNumber): bool
    {
        $number = trim($transactionNumber);

        if ($number === '') {
            return false;
        }

        if (Sale::where('transaction_number', $number)->exists()) {
            return true;
        }

        if (Purchase::where('transaction_number', $number)->exists()) {
            return true;
        }

        $existsInSalePayments = class_exists(SalePayment::class)
            ? SalePayment::where('reference_no', $number)->exists()
            : false;

        if ($existsInSalePayments) {
            return true;
        }

        $existsInCreditPayments = class_exists(CreditPayment::class)
            ? CreditPayment::where('reference_no', $number)->exists()
            : false;

        return $existsInCreditPayments;
    }

    public function getBanksProperty(): array
    {
        $bankService = new \App\Services\BankService();
        return $bankService->getEthiopianBanks();
    }

    public function render()
    {
        return view('livewire.sales.create');
    }
}