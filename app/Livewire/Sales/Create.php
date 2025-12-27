<?php

declare(strict_types=1);

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
        'sale_method' => 'piece', // 'piece' or 'unit' - mutually exclusive
        'unit_price' => 0, // Price per unit (e.g., per kg)
        'price' => 0, // Calculated price per piece
        'notes' => '',
    ];

    // Search and selection
    public $customerSearch = '';
    public $selectedCustomer = null;
    public $itemSearch = '';
    
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

    public function updatedFormIsWalkingCustomer(bool $value): void
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

    public function updatedFormBranchId(?string $value): void
    {
        if ($value) {
            $this->form['warehouse_id'] = ''; // Clear warehouse when branch is selected
            $this->loadAvailableItems();
        }
    }

    public function updatedFormWarehouseId(?string $value): void
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
            
            // Set unit price and price based on current sale method
            if ($this->newItem['sale_method'] === 'piece') {
                $this->newItem['unit_price'] = $item->selling_price ?? 0;
                $this->newItem['price'] = $this->newItem['unit_price'];
                $this->availableStock = $stockValue;
            } else {
                $this->newItem['unit_price'] = $item->selling_price_per_unit ?? 0;
                $this->newItem['price'] = $this->newItem['unit_price'];
                $this->availableStock = $stock->total_units ?? 0;
            }
            
            $this->itemSearch = '';
            
            // Show warning if stock is zero or negative, but don't prevent selection
            if ($this->availableStock <= 0) {
                $this->stockWarningType = 'out_of_stock';
                $this->stockWarningItem = [
                    'name' => $item->name,
                    'price' => $this->newItem['price'],
                    'stock' => $this->availableStock
                ];
                // Don't clear selectedItem - let user proceed with warning
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

    public function updatedFormPaymentMethod(string $value): void
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

    public function updatedNewItemSaleMethod(string $value): void
    {
        $this->newItem['quantity'] = 1;
        
        if ($this->selectedItem && $this->form['warehouse_id']) {
            $stock = Stock::where('warehouse_id', $this->form['warehouse_id'])
                ->where('item_id', $this->selectedItem['id'])
                ->first();
                
            if ($stock) {
                if ($value === 'piece') {
                    $this->newItem['unit_price'] = $this->selectedItem['selling_price'] ?? 0;
                    $this->newItem['price'] = $this->newItem['unit_price'];
                    $this->availableStock = $stock->piece_count ?? $stock->quantity ?? 0;
                } else {
                    $this->newItem['unit_price'] = $this->selectedItem['selling_price_per_unit'] ?? 0;
                    $this->newItem['price'] = $this->newItem['unit_price'];
                    $this->availableStock = $stock->total_units;
                }
            }
        }
    }
    
    public function updatedNewItemUnitPrice(float $value): void
    {
        $this->newItem['price'] = (float)$value;
    }
    
    public function getAvailableStockForMethod(): float
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
        
        if ($this->newItem['sale_method'] === 'piece') {
            return (float)max($stock->piece_count ?? 0, $stock->quantity ?? 0);
        } else {
            return (float)($stock->total_units ?? 0.0);
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