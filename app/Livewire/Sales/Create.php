<?php

namespace App\Livewire\Sales;

use App\Models\Item;
use App\Services\Sales\SalesServiceContainer;
use App\Services\Sales\FormValidationService;
use App\Traits\HasFlashMessages;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.app')]
class Create extends Component
{
    use HasFlashMessages;

    // Services (cached)
    private ?SalesServiceContainer $services = null;
    private ?FormValidationService $validator = null;

    // Form state
    public array $form = [];
    public array $items = [];
    public array $customers = [];
    public array $warehouses = [];
    public array $branches = [];
    public array $bankAccounts = [];

    // Item management
    public array $newItem = [];
    public string $customerSearch = '';
    public ?array $selectedCustomer = null;
    public string $itemSearch = '';
    public ?array $selectedItem = null;
    public float $availableStock = 0;
    public ?int $editingItemIndex = null;
    public ?string $stockWarningType = null;
    public ?array $stockWarningItem = null;
    public bool $warningAcknowledged = false;

    // Calculations
    public float $subtotal = 0;
    public float $taxAmount = 0;
    public float $totalAmount = 0;
    public float $shippingAmount = 0;

    protected function rules(): array
    {
        return $this->getValidator()->getRules($this->form, $this->totalAmount);
    }

    protected function messages(): array
    {
        return $this->getValidator()->getMessages();
    }

    public function mount(): void
    {
        $this->initializeForm();
        $this->loadInitialData();
        $this->setUserLocation();
    }

    // Customer Management
    public function updatedCustomerSearch(): void
    {
        $this->customers = $this->getServices()->customer()->searchCustomers($this->customerSearch)->toArray();
    }

    public function selectCustomer(int $customerId): void
    {
        $customerData = $this->getServices()->customer()->getCustomerData($customerId);
        
        if ($customerData) {
            $this->form['customer_id'] = $customerData['id'];
            $this->selectedCustomer = $customerData;
            $this->customerSearch = '';
        }
    }

    public function clearCustomer(): void
    {
        $this->form['customer_id'] = '';
        $this->selectedCustomer = null;
        $this->customerSearch = '';
        $this->customers = $this->getServices()->customer()->searchCustomers()->toArray();
    }

    // Item Management
    public function selectItem(int $itemId): void
    {
        $item = Item::find($itemId);
        if (!$item) return;

        $warehouseId = (int)($this->form['warehouse_id'] ?? 0);
        if (!$warehouseId) {
            $this->addError('form.warehouse_id', 'Please select a warehouse first before adding items.');
            return;
        }

        $this->setSelectedItem($item, $warehouseId);
        $this->checkStock($item);
    }

    public function addItem(): void
    {
        $this->validateItem();
        if ($this->getErrorBag()->isNotEmpty()) return;
        
        // Check for warnings only if not already acknowledged
        if (!$this->warningAcknowledged) {
            $this->validateStock();
            if ($this->getErrorBag()->isNotEmpty()) return;
            
            // If there's a warning, show modal and wait for acknowledgment
            if ($this->stockWarningType) {
                return;
            }
        }
        
        // Proceed to add item (either no warnings or warnings acknowledged)
        $this->processAddItem();
        $this->warningAcknowledged = false; // Reset for next item
    }

    public function editItem(int $index): void
    {
        $editData = $this->getServices()->cart()->getItemForEdit($index, $this->items);
        
        if ($editData) {
            $this->editingItemIndex = $index;
            $this->newItem = $editData;
            $this->setItemForEdit($index);
        }
    }

    public function removeItem(int $index): void
    {
        $removedItem = $this->getServices()->cart()->removeItemFromCart($index, $this->items);
        $this->updateTotals();
        $this->notify($removedItem['name'] . ' removed from cart', 'success');
    }

    public function clearCart(): void
    {
        $this->getServices()->cart()->clearCart($this->items);
        $this->updateTotals();
        $this->notify('Cart cleared', 'success');
    }

    // Form Updates
    public function updatedFormIsWalkingCustomer($value): void
    {
        if ($value) {
            $this->clearCustomer();
            
            if (!$this->getServices()->customer()->validateWalkingCustomerPayment($this->form['payment_method'])) {
                $this->form['payment_method'] = 'cash';
                $this->updatePaymentStatus();
            }
        }
    }

    public function updatedFormPaymentMethod(): void
    {
        $this->resetPaymentFields();
        $this->updatePaymentStatus();
        $this->updateTotals();
    }

    public function updatedFormTax(): void { $this->updateTotals(); }
    public function updatedFormShipping(): void { $this->updateTotals(); }
    public function updatedFormBranchId($value): void { if ($value) $this->form['warehouse_id'] = ''; }
    public function updatedFormWarehouseId($value): void { if ($value) $this->form['branch_id'] = ''; }

