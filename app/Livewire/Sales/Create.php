<?php

namespace App\Livewire\Sales;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warehouse;
use App\Models\Stock;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Credit;
use App\Models\StockHistory;
use App\Models\CreditPayment;
use App\Facades\UserHelperFacade as UserHelper;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

#[Layout('layouts.app')]

class Create extends Component
{
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
        'warehouse_id' => '',
        'branch_id' => '',
        'sale_date' => '',
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'transaction_number' => '',
        'bank_account_id' => '',
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
    public $itemOptions = [];

    // Item being added
    public $newItem = [
        'item_id' => '',
        'quantity' => 1,
        'unit_price' => 0, // Price per unit (e.g., per kg)
        'price' => 0, // Calculated price per piece
        'notes' => '',
    ];

    // Search and selection
    public $customerSearch = '';
    public $itemSearch = '';
    public $selectedCustomer = null;
    public $selectedItem = null;
    public $availableStock = 0;
    
    // UI state
    public $editingItemIndex = null;
    public $showConfirmModal = false;
    public $showStockWarning = false;
    public $stockWarningType = null; // 'out_of_stock' or 'insufficient'
    public $stockWarningItem = null;
    public $requestedQuantity = 0;

    // Add a property to track the user's location type
    public $userLocationType = null;
    public $userLocationId = null;
    
    // Add property for location selection dropdown
    public $locationSelection = '';

    protected function rules()
    {
        $rules = [
            'form.sale_date' => 'required|date',
            'form.customer_id' => 'required|exists:customers,id',
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

        // Enforce that selected warehouse is accessible by the user
        $rules['form.warehouse_id'] = [
            $rules['form.warehouse_id'] ?? 'nullable',
            function ($attribute, $value, $fail) {
                if (!empty($value) && !\App\Facades\UserHelperFacade::hasAccessToWarehouse((int) $value)) {
                    $fail('You do not have permission to access this warehouse.');
                }
            }
        ];

        // Payment method specific validations
        if ($this->form['payment_method'] === PaymentMethod::TELEBIRR->value) {
            $rules['form.transaction_number'] = 'required|string|min:5';
        }

        if ($this->form['payment_method'] === PaymentMethod::BANK_TRANSFER->value) {
            $rules['form.bank_account_id'] = 'required|exists:bank_accounts,id';
        }

        if ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value) {
            $rules['form.advance_amount'] = 'required|numeric|min:0.01|lt:' . $this->totalAmount;
        }

        return $rules;
    }

    protected $messages = [
        'form.customer_id.required' => 'Please select a customer.',
        'form.branch_id.required_without' => 'Please select either a branch or warehouse.',
        'form.warehouse_id.required_without' => 'Please select either a branch or warehouse.',
        'items.required' => 'Please add at least one item to the sale.',
        'items.min' => 'Please add at least one item to the sale.',
        'form.transaction_number.required' => 'Transaction number is required for Telebirr payments.',
        'form.bank_account_id.required' => 'Please select a bank account for bank transfers.',
        'form.advance_amount.required' => 'Please enter an advance amount.',
        'form.advance_amount.lt' => 'Advance amount must be less than the total amount.',
    ];

    public function mount()
    {
        // Initialize items as an empty array if not already set
        $this->items = is_array($this->items) ? $this->items : [];
        
        $this->form = [
            'sale_date' => date('Y-m-d'),
            'reference_no' => $this->generateReferenceNumber(),
            'customer_id' => '',
            'warehouse_id' => '',
            'branch_id' => '',
            'payment_method' => PaymentMethod::defaultForSales()->value,
            'payment_status' => PaymentStatus::PAID->value,
            'tax' => 0,
            'shipping' => 0,
            'transaction_number' => '',
            'bank_account_id' => '',
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
            \Log::error('Failed to load bank accounts', ['error' => $e->getMessage()]);
            $this->bankAccounts = collect([]);
        }
        
        // Auto-set location based on user assignment
        $user = auth()->user();
        \Log::info('User location info', [
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'warehouse_id' => $user->warehouse_id
        ]);
        
        if ($user->branch_id) {
            $this->form['branch_id'] = $user->branch_id;
            $this->userLocationType = 'branch';
            $this->userLocationId = $user->branch_id;
            $this->loadItemsForLocation();
        } elseif ($user->warehouse_id) {
            $this->form['warehouse_id'] = $user->warehouse_id;
            $this->userLocationType = 'warehouse';
            $this->userLocationId = $user->warehouse_id;
            $this->loadItemsForLocation();
        } else {
            // If no specific assignment, auto-select first available warehouse
            $firstWarehouse = Warehouse::first();
            if ($firstWarehouse) {
                $this->form['warehouse_id'] = $firstWarehouse->id;
                $this->loadItemsForLocation();
                \Log::info('Auto-selected first warehouse', ['warehouse_id' => $firstWarehouse->id]);
            }
        }
    }

