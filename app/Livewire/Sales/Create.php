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
use App\Traits\HasFlashMessages;
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
    use HasFlashMessages;
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
        'sale_method' => 'piece', // 'piece' or 'unit' - mutually exclusive
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
        if ($this->form['payment_method'] === 'telebirr') {
            $rules['form.transaction_number'] = 'required|string|min:5';
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
            'payment_method' => 'cash',
            'payment_status' => 'paid',
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
            $this->bankAccounts = collect([]);
        }
        
        // Auto-set location based on user assignment
        $user = auth()->user();
        
        if ($user->warehouse_id) {
            // Warehouse user - auto-select their warehouse
            $this->form['warehouse_id'] = $user->warehouse_id;
            $this->userLocationType = 'warehouse';
            $this->userLocationId = $user->warehouse_id;
            $this->loadItemsForLocation();
        } elseif ($user->isBranchManager() && $user->branch_id) {
            // Branch manager - auto-select first warehouse in their branch
            $branchWarehouse = Warehouse::whereHas('branches', function($q) use ($user) {
                $q->where('branches.id', $user->branch_id);
            })->first();
            
            if ($branchWarehouse) {
                $this->form['warehouse_id'] = $branchWarehouse->id;
                $this->loadItemsForLocation();
            }
        } elseif ($user->branch_id) {
            // Other branch users - set branch
            $this->form['branch_id'] = $user->branch_id;
            $this->userLocationType = 'branch';
            $this->userLocationId = $user->branch_id;
            $this->loadItemsForLocation();
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
                $this->loadItemsForLocation();
            } else {
                // Fallback to first warehouse
                $firstWarehouse = Warehouse::first();
                if ($firstWarehouse) {
                    $this->form['warehouse_id'] = $firstWarehouse->id;
                    $this->loadItemsForLocation();
                }
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
            // Load only items that have stock in this warehouse
            $items = Item::forUser(auth()->user())
                ->where('is_active', true)
                ->whereNotIn('id', $addedItemIds)
                ->whereHas('stocks', function($q) {
                    $q->where('warehouse_id', $this->form['warehouse_id']);
                })
                ->orderBy('name')
                ->get();

            
            $this->itemOptions = $items->map(function ($item) {
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
                
                // Calculate actual stock value
                $stockValue = max(
                    $stock->piece_count ?? 0,
                    $stock->quantity ?? 0
                );
                
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
                
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'selling_price' => $item->selling_price ?? 0,
                    'selling_price_per_unit' => $item->selling_price_per_unit ?? 0,
                    'unit_quantity' => $item->unit_quantity ?? 1,
                    'item_unit' => $item->item_unit ?? 'piece',
                    'quantity' => $stockValue,
                    'unit' => $item->unit ?? '',
                ];
            })
            ->values()
            ->toArray();
                
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
                $warehouseIds = [];
            }
                
            if (!empty($warehouseIds)) {
                // Load only items that have stock in branch warehouses
                $items = Item::forUser(auth()->user())
                    ->where('is_active', true)
                    ->whereNotIn('id', $addedItemIds)
                    ->whereHas('stocks', function($q) use ($warehouseIds) {
                        $q->whereIn('warehouse_id', $warehouseIds);
                    })
                    ->orderBy('name')
                    ->get();
                    
                $this->itemOptions = $items->map(function ($item) use ($warehouseIds) {
                    // Calculate total stock across all branch warehouses
                    $totalStock = 0;
                    foreach ($warehouseIds as $warehouseId) {
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
                        
                        $totalStock += $stockValue;
                    }
                    
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
                ->values()
                ->toArray();
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
        // Force reload items if search is not empty and no items loaded
        if (!empty($this->itemSearch) && empty($this->itemOptions)) {
            $this->loadItemsForLocation();
        }
    }

    public function selectItem($itemId)
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

    public function clearSelectedItem()
    {
        $this->newItem = [
            'item_id' => '',
            'quantity' => 1,
            'sale_method' => 'piece', // Default to piece method
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
        
        // Validate selling price is not below cost price
        $item = Item::find($this->newItem['item_id']);
        $saleMethod = $this->newItem['sale_method'] ?? 'piece';
        $sellingPrice = (float)$this->newItem['price'];
        
        if ($saleMethod === 'piece') {
            $costPrice = $item->cost_price ?? 0;
            if ($sellingPrice < $costPrice) {
                $this->addError('newItem.price', 'Selling price (' . number_format($sellingPrice, 2) . ') cannot be below cost price (' . number_format($costPrice, 2) . ')');
                return;
            }
        } else {
            $costPricePerUnit = $item->cost_price_per_unit ?? 0;
            if ($sellingPrice < $costPricePerUnit) {
                $this->addError('newItem.price', 'Unit selling price (' . number_format($sellingPrice, 2) . ') cannot be below cost price per unit (' . number_format($costPricePerUnit, 2) . ')');
                return;
            }
        }
        
        $requestedQty = floatval($this->newItem['quantity']);
        
        // Business Logic: Show warnings for awareness but allow negative stock
        if ($this->availableStock <= 0) {
            // Critical: Out of Stock - Show warning modal but allow override
            $this->stockWarningType = 'out_of_stock';
            $item = Item::find($this->newItem['item_id']);
            $this->stockWarningItem = [
                'name' => $item->name,
                'price' => $this->newItem['price'],
                'stock' => $this->availableStock
            ];
            return; // Show modal for user decision
        }
        
        if ($requestedQty > $this->availableStock) {
            // Warning: Insufficient Stock - Show informational modal but allow override
            $this->stockWarningType = 'insufficient';
            $item = Item::find($this->newItem['item_id']);
            $this->stockWarningItem = [
                'name' => $item->name,
                'available' => $this->availableStock,
                'requested' => $requestedQty,
                'deficit' => $requestedQty - $this->availableStock,
                'price' => $this->newItem['price']
            ];
            return; // Show modal for user decision
        }
        
        // Normal stock level - add item without warning
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
                // For branch sales, calculate total available
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

            // Create sale - set either branch_id OR warehouse_id, not both
            $branchId = $this->resolveBranchId();
            $sale = new Sale();
            $sale->reference_no = $this->form['reference_no'];
            $sale->customer_id = $this->form['customer_id'];
            
            // Set location: prefer warehouse_id if explicitly selected, otherwise use branch_id
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
            $sale->status = 'pending'; // Start as pending, will be completed by processSale()
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
                    break;
                case 'credit_advance':
                    $sale->paid_amount = $this->form['advance_amount'];
                    $sale->advance_amount = $this->form['advance_amount'];
                    $sale->due_amount = $this->totalAmount - $this->form['advance_amount'];
                    break;
                case 'credit_full':
                    $sale->paid_amount = 0;
                    $sale->due_amount = $this->totalAmount;
                    break;
            }

            if ($this->form['payment_method'] === 'telebirr') {
                $sale->transaction_number = $this->form['transaction_number'];
                $sale->receipt_url = $this->form['receipt_url'];
            }

            if ($this->form['payment_method'] === 'bank_transfer') {
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
                $saleItem->sale_method = $item['sale_method'] ?? 'piece';
                $saleItem->unit_price = $item['price'];
                $saleItem->subtotal = $item['subtotal'];
                $saleItem->notes = $item['notes'] ?? null;
                $saleItem->save();
            }

            // Process the sale (update stock)
            $sale->processSale();

            // Credit creation is handled by Sale model's processSale() method

            DB::commit();
            
            // Show success message with credit information if applicable
            $successMessage = 'Sale created successfully!';
            if ($this->form['payment_method'] === PaymentMethod::CREDIT_ADVANCE->value && $this->form['advance_amount'] > 0) {
                $successMessage .= ' Credit record created for remaining amount of ' . number_format($this->totalAmount - $this->form['advance_amount'], 2) . ' ETB.';
            } elseif ($this->form['payment_method'] === PaymentMethod::FULL_CREDIT->value) {
                $successMessage .= ' Credit record created for full amount of ' . number_format($this->totalAmount, 2) . ' ETB.';
            }
            
            $this->flashSuccess('Sale completed successfully.');
            
            // Redirect to sales list
            return redirect()->route('admin.sales.index');

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

    private function generateReferenceNumber()
    {
        $prefix = 'SALE-';
        $date = date('Ymd');
        $count = Sale::whereDate('created_at', today())->count() + 1;
        return $prefix . $date . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    private function notify($message, $type = 'info')
    {
        match($type) {
            'success' => $this->flashSuccess($message),
            default => $this->dispatch('notify', type: $type, message: $message)
        };
    }

    /**
     * Create credit record and advance payment for credit_advance sales
     */
    private function createCreditAndAdvancePayment(Sale $sale, ?int $branchId = null)
    {
        try {
            // Calculate remaining amount
            $remainingAmount = $this->totalAmount - $this->form['advance_amount'];
            
            // Resolve branch_id for credit (required for credits)
            $creditBranchId = $sale->branch_id ?: $branchId ?: $this->resolveBranchId();
            
            // Create credit record for the remaining amount
            $credit = new Credit();
            $credit->customer_id = $sale->customer_id;
            $credit->amount = $remainingAmount;
            $credit->paid_amount = 0; // No payments yet on the credit
            $credit->balance = $remainingAmount;
            $credit->reference_no = $sale->reference_no;
            $credit->reference_type = 'sale';
            $credit->reference_id = $sale->id;
            $credit->credit_type = 'receivable';
            $credit->description = "Credit for sale #{$sale->reference_no}";
            $credit->credit_date = $sale->sale_date;
            $credit->due_date = now()->addDays(30);
            $credit->status = 'active';
            $credit->user_id = auth()->id();
            $credit->branch_id = $creditBranchId;
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



        } catch (\Exception $e) {
            throw new \Exception('Failed to create credit record and advance payment: ' . $e->getMessage());
        }
    }

    /**
     * Create full credit record for full_credit sales
     */
    private function createFullCredit(Sale $sale, ?int $branchId = null)
    {
        try {
            // Resolve branch_id for credit (required for credits)
            $creditBranchId = $sale->branch_id ?: $branchId ?: $this->resolveBranchId();
            
            // Create credit record for the full amount
            $credit = new Credit();
            $credit->customer_id = $sale->customer_id;
            $credit->amount = $this->totalAmount;
            $credit->paid_amount = 0;
            $credit->balance = $this->totalAmount;
            $credit->reference_no = $sale->reference_no;
            $credit->reference_type = 'sale';
            $credit->reference_id = $sale->id;
            $credit->credit_type = 'receivable';
            $credit->description = "Credit for sale #{$sale->reference_no}";
            $credit->credit_date = $sale->sale_date;
            $credit->due_date = now()->addDays(30);
            $credit->status = 'active';
            $credit->user_id = auth()->id();
            $credit->branch_id = $creditBranchId;
            $credit->warehouse_id = $sale->warehouse_id;
            $credit->created_by = auth()->id();
            $credit->save();



        } catch (\Exception $e) {
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
        if (empty($this->itemSearch) || strlen(trim($this->itemSearch)) < 1) {
            return [];
        }
        
        $warehouseId = $this->form['warehouse_id'];
        if (empty($warehouseId)) {
            return [];
        }
        
        $searchTerm = trim($this->itemSearch);
        
        // Get items already in cart to exclude them
        $addedItemIds = [];
        if (is_array($this->items)) {
            $collection = collect($this->items);
            if (method_exists($collection, 'pluck')) {
                $addedItemIds = $collection->pluck('item_id')->toArray();
            }
        }
        
        $results = Item::forUser(auth()->user())
            ->where('is_active', true)
            ->whereNotIn('id', $addedItemIds)
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('sku', 'LIKE', "%{$searchTerm}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($item) use ($warehouseId) {
                // Get or create stock record
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
                
                // Calculate actual stock value
                $stockValue = max(
                    $stock->piece_count ?? 0,
                    $stock->quantity ?? 0
                );
                
                // If stock is 0, check purchase history and sync
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
                    'selling_price_per_unit' => $item->selling_price_per_unit ?? 0,
                    'unit_quantity' => $item->unit_quantity ?? 1,
                    'item_unit' => $item->item_unit ?? 'piece',
                ];
            })
            ->toArray();
            
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
            

            
            // Show a more descriptive error message based on the exception type
            $errorMessage = $this->getDescriptiveErrorMessage($e);
            $this->addError('general', $errorMessage);
            $this->notify('❌ ' . $errorMessage, 'error');
            
            return false;
        }
    }

    /**
     * Handle sale method changes - mutually exclusive selection
     */
    public function updatedNewItemSaleMethod($value)
    {
        // Reset quantity when switching methods
        $this->newItem['quantity'] = 1;
        
        if ($this->selectedItem && $this->form['warehouse_id']) {
            // Update available stock based on new sale method
            $stock = Stock::where('warehouse_id', $this->form['warehouse_id'])
                ->where('item_id', $this->selectedItem['id'])
                ->first();
                
            if ($stock) {
                if ($value === 'piece') {
                    // Selling by piece - unit price is per piece
                    $this->newItem['unit_price'] = $this->selectedItem['selling_price'] ?? 0;
                    $this->newItem['price'] = $this->newItem['unit_price']; // Price = unit_price for 1 piece
                    $this->availableStock = $stock->piece_count ?? $stock->quantity ?? 0;
                } else {
                    // Selling by unit - unit price is per unit
                    $this->newItem['unit_price'] = $this->selectedItem['selling_price_per_unit'] ?? 0;
                    $this->newItem['price'] = $this->newItem['unit_price']; // Price = unit_price for 1 unit
                    $this->availableStock = $stock->total_units;
                }
            }
        }
    }
    
    /**
     * Handle unit price changes for auto-calculation
     */
    public function updatedNewItemUnitPrice($value)
    {
        // Price is always unit_price (per piece or per unit depending on sale method)
        $this->newItem['price'] = (float)$value;
    }
    
    /**
     * Get available stock based on sale method
     */
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
            // For unit sales, return total available units
            return (float)($stock->total_units ?? 0.0);
        }
    }



    public function proceedWithWarning()
    {
        // Add the item directly when user proceeds with warning
        $this->processAddItem();
        
        // Clear warning modal
        $this->stockWarningType = null;
        $this->stockWarningItem = null;
    }
    
    public function cancelStockWarning()
    {
        $this->stockWarningType = null;
        $this->stockWarningItem = null;
    }



    /**
     * Resolve branch_id from various sources
     */
    private function resolveBranchId(): ?int
    {
        // First priority: explicitly selected branch
        if (!empty($this->form['branch_id'])) {
            return (int)$this->form['branch_id'];
        }
        
        // Second priority: get branch from selected warehouse
        if (!empty($this->form['warehouse_id'])) {
            $warehouse = Warehouse::with('branches')->find($this->form['warehouse_id']);
            if ($warehouse && $warehouse->branches->isNotEmpty()) {
                return (int)$warehouse->branches->first()->id;
            }
        }
        
        // Third priority: user's assigned branch
        if (auth()->user()->branch_id) {
            return (int)auth()->user()->branch_id;
        }
        
        // Fourth priority: get branch from user's assigned warehouse
        if (auth()->user()->warehouse_id) {
            $warehouse = Warehouse::with('branches')->find(auth()->user()->warehouse_id);
            if ($warehouse && $warehouse->branches->isNotEmpty()) {
                return (int)$warehouse->branches->first()->id;
            }
        }
        
        // Last resort: get the first active branch
        $firstBranch = Branch::where('is_active', true)->first();
        if ($firstBranch) {
            return (int)$firstBranch->id;
        }
        
        return null;
    }

    public function render()
    {
        return view('livewire.sales.create');
    }
}