    // Validation & Error Handling
    public function updatedNewItemQuantity($value): void
    {
        $this->resetErrorBag('newItem.quantity');
        $this->clearStockWarning();
        $this->warningAcknowledged = false; // Reset acknowledgment on change
        
        if ($value && $this->availableStock > 0) {
            $quantity = floatval($value);
            if ($quantity > $this->availableStock) {
                $this->stockWarningType = 'insufficient_stock';
                $this->stockWarningItem = [
                    'name' => $this->selectedItem['name'],
                    'available' => $this->availableStock,
                    'requested' => $quantity,
                    'deficit' => $quantity - $this->availableStock
                ];
            }
        }
    }

    public function updatedNewItemUnitPrice($value): void
    {
        $this->resetErrorBag('newItem.unit_price');
        $this->clearStockWarning();
        $this->warningAcknowledged = false; // Reset acknowledgment on change
        
        if (!$value || floatval($value) <= 0) {
            $this->addError('newItem.unit_price', 'Price must be greater than zero');
            return;
        }
        
        // Debounced below-cost pricing check (only after user stops typing)
        $this->dispatch('checkBelowCostPrice', ['price' => $value]);
    }

    public function updatedFormTransactionNumber(): void { $this->resetErrorBag('form.transaction_number'); }
    public function updatedFormBankAccountId(): void { 
        $this->resetErrorBag(['form.bank_account_id', 'form.transaction_number']);
    }
    public function updatedFormCustomerId(): void { $this->resetErrorBag('form.customer_id'); }

    // Sale Processing
    public function validateAndShowModal(): bool
    {
        try {
            $this->validate();
            $this->dispatch('showConfirmationModal');
            return true;
        } catch (ValidationException $e) {
            $this->addValidationErrors($e);
            return false;
        }
    }

    public function confirmSale()
    {
        $this->resetErrorBag();
        $this->notify('💾 Creating sale...', 'info');

        $result = $this->getServices()->saleConfirmation()->confirmSale(
            $this->form,
            $this->items,
            $this->totalAmount,
            $this->taxAmount,
            $this->shippingAmount
        );

        return $result['success'] 
            ? $this->handleSaleSuccess()
            : $this->handleSaleFailure($result);
    }

    // Stock Warnings
    public function proceedWithWarning(): void
    {
        $this->warningAcknowledged = true;
        $this->clearStockWarning();
        // Don't add item here - let user click Add button again
    }
    
    public function cancelStockWarning(): void
    {
        $this->clearStockWarning();
        $this->warningAcknowledged = false;
    }

    public function checkBelowCostPrice($price): void
    {
        if ($this->selectedItem) {
            $item = Item::find($this->selectedItem['id']);
            if ($item && $item->isPriceBelowCost(floatval($price))) {
                $minPrice = $item->getMinimumSellingPrice();
                $this->stockWarningType = 'below_cost_warning';
                $this->stockWarningItem = [
                    'name' => $item->name,
                    'selling_price' => floatval($price),
                    'cost_price' => $minPrice,
                    'loss_amount' => $minPrice - floatval($price),
                    'loss_percentage' => $minPrice > 0 ? (($minPrice - floatval($price)) / $minPrice) * 100 : 0
                ];
            }
        }
    }

    public function cancelEdit(): void
    {
        $this->editingItemIndex = null;
        $this->clearSelectedItem();
    }

    // Computed Properties
    public function getFilteredCustomersProperty()
    {
        return json_decode(json_encode($this->customers));
    }

    public function getWarehouseOptionsProperty()
    {
        return json_decode(json_encode($this->warehouses));
    }

    public function getFilteredItemOptionsProperty(): array
    {
        if (empty($this->itemSearch) || strlen(trim($this->itemSearch)) < 2) {
            return [];
        }
        
        return $this->searchItems();
    }

    public function isFormReady(): bool
    {
        return !empty($this->form['customer_id']) && 
               !empty($this->items) &&
               ($this->form['branch_id'] || $this->form['warehouse_id']) &&
               $this->getServices()->payment()->isPaymentMethodValid($this->form, $this->totalAmount);
    }

    // Navigation
    public function cancel()
    {
        return redirect()->route('admin.sales.index');
    }

    public function render()
    {
        return view('livewire.sales.create');
    }

    // Service Getters (Cached)
    private function getServices(): SalesServiceContainer
    {
        return $this->services ??= app(SalesServiceContainer::class);
    }

    private function getValidator(): FormValidationService
    {
        return $this->validator ??= app(FormValidationService::class);
    }