    private function loadCustomers()
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

    private function loadLocations()
    {
        // Only load if user is not assigned to a specific location
        if (!auth()->user()->branch_id && !auth()->user()->warehouse_id) {
            $this->branches = Branch::orderBy('name')->get();
            $this->warehouses = Warehouse::orderBy('name')->get();
        }
    }

    private function loadItemsForLocation()
    {
        // Clear existing items
        $this->itemOptions = [];
        
        // Get items already in cart
        $addedItemIds = [];
        if (is_array($this->items)) {
            $collection = collect($this->items);
            if (method_exists($collection, 'pluck')) {
                $addedItemIds = $collection->pluck('item_id')->toArray();
            }
        }
        
        if ($this->form['warehouse_id']) {
            \Log::info('Loading items for warehouse', ['warehouse_id' => $this->form['warehouse_id']]);
            
            // First check if there are any items at all
            $totalItems = Item::where('is_active', true)->count();
            $totalStocks = Stock::where('warehouse_id', $this->form['warehouse_id'])->count();
            $stocksWithQuantity = Stock::where('warehouse_id', $this->form['warehouse_id'])->where('quantity', '>', 0)->count();
            
            \Log::info('Database check', [
                'total_items' => $totalItems,
                'total_stocks_in_warehouse' => $totalStocks,
                'stocks_with_quantity' => $stocksWithQuantity
            ]);
            
            // Load all items that have stock records in the warehouse (including 0 stock)
            $items = Item::where('is_active', true)
                ->whereNotIn('id', $addedItemIds)
                ->whereHas('stocks', function ($query) {
                    $query->where('warehouse_id', $this->form['warehouse_id']);
                })
                ->with(['stocks' => function ($query) {
                    $query->where('warehouse_id', $this->form['warehouse_id']);
                }])
                ->orderBy('name')
                ->get();
                
            \Log::info('Items found', ['count' => $items->count()]);
            
            $this->itemOptions = $items->map(function ($item) {
                    $stock = $item->stocks->first();
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'sku' => $item->sku,
                        'selling_price' => $item->selling_price ?? 0,
                        'selling_price_per_unit' => $item->selling_price_per_unit ?? 0,
                        'unit_quantity' => $item->unit_quantity ?? 1,
                        'item_unit' => $item->item_unit ?? 'piece',
                        'quantity' => $stock ? $stock->quantity : 0,
                        'unit' => $item->unit ?? '',
                    ];
                })
                ->values();
                
            \Log::info('Final item options', [
                'count' => count($this->itemOptions),
                'items' => $this->itemOptions
            ]);
            
            // Also check if there are ANY items in the database
            $allItems = Item::where('is_active', true)->get(['id', 'name', 'sku']);
            \Log::info('All active items in database', [
                'count' => $allItems->count(),
                'items' => $allItems->toArray()
            ]);
                
        } elseif ($this->form['branch_id']) {
            // Load items available in warehouses serving this branch
            $warehouseIds = [];
            try {
                $branch = Branch::with('warehouses:id')->find($this->form['branch_id']);
                if ($branch && method_exists($branch, 'warehouses')) {
                    $warehouseIds = $branch->warehouses()->pluck('warehouses.id')->toArray();
                } else {
                    $warehouseIds = DB::table('branch_warehouse')
                        ->where('branch_id', $this->form['branch_id'])
                        ->pluck('warehouse_id')
                        ->toArray();
                }
            } catch (\Exception $e) {
                \Log::error('Failed to load warehouse IDs', ['error' => $e->getMessage()]);
                $warehouseIds = [];
            }
                
            if (!empty($warehouseIds)) {
                $this->itemOptions = Item::where('is_active', true)
                    ->whereNotIn('id', $addedItemIds)
                    ->whereHas('stocks', function ($query) use ($warehouseIds) {
                        $query->whereIn('warehouse_id', $warehouseIds);
                    })
                    ->with(['stocks' => function ($query) use ($warehouseIds) {
                        $query->whereIn('warehouse_id', $warehouseIds);
                    }])
                    ->orderBy('name')
                    ->get()
                    ->map(function ($item) {
                        $totalStock = $item->stocks->sum('quantity');
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'sku' => $item->sku,
                            'selling_price' => $item->selling_price ?? 0,
                            'selling_price_per_unit' => $item->selling_price_per_unit ?? 0,
                            'unit_quantity' => $item->unit_quantity ?? 1,
                            'item_unit' => $item->item_unit ?? 'piece',
                            'quantity' => $totalStock,
                            'unit' => $item->unit ?? '',
                        ];
                    })
                    ->values();
            }
        }
    }

    public function updatedCustomerSearch()
    {
        $this->loadCustomers();
    }

    public function selectCustomer($customerId)
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

    public function clearCustomer()
    {
        $this->form['customer_id'] = '';
        $this->selectedCustomer = null;
        $this->customerSearch = '';
        $this->loadCustomers();
    }

    public function updatedFormBranchId($value)
    {
        if ($value) {
            $this->form['warehouse_id'] = ''; // Clear warehouse when branch is selected
            $this->loadItemsForLocation();
        }
    }

    public function updatedFormWarehouseId($value)
    {
        if ($value) {
            $this->form['branch_id'] = ''; // Clear branch when warehouse is selected
            $this->loadItemsForLocation();
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
            $this->loadItemsForLocation();
        }
    }

    public function updatedItemSearch()
    {
        \Log::info('Item search updated', [
            'search' => $this->itemSearch,
            'warehouse_id' => $this->form['warehouse_id'],
            'branch_id' => $this->form['branch_id'],
            'itemOptions_count' => count($this->itemOptions)
        ]);
        
        // Force reload items if search is not empty and no items loaded
        if (!empty($this->itemSearch) && empty($this->itemOptions)) {
            $this->loadItemsForLocation();
        }
    }

    public function selectItem($itemId)
    {
        $item = Item::with(['stocks' => function ($query) {
            $query->where('warehouse_id', $this->form['warehouse_id']);
        }])->find($itemId);
        
        if ($item) {
            $stock = $item->stocks->first();
            $this->selectedItem = [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'unit_quantity' => $item->unit_quantity ?? 1,
                'item_unit' => $item->item_unit ?? 'piece',
            ];
            $this->newItem['item_id'] = $item->id;
            $this->newItem['unit_price'] = $item->selling_price_per_unit ?? 0;
            $this->newItem['price'] = ($item->selling_price_per_unit ?? 0) * ($item->unit_quantity ?? 1);
            $this->availableStock = $stock ? $stock->quantity : 0;
            $this->itemSearch = '';
            
            // Show warning if out of stock - don't set selected item fields yet
            if ($this->availableStock <= 0) {
                $this->stockWarningType = 'out_of_stock';
                $this->stockWarningItem = [
                    'name' => $item->name,
                    'price' => $this->newItem['price'],
                    'stock' => $this->availableStock
                ];
                // Clear selected item to hide quantity fields
                $this->selectedItem = null;
                return;
            }
        }
    }

    public function clearSelectedItem()
    {
        $this->newItem = [
            'item_id' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'price' => 0,
            'notes' => '',
        ];
        $this->selectedItem = null;
        $this->availableStock = 0;
        $this->itemSearch = '';
    }

    public function addItem()
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
        
        $requestedQty = floatval($this->newItem['quantity']);
        
        // Check stock levels and show warnings if needed
        \Log::info('Checking stock levels', [
            'available_stock' => $this->availableStock,
            'requested_qty' => $requestedQty
        ]);
        
        // Check for insufficient stock and show warning
        if ($requestedQty > $this->availableStock) {
            $this->stockWarningType = 'insufficient';
            $item = Item::find($this->newItem['item_id']);
            $this->stockWarningItem = [
                'name' => $item->name,
                'available' => $this->availableStock,
                'requested' => $requestedQty,
                'deficit' => $requestedQty - $this->availableStock
            ];
            return;
        }
        
        \Log::info('No stock warnings needed, proceeding with add item');
        
        $this->processAddItem();
    }
    
    private function showOutOfStockWarning()
    {
        $item = Item::find($this->newItem['item_id']);
        $this->stockWarningType = 'out_of_stock';
        $this->stockWarningItem = [
            'name' => $item->name,
            'price' => $this->newItem['price'],
            'stock' => $this->availableStock
        ];
        $this->showStockWarning = true;
        
        \Log::info('Showing out of stock warning', [
            'item' => $this->stockWarningItem,
            'available_stock' => $this->availableStock
        ]);
        
        $this->dispatch('showStockWarningModal');
    }
    
    private function showInsufficientStockWarning($requestedQty)
    {
        $item = Item::find($this->newItem['item_id']);
        $this->stockWarningType = 'insufficient';
        $this->stockWarningItem = [
            'name' => $item->name,
            'available' => $this->availableStock,
            'requested' => $requestedQty,
            'deficit' => $requestedQty - $this->availableStock
        ];
        $this->requestedQuantity = $requestedQty;
        $this->showStockWarning = true;
        $this->dispatch('showStockWarningModal');
    }
    
    public function proceedWithWarning()
    {
        // Restore selected item to show quantity fields
        if (!empty($this->newItem['item_id'])) {
            $item = Item::find($this->newItem['item_id']);
            if ($item) {
                $this->selectedItem = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'unit_quantity' => $item->unit_quantity ?? 1,
                    'item_unit' => $item->item_unit ?? 'piece',
                ];
            }
        }
        
        $this->stockWarningType = null;
        $this->stockWarningItem = null;
    }
    
    public function cancelStockWarning()
    {
        $this->stockWarningType = null;
        $this->stockWarningItem = null;
        $this->requestedQuantity = 0;
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
        
        // Check if item already exists in cart
        $existingIndex = collect($this->items)->search(function ($cartItem) {
            return $cartItem['item_id'] == $this->newItem['item_id'];
        });

        if ($existingIndex !== false && $this->editingItemIndex === null) {
            $this->notify('Item already in cart. Use edit to modify.', 'warning');
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
        $this->loadItemsForLocation();
    }
    
    public function editItem($index)
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
                // For branch sales, calculate total available
        $warehouseIds = [];
        try {
            $warehouseIds = DB::table('branch_warehouse')
                ->where('branch_id', $this->form['branch_id'])
                ->pluck('warehouse_id')
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('Failed to load warehouse IDs in editItem', [
                'error' => $e->getMessage(),
                'branch_id' => $this->form['branch_id'] ?? null
            ]);
        }
        
        $totalStock = 0;
        if (!empty($warehouseIds)) {
            try {
                $totalStock = Stock::where('item_id', $item['item_id'])
                    ->whereIn('warehouse_id', $warehouseIds)
                    ->sum('quantity');
            } catch (\Exception $e) {
                \Log::error('Failed to calculate total stock', [
                    'error' => $e->getMessage(),
                    'item_id' => $item['item_id'] ?? null,
                    'warehouse_ids' => $warehouseIds
                ]);
            }
        }
        
        $this->availableStock = $totalStock + $item['quantity'];
            }
        }
    }
    
    public function cancelEdit()
    {
        $this->editingItemIndex = null;
        $this->clearSelectedItem();
    }

    public function removeItem($index)
    {
        if (!isset($this->items[$index])) {
            return;
        }

        $removedItem = $this->items[$index];
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        
        $this->updateTotals();
        $this->loadItemsForLocation();
        
        $this->notify($removedItem['name'] . ' removed from cart', 'success');
    }

    public function clearCart()
    {
        $this->items = [];
        $this->updateTotals();
        $this->loadItemsForLocation();
        $this->notify('Cart cleared', 'success');
    }

    public function loadItemOptions()
    {
        $this->loadItemsForLocation();
        $this->notify('Items refreshed successfully', 'success');
    }

    private function updateTotals()
    {
        // Calculate subtotal safely
        $this->subtotal = 0;
        if (is_array($this->items)) {
            try {
                $collection = collect($this->items);
                if (method_exists($collection, 'sum')) {
                    $this->subtotal = $collection->sum('subtotal');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to calculate subtotal', [
                    'error' => $e->getMessage(),
                    'items' => is_array($this->items) ? count($this->items) : 'not_array'
                ]);
            }
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

    public function updatedFormTax()
    {
        $this->updateTotals();
    }

    public function updatedFormShipping()
    {
        $this->updateTotals();
    }

    public function updatedFormPaymentMethod($value)
    {
        // Reset payment-specific fields
        $this->form['transaction_number'] = '';
        $this->form['bank_account_id'] = '';
        $this->form['receipt_url'] = '';
        $this->form['advance_amount'] = 0;

        $this->updatePaymentStatus();
        $this->updateTotals();
    }

    private function updatePaymentStatus()
    {
        switch ($this->form['payment_method']) {
            case PaymentMethod::CASH->value:
            case PaymentMethod::BANK_TRANSFER->value:
            case PaymentMethod::TELEBIRR->value:
                $this->form['payment_status'] = PaymentStatus::PAID->value;
                break;
            case PaymentMethod::CREDIT_ADVANCE->value:
                $this->form['payment_status'] = PaymentStatus::PARTIAL->value;
                if ($this->totalAmount > 0 && (empty($this->form['advance_amount']) || $this->form['advance_amount'] == 0)) {
                    $this->form['advance_amount'] = round($this->totalAmount * 0.2, 2); // Default 20%
                }
                break;
            case PaymentMethod::FULL_CREDIT->value:
                $this->form['payment_status'] = PaymentStatus::DUE->value;
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

            // Validate location (either branch OR warehouse)
            if (!auth()->user()->branch_id && !auth()->user()->warehouse_id) {
                if (empty($this->form['branch_id']) && empty($this->form['warehouse_id'])) {
                    throw new \Exception('Please select either a branch or warehouse.');
                }
                if (!empty($this->form['branch_id']) && !empty($this->form['warehouse_id'])) {
                    throw new \Exception('Please select either a branch or warehouse, not both.');
                }
            }

            DB::beginTransaction();

            // Create sale
            $sale = new Sale();
            $sale->reference_no = $this->form['reference_no'];
            $sale->customer_id = $this->form['customer_id'];
            $sale->branch_id = $this->form['branch_id'] ?: null;
            $sale->warehouse_id = $this->form['warehouse_id'] ?: null;
            $sale->user_id = auth()->id();
            $sale->sale_date = $this->form['sale_date'];
            $sale->payment_method = $this->form['payment_method'];
            $sale->payment_status = $this->form['payment_status'];
            $sale->status = 'pending'; // Start as pending, will be completed by processSale()
            $sale->tax = $this->taxAmount;
            $sale->shipping = $this->shippingAmount;
            $sale->discount = 0;
            $sale->total_amount = $this->totalAmount;
            $sale->notes = $this->form['notes'];

            // Set payment-specific fields
            switch ($this->form['payment_method']) {
                case PaymentMethod::CASH->value:
                case PaymentMethod::BANK_TRANSFER->value:
                case PaymentMethod::TELEBIRR->value:
                    $sale->paid_amount = $this->totalAmount;
                    $sale->due_amount = 0;
                    break;
                case PaymentMethod::CREDIT_ADVANCE->value:
                    $sale->paid_amount = $this->form['advance_amount'];
                    $sale->advance_amount = $this->form['advance_amount'];
                    $sale->due_amount = $this->totalAmount - $this->form['advance_amount'];
                    break;
                case PaymentMethod::FULL_CREDIT->value:
                    $sale->paid_amount = 0;
                    $sale->due_amount = $this->totalAmount;
                    break;
            }

            if ($this->form['payment_method'] === PaymentMethod::TELEBIRR->value) {
                $sale->transaction_number = $this->form['transaction_number'];
                $sale->receipt_url = $this->form['receipt_url'];
            }

            if ($this->form['payment_method'] === PaymentMethod::BANK_TRANSFER->value) {
                $sale->bank_account_id = $this->form['bank_account_id'];
            }

            $sale->save();

            // Add sale items and update stock
            foreach ($this->items as $item) {
                // Validate item data before saving
                if (empty($item['item_id'])) {
                    throw new \Exception('Invalid item data: missing item ID');
                }
                
                if (empty($item['quantity']) || $item['quantity'] <= 0) {
                    throw new \Exception('Invalid quantity for item. Quantity must be greater than zero.');
                }
                
                if (empty($item['price']) || $item['price'] <= 0) {
                    throw new \Exception('Invalid price for item. Price must be greater than zero.');
                }
                
                // Create sale item
                $saleItem = new SaleItem();
                $saleItem->sale_id = $sale->id;
                $saleItem->item_id = $item['item_id'];
                $saleItem->quantity = $item['quantity'];
                $saleItem->unit_price = $item['price'];
                $saleItem->subtotal = $item['subtotal'];
                $saleItem->notes = $item['notes'] ?? null;
                $saleItem->save();
            }

            // Process the sale (update stock)
            $processResult = $sale->processSale();
            \Log::info('Sale process result', [
                'result' => $processResult, 
                'sale_id' => $sale->id,
                'total_amount' => $this->totalAmount,
                'item_count' => count($this->items),
                'payment_method' => $this->form['payment_method']
            ]);

            // Handle credit creation for credit-based payment methods
            if ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value && $this->form['advance_amount'] > 0) {
                $this->createCreditAndAdvancePayment($sale);
            } elseif ($this->form['payment_method'] === PaymentMethod::FULL_CREDIT->value) {
                $this->createFullCredit($sale);
            }

            DB::commit();
            
            // Show success message with credit information if applicable
            $successMessage = 'Sale created successfully!';
            if ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value && $this->form['advance_amount'] > 0) {
                $successMessage .= ' Credit record created for remaining amount of ' . number_format($this->totalAmount - $this->form['advance_amount'], 2) . ' ETB.';
            } elseif ($this->form['payment_method'] === PaymentMethod::FULL_CREDIT->value) {
                $successMessage .= ' Credit record created for full amount of ' . number_format($this->totalAmount, 2) . ' ETB.';
            }
            
            session()->flash('success', $successMessage);
            
            // Redirect to sales list
            return redirect()->route('admin.sales.index');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->notify('Please fix the validation errors.', 'error');
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Sale creation database error', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Provide specific database error messages
            $errorMessage = $this->getDatabaseErrorMessage($e);
            $this->notify($errorMessage, 'error');
            return false;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'form_data' => $this->form,
                'items' => $this->items
            ]);
            
            // Provide descriptive error message
            $errorMessage = $this->getDescriptiveErrorMessage($e);
            $this->notify($errorMessage, 'error');
            return false; // Return false to indicate failure
        }
    }

    public function cancel()
    {
        return redirect()->route('admin.sales.index');
    }

    private function generateReferenceNumber()
    {
        $prefix = 'SALE-';
        $date = date('Ymd');
        $count = Sale::whereDate('created_at', today())->count() + 1;
        return $prefix . $date . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    private function notify($message, $type = 'info')
    {
        $this->dispatch('notify', [
            'type' => $type,
            'message' => $message
        ]);
    }

    /**
     * Create credit record and advance payment for credit_advance sales
     */
    private function createCreditAndAdvancePayment(Sale $sale)
    {
        try {
            // Calculate remaining amount
            $remainingAmount = $this->totalAmount - $this->form['advance_amount'];
            
            // Create credit record for the remaining amount
            $credit = new Credit();
            $credit->customer_id = $sale->customer_id;
            $credit->amount = $remainingAmount;
            $credit->paid_amount = 0; // No payments yet on the credit
            $credit->balance = $remainingAmount;
            $credit->reference_no = $sale->reference_no . '-CREDIT';
            $credit->reference_type = 'sale';
            $credit->reference_id = $sale->id;
            $credit->credit_type = 'receivable';
            $credit->description = "Credit for sale {$sale->reference_no} - Advance payment of " . number_format($this->form['advance_amount'], 2) . " ETB made";
            $credit->credit_date = $sale->sale_date;
            $credit->due_date = null; // Can be set based on business rules
            $credit->status = 'active';
            $credit->user_id = auth()->id();
            $credit->branch_id = $sale->branch_id;
            $credit->warehouse_id = $sale->warehouse_id;
            $credit->created_by = auth()->id();
            $credit->save();

            // Create advance payment record
            $advancePayment = new CreditPayment();
            $advancePayment->credit_id = $credit->id;
            $advancePayment->amount = $this->form['advance_amount'];
            $advancePayment->payment_method = $this->form['payment_method'];
            $advancePayment->reference_no = $sale->reference_no . '-ADVANCE';
            $advancePayment->payment_date = $sale->sale_date;
            $advancePayment->notes = "Advance payment for sale {$sale->reference_no}";
            $advancePayment->user_id = auth()->id();
            $advancePayment->save();

            // Update credit with the advance payment
            $credit->addPayment($this->form['advance_amount'], $this->form['payment_method'], $sale->reference_no . '-ADVANCE', "Advance payment for sale {$sale->reference_no}");

            \Log::info('Credit and advance payment created successfully', [
                'sale_id' => $sale->id,
                'credit_id' => $credit->id,
                'advance_payment_id' => $advancePayment->id,
                'total_amount' => $this->totalAmount,
                'advance_amount' => $this->form['advance_amount'],
                'remaining_amount' => $remainingAmount
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to create credit and advance payment', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to create credit record and advance payment: ' . $e->getMessage());
        }
    }

    /**
     * Create full credit record for full_credit sales
     */
    private function createFullCredit(Sale $sale)
    {
        try {
            // Create credit record for the full amount
            $credit = new Credit();
            $credit->customer_id = $sale->customer_id;
            $credit->amount = $this->totalAmount;
            $credit->paid_amount = 0;
            $credit->balance = $this->totalAmount;
            $credit->reference_no = $sale->reference_no . '-CREDIT';
            $credit->reference_type = 'sale';
            $credit->reference_id = $sale->id;
            $credit->credit_type = 'receivable';
            $credit->description = "Full credit for sale {$sale->reference_no}";
            $credit->credit_date = $sale->sale_date;
            $credit->due_date = null; // Can be set based on business rules
            $credit->status = 'active';
            $credit->user_id = auth()->id();
            $credit->branch_id = $sale->branch_id;
            $credit->warehouse_id = $sale->warehouse_id;
            $credit->created_by = auth()->id();
            $credit->save();

            \Log::info('Full credit created successfully', [
                'sale_id' => $sale->id,
                'credit_id' => $credit->id,
                'total_amount' => $this->totalAmount
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to create full credit', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to create credit record: ' . $e->getMessage());
        }
    }

    /**
     * Get descriptive error message based on exception type
     */
    private function getDescriptiveErrorMessage(\Exception $e): string
    {
        $errorMessage = $e->getMessage();
        
        // Database constraint violations
        if (str_contains($errorMessage, 'Column') && str_contains($errorMessage, 'cannot be null')) {
            $column = $this->extractColumnName($errorMessage);
            return "Missing required information: {$column}. Please fill in all required fields.";
        }
        
        if (str_contains($errorMessage, 'foreign key constraint fails')) {
            return "Invalid reference data. Please check customer, warehouse, or item selections.";
        }
        
        if (str_contains($errorMessage, 'Duplicate entry')) {
            return "This sale reference number already exists. Please try again.";
        }
        
        // Stock-related errors
        if (str_contains($errorMessage, 'insufficient stock') || str_contains($errorMessage, 'stock')) {
            return "Insufficient stock for one or more items. Please check available quantities.";
        }
        
        // Validation errors
        if (str_contains($errorMessage, 'validation') || str_contains($errorMessage, 'required')) {
            return "Please check all required fields and ensure data is valid.";
        }
        
        // Network or connection errors
        if (str_contains($errorMessage, 'connection') || str_contains($errorMessage, 'timeout')) {
            return "Network connection issue. Please check your internet connection and try again.";
        }
        
        // Permission errors
        if (str_contains($errorMessage, 'permission') || str_contains($errorMessage, 'unauthorized')) {
            return "You don't have permission to create sales. Please contact your administrator.";
        }
        
        // Default descriptive message
        return "Sale creation failed: " . $errorMessage . ". Please try again or contact support if the problem persists.";
    }

    /**
     * Extract column name from database error message
     */
    private function extractColumnName(string $errorMessage): string
    {
        if (preg_match("/Column '([^']+)' cannot be null/", $errorMessage, $matches)) {
            $column = $matches[1];
            
            // Map database columns to user-friendly names
            $columnMap = [
                'customer_id' => 'Customer',
                'warehouse_id' => 'Warehouse',
                'branch_id' => 'Branch',
                'item_id' => 'Item',
                'quantity' => 'Quantity',
                'unit_price' => 'Unit Price',
                'payment_method' => 'Payment Method',
                'sale_date' => 'Sale Date',
                'reference_no' => 'Reference Number'
            ];
            
            return $columnMap[$column] ?? ucfirst(str_replace('_', ' ', $column));
        }
        
        return 'Required field';
    }

    /**
     * Get specific database error messages
     */
    private function getDatabaseErrorMessage(\Illuminate\Database\QueryException $e): string
    {
        $errorMessage = $e->getMessage();
        $errorCode = $e->getCode();
        
        // MySQL error codes
        switch ($errorCode) {
            case 1048: // Column cannot be null
                $column = $this->extractColumnName($errorMessage);
                return "Missing required information: {$column}. Please fill in all required fields.";
                
            case 1062: // Duplicate entry
                return "This sale reference number already exists. Please try again.";
                
            case 1452: // Foreign key constraint fails
                return "Invalid reference data. Please check customer, warehouse, or item selections.";
                
            case 1264: // Out of range value
                return "Invalid numeric value. Please check quantities and prices.";
                
            case 1366: // Incorrect integer value
                return "Invalid data format. Please check all input fields.";
                
            case 1054: // Unknown column
                return "System configuration error. Please contact support.";
                
            default:
                // Check error message content for common patterns
                if (str_contains($errorMessage, 'Column') && str_contains($errorMessage, 'cannot be null')) {
                    $column = $this->extractColumnName($errorMessage);
                    return "Missing required information: {$column}. Please fill in all required fields.";
                }
                
                if (str_contains($errorMessage, 'foreign key constraint fails')) {
                    return "Invalid reference data. Please check customer, warehouse, or item selections.";
                }
                
                if (str_contains($errorMessage, 'Duplicate entry')) {
                    return "This sale reference number already exists. Please try again.";
                }
                
                return "Database error occurred. Please try again or contact support if the problem persists.";
        }
    }

    // Computed properties
    public function getFilteredCustomersProperty()
    {
        return $this->customers;
    }

    public function getFilteredItemOptionsProperty()
    {
        if (empty($this->itemSearch) || strlen($this->itemSearch) < 2) {
            return [];
        }
        
        $warehouseId = $this->form['warehouse_id'];
        if (empty($warehouseId)) {
            \Log::info('No warehouse selected for search');
            return [];
        }
        
        $searchTerm = strtolower($this->itemSearch);
        
        // Debug: Check if there are any items at all
        $totalItems = Item::where('is_active', true)->count();
        $itemsWithSearch = Item::where('is_active', true)
            ->where(function ($query) use ($searchTerm) {
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"])
                      ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$searchTerm}%"]);
            })->count();
        $stocksInWarehouse = Stock::where('warehouse_id', $warehouseId)->count();
        
        \Log::info('Search debug', [
            'search_term' => $searchTerm,
            'warehouse_id' => $warehouseId,
            'total_items' => $totalItems,
            'items_matching_search' => $itemsWithSearch,
            'stocks_in_warehouse' => $stocksInWarehouse
        ]);
        
        $results = Item::where('is_active', true)
            ->where(function ($query) use ($searchTerm) {
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"])
                      ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$searchTerm}%"]);
            })
            ->with(['stocks' => function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }])
            ->limit(8)
            ->get()
            ->map(function ($item) {
                $stock = $item->stocks->first();
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'quantity' => $stock ? $stock->quantity : 0,
                    'selling_price_per_unit' => $item->selling_price_per_unit ?? 0,
                    'unit_quantity' => $item->unit_quantity ?? 1,
                    'item_unit' => $item->item_unit ?? 'piece',
                ];
            })
            ->toArray();
            
        \Log::info('Search results', ['count' => count($results), 'results' => $results]);
        
        return $results;
    }

    public function isFormReady()
    {
        return !empty($this->form['customer_id']) && 
               !empty($this->items) &&
               ($this->form['branch_id'] || $this->form['warehouse_id']) &&
               $this->isPaymentMethodValid();
    }

    private function isPaymentMethodValid()
    {
        switch ($this->form['payment_method']) {
            case 'telebirr':
                return !empty($this->form['transaction_number']);
            case 'bank_transfer':
                return !empty($this->form['bank_account_id']);
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
    public function validateAndShowModal()
    {
        // Clear any previous validation errors
        $this->resetErrorBag();
        
        // Basic validation first - collect errors instead of throwing exceptions
        $validationErrors = [];
        
        if (empty($this->items) || count($this->items) === 0) {
            $validationErrors['items'] = 'No items found for this sale. Please add at least one item.';
        }

        if (empty($this->form['customer_id'])) {
            $validationErrors['form.customer_id'] = 'Please select a customer.';
        }

        if (empty($this->form['warehouse_id']) && empty($this->form['branch_id'])) {
            $validationErrors['form.warehouse_id'] = 'Please select either a warehouse or branch.';
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

        if (empty($this->form['customer_id'])) {
            $validationErrors['form.customer_id'] = 'Cannot create sale: Please select a customer';
        }

        if (empty($this->form['warehouse_id']) && empty($this->form['branch_id'])) {
            $validationErrors['form.warehouse_id'] = 'Cannot create sale: Please select either a warehouse or branch';
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
            
            // Log the result for debugging
            \Log::info('Sale save result', ['result' => $result]);
            
            // Check if save returned false (indicating failure)
            if ($result === false) {
                // Close the modal to show errors on the form
                $this->dispatch('closeSaleModal');
                
                // Show a more descriptive error message
                if ($this->getErrorBag()->isEmpty()) {
                    $this->addError('general', 'Sale creation failed. Please check your input and try again. If the problem persists, contact support.');
                    $this->notify('❌ Sale creation failed. Please review the form and try again.', 'error');
                }
                
                return false;
            }
            
            // If we get here, save was successful (save() returns redirect on success)
            // Close the modal
            $this->dispatch('closeSaleModal');
            $this->dispatch('saleCompleted');
            
            // Show success message with more details
            $totalAmount = number_format($this->totalAmount, 2);
            $itemCount = count($this->items);
            $this->notify("✅ Sale created successfully! {$itemCount} items, ETB {$totalAmount}", 'success');
            
            // Return the redirect from save method
            return $result;
            
        } catch (\Exception $e) {
            // Close the modal to show errors on the form
            $this->dispatch('closeSaleModal');
            
            // Log the specific error for debugging
            \Log::error('Sale creation exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'form_data' => $this->form,
                'items' => $this->items
            ]);
            
            // Show a more descriptive error message based on the exception type
            $errorMessage = $this->getDescriptiveErrorMessage($e);
            $this->addError('general', $errorMessage);
            $this->notify('❌ ' . $errorMessage, 'error');
            
            return false;
        }
    }

    /**
     * Handle unit price changes for auto-calculation
     */
    public function updatedNewItemUnitPrice($value)
    {
        // Calculate piece price based on unit price and item's unit quantity
        if ($this->selectedItem && isset($this->selectedItem['unit_quantity'])) {
            $this->newItem['price'] = (float)$value * (int)($this->selectedItem['unit_quantity'] ?? 1);
        }
        
        // Update totals when price changes
        $this->updateTotals();
    }

    public function render()
    {
        return view('livewire.sales.create');
    }
}