    // Initialization Methods
    private function initializeForm(): void
    {
        $this->items = [];
        $this->form = $this->getServices()->saleForm()->getDefaultFormData();
        $this->resetNewItem();
    }

    private function loadInitialData(): void
    {
        $services = $this->getServices();
        $this->customers = $services->customer()->searchCustomers()->toArray();
        
        $locations = $services->location()->loadLocations();
        $this->branches = $locations['branches']->toArray();
        $this->warehouses = $locations['warehouses']->toArray();
        $this->bankAccounts = $services->location()->loadBankAccounts()->toArray();
    }

    private function setUserLocation(): void
    {
        $locationData = $this->getServices()->location()->autoSetUserLocation();
        $this->form = array_merge($this->form, $locationData);
    }

    // Item Management Helpers
    private function resetNewItem(): void
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
    }

    private function setSelectedItem(Item $item, int $warehouseId): void
    {
        $unitPrice = $item->selling_price_per_unit ?? ($item->selling_price / max($item->unit_quantity, 1));
        
        $this->selectedItem = [
            'id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'selling_price_per_unit' => $unitPrice,
            'unit_quantity' => $item->unit_quantity ?? 1,
            'item_unit' => $item->item_unit ?? 'piece',
        ];

        $this->newItem = array_merge($this->newItem, [
            'item_id' => $item->id,
            'sale_method' => 'piece',
            'sale_unit' => 'each',
            'unit_price' => $unitPrice,
            'price' => $unitPrice,
            'quantity' => 1,
        ]);

        $this->availableStock = $item->getStockInWarehouse($warehouseId);
        $this->itemSearch = '';
    }

    private function checkStock(Item $item): void
    {
        if ($this->availableStock <= 0) {
            $this->stockWarningType = 'out_of_stock';
            $this->stockWarningItem = [
                'name' => $item->name,
                'price' => $this->newItem['price'],
                'stock' => $this->availableStock
            ];
        }
    }

    public function clearSelectedItem(): void
    {
        $this->resetNewItem();
        $this->selectedItem = null;
        $this->availableStock = 0;
        $this->itemSearch = '';
    }

    private function clearStockWarning(): void
    {
        $this->stockWarningType = null;
        $this->stockWarningItem = null;
    }

    // Validation Methods
    private function validateItem(): void
    {
        $this->validate([
            'newItem.item_id' => 'required|exists:items,id',
            'newItem.quantity' => 'required|numeric|min:1',
            'newItem.unit_price' => 'required|numeric|min:0.01',
        ], [
            'newItem.item_id.required' => 'Please select an item',
            'newItem.quantity.required' => 'Please enter quantity',
            'newItem.quantity.min' => 'Quantity must be at least 1',
            'newItem.unit_price.required' => 'Please enter price',
            'newItem.unit_price.min' => 'Price must be greater than zero',
        ]);
    }

    private function validateStock(): void
    {
        $quantity = floatval($this->newItem['quantity']);
        $price = floatval($this->newItem['unit_price']);
        
        // Priority 1: Check for below-cost pricing (most critical)
        if ($this->selectedItem && $price > 0) {
            $item = Item::find($this->selectedItem['id']);
            if ($item && $item->isPriceBelowCost($price)) {
                $minPrice = $item->getMinimumSellingPrice();
                $this->stockWarningType = 'below_cost_warning';
                $this->stockWarningItem = [
                    'name' => $item->name,
                    'selling_price' => $price,
                    'cost_price' => $minPrice,
                    'loss_amount' => $minPrice - $price,
                    'loss_percentage' => $minPrice > 0 ? (($minPrice - $price) / $minPrice) * 100 : 0
                ];
                return; // Stop here, handle pricing first
            }
        }
        
        // Priority 2: Check stock availability (warning, not error)
        if ($this->availableStock <= 0 && !$this->warningAcknowledged) {
            $this->stockWarningType = 'out_of_stock';
            $this->stockWarningItem = [
                'name' => $this->selectedItem['name'],
                'price' => $price,
                'stock' => $this->availableStock
            ];
            return;
        }
        
        // Priority 3: Check quantity vs available stock
        if ($quantity > $this->availableStock && !$this->warningAcknowledged) {
            $this->stockWarningType = 'insufficient_stock';
            $this->stockWarningItem = [
                'name' => $this->selectedItem['name'],
                'available' => $this->availableStock,
                'requested' => $quantity,
                'deficit' => $quantity - $this->availableStock
            ];
        }
    }

    private function addValidationErrors(ValidationException $e): void
    {
        foreach ($e->validator->errors()->getMessages() as $field => $messages) {
            foreach ($messages as $message) {
                $this->addError($field, $message);
            }
        }
    }

    // Item Processing
    private function processAddItem(): void
    {
        $itemData = $this->buildItemData();
        
        if ($this->editingItemIndex !== null) {
            $this->items[$this->editingItemIndex] = $itemData;
            $message = 'Item updated successfully';
        } else {
            $this->items[] = $itemData;
            $message = 'Item added successfully';
        }
        
        $this->editingItemIndex = null;
        $this->notify($message, 'success');
        $this->updateTotals();
        $this->clearSelectedItem();
    }

    private function buildItemData(): array
    {
        if (!$this->selectedItem && !empty($this->newItem['item_id'])) {
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
        
        return [
            'item_id' => $this->newItem['item_id'] ?? '',
            'name' => $this->selectedItem['name'] ?? 'Unknown Item',
            'sku' => $this->selectedItem['sku'] ?? '',
            'quantity' => floatval($this->newItem['quantity'] ?? 0),
            'sale_method' => $this->newItem['sale_method'] ?? 'piece',
            'price' => floatval($this->newItem['unit_price'] ?? 0),
            'subtotal' => floatval($this->newItem['quantity'] ?? 0) * floatval($this->newItem['unit_price'] ?? 0),
            'unit_quantity' => $this->selectedItem['unit_quantity'] ?? 1,
            'item_unit' => $this->selectedItem['item_unit'] ?? 'piece',
            'notes' => $this->newItem['notes'] ?? null,
        ];
    }

    private function setItemForEdit(int $index): void
    {
        $item = $this->items[$index];
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
            
            $this->availableStock = $this->getServices()->cart()->getAvailableStockForEdit(
                $item['item_id'],
                $item['quantity'],
                (int)($this->form['warehouse_id'] ?? 0),
                (int)($this->form['branch_id'] ?? 0)
            );
        }
    }

    // Search Methods
    private function searchItems(): array
    {
        $warehouseId = (int)($this->form['warehouse_id'] ?? 0);
        $searchTerm = strtolower(trim($this->itemSearch));
        
        $user = Auth::user();
        $query = Item::where('is_active', true);
        
        // Apply branch filtering for non-admin users
        if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
            if ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }
        }
        
        return $query->where(function ($query) use ($searchTerm) {
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"])
                      ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$searchTerm}%"]);
            })
            ->limit(15)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => $warehouseId ? $item->getStockInWarehouse($warehouseId) : 0,
                'selling_price_per_unit' => $item->selling_price_per_unit ?: ($item->selling_price / max($item->unit_quantity, 1)),
                'unit_quantity' => $item->unit_quantity ?? 1,
                'item_unit' => $item->item_unit ?? 'piece',
            ])
            ->toArray();
    }

    // Form Management
    private function resetPaymentFields(): void
    {
        $this->form = array_merge($this->form, [
            'transaction_number' => '',
            'bank_account_id' => '',
            'receiver_account_holder' => '',
            'receipt_url' => '',
            'advance_amount' => 0,
        ]);

        $this->resetErrorBag([
            'form.transaction_number',
            'form.bank_account_id',
            'form.advance_amount'
        ]);
    }

    private function updateTotals(): void
    {
        $this->subtotal = array_sum(array_column($this->items, 'subtotal'));
        $this->taxAmount = ($this->subtotal * ($this->form['tax'] ?? 0)) / 100;
        $this->shippingAmount = floatval($this->form['shipping'] ?? 0);
        $this->totalAmount = $this->subtotal + $this->taxAmount + $this->shippingAmount;
        
        $this->updatePaymentStatus();
    }

    private function updatePaymentStatus(): void
    {
        $this->getServices()->payment()->updatePaymentStatus($this->form, $this->totalAmount);
    }

    // Sale Result Handlers
    private function handleSaleSuccess()
    {
        $this->dispatch('closeSaleModal');
        $this->dispatch('saleCompleted');
        return redirect()->route('admin.sales.index')->with('success', 'Sale completed successfully.');
    }

    private function handleSaleFailure(array $result): bool
    {
        $this->dispatch('closeSaleModal');

        if ($result['type'] === 'validation') {
            foreach ($result['errors'] as $field => $message) {
                $this->addError($field, $message);
            }
            $this->notify('❌ Please fix the errors before creating the sale.', 'error');
            $this->dispatch('scrollToFirstError');
        } else {
            $this->addError('general', 'Sale creation failed: ' . $result['message']);
            $this->notify('❌ Sale creation failed: ' . $result['message'], 'error');
        }

        return false;
    }

    // Notification Helper
    private function notify(string $message, string $type = 'info'): void
    {
        match($type) {
            'success' => $this->flashSuccess($message),
            default => $this->dispatch('notify', ['type' => $type, 'message' => $message])
        };
    }
}