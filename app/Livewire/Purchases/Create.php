<?php

namespace App\Livewire\Purchases;

use App\Models\{BankAccount, Supplier, Branch, Item, Purchase, Sale, SalePayment, CreditPayment, User};
use App\Enums\{PaymentMethod, PaymentStatus, UserRole};
use App\Services\{PurchaseService, PurchaseValidationService};
use App\Livewire\Purchases\Traits\{HandlesItems, HandlesPayments, HandlesSuppliers};
use Livewire\{Component, Attributes\Layout};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
class Create extends Component
{
    use HandlesItems, HandlesPayments, HandlesSuppliers;

    protected $listeners = [
        'supplierSelected',
        'itemSelected', 
        'setItemAndCost' => 'handleSetItemAndCost',
        'setSupplierManually',
        'itemCreated' => 'handleItemCreated'
    ];

    public float $subtotal = 0;
    public float $taxAmount = 0;
    public float $totalAmount = 0;
    public array $items = [];
    public $branches;
    public $bankAccounts;

    protected function rules(): array
    {
        $baseRules = [
            'form.purchase_date' => 'required|date',
            'form.supplier_id' => 'required|exists:suppliers,id',
            'form.branch_id' => 'required|exists:branches,id',
            'form.payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'form.tax' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
        ];

        return array_merge($baseRules, $this->getPaymentMethodRules());
    }

    private function getPaymentMethodRules(): array
    {
        $rules = [];
        $paymentMethod = $this->form['payment_method'] ?? null;

        if (in_array($paymentMethod, [PaymentMethod::TELEBIRR->value, PaymentMethod::BANK_TRANSFER->value])) {
            $rules['form.transaction_number'] = [
                'required', 'string', 'min:5', 'max:255',
                fn($attribute, $value, $fail) => $this->transactionNumberExists($value) 
                    ? $fail('This transaction number has already been used.') : null
            ];
        }

        if ($paymentMethod === PaymentMethod::BANK_TRANSFER->value) {
            $rules['form.bank_account_id'] = 'required|exists:bank_accounts,id';
        }

        if ($paymentMethod === PaymentMethod::CREDIT_ADVANCE->value) {
            $rules['form.advance_amount'] = 'required|numeric|min:0.01|max:' . $this->totalAmount;
        }

        return $rules;
    }

    protected $messages = [
        'form.supplier_id.required' => 'Please select a supplier.',
        'form.branch_id.required' => 'Please select a branch.',
        'items.required' => 'Please add at least one item to the purchase.',
        'items.min' => 'Please add at least one item to the purchase.',
        'form.transaction_number.required' => 'Transaction number is required for this payment method.',
        'form.transaction_number.min' => 'Transaction number must be at least 5 characters.',
        'form.bank_account_id.required' => 'Please select a bank account.',
        'form.bank_account_id.exists' => 'Selected bank account is invalid.',
    ];

    public array $form = [];

    public function mount(): void
    {
        $this->authorizeAccess();
        $this->initializeForm();
        $this->loadData();
        $this->setDefaultBranch();
        $this->setSupplierFromQuery();
    }

    private function authorizeAccess(): void
    {
        if (!(new PurchaseService())->canCreatePurchases()) {
            abort(403, 'You do not have permission to create purchases.');
        }
    }

    private function initializeForm(): void
    {
        $this->form = [
            'purchase_date' => now()->format('Y-m-d'),
            'reference_no' => $this->generateReferenceNumber(),
            'supplier_id' => '',
            'branch_id' => '',
            'payment_method' => PaymentMethod::defaultForPurchases()->value,
            'payment_status' => PaymentStatus::PAID->value,
            'tax' => 0,
            'transaction_number' => '',
            'bank_account_id' => '',
            'receiver_bank_name' => '',
            'receiver_account_number' => '',
            'advance_amount' => 0,
            'notes' => '',
        ];
    }

    private function loadData(): void
    {
        $this->loadItems();
        $this->suppliers = Supplier::active()->ordered()->get();
        $this->branches = $this->getAccessibleBranches();
        $this->bankAccounts = BankAccount::active()->ordered()->get();
    }

    private function setDefaultBranch(): void
    {
        $user = Auth::user();
        
        if ($user?->branch_id) {
            $this->form['branch_id'] = $user->branch_id;
        } elseif ($this->branches->isNotEmpty()) {
            $this->form['branch_id'] = $this->branches->first()->id;
        }
    }

    private function setSupplierFromQuery(): void
    {
        $supplierId = request()->query('supplier_id');
        
        if ($supplierId && Supplier::active()->find($supplierId)) {
            $this->form['supplier_id'] = $supplierId;
        }
    }

    private function getAccessibleBranches()
    {
        $user = Auth::user();
        
        $userRoles = $user->roles->pluck('name')->toArray();
        $adminRoles = [UserRole::SUPER_ADMIN->value, UserRole::GENERAL_MANAGER->value];
        
        if (!empty(array_intersect($userRoles, $adminRoles))) {
            return Branch::active()->ordered()->get();
        }
        
        if ($user->branch_id) {
            return Branch::active()->where('id', $user->branch_id)->get();
        }
        
        return Branch::active()->ordered()->get();
    }

    private function updateTotals(): void
    {
        $this->subtotal = round(collect($this->items)->sum('subtotal'), 2);
        
        $taxRate = (float) ($this->form['tax'] ?? 0);
        $this->taxAmount = round($this->subtotal * ($taxRate / 100), 2);
        
        $this->totalAmount = round($this->subtotal + $this->taxAmount, 2);
        
        $this->adjustAdvanceAmount();
    }

    private function adjustAdvanceAmount(): void
    {
        if ($this->form['payment_method'] !== PaymentMethod::CREDIT_ADVANCE->value) {
            return;
        }

        $advanceAmount = (float) ($this->form['advance_amount'] ?? 0);
        
        if ($advanceAmount <= 0) {
            $this->form['advance_amount'] = round($this->totalAmount * 0.2, 2);
        } elseif ($advanceAmount > $this->totalAmount) {
            $this->form['advance_amount'] = $this->totalAmount;
        }
    }

    public function updatedFormBranchId($value): void
    {
        $this->form['branch_id'] = (int) $value;
        
        if ($this->form['branch_id'] && !empty($this->newItem['item_id'])) {
            $this->current_stock = $this->getItemStock($this->newItem['item_id']);
        }
        
        if ($this->items) {
            $this->notify('Branch changed. Please verify items and quantities for the new branch.', 'warning');
        }
    }

    public function updatedFormTax(): void
    {
        $this->updateTotals();
    }

    public function save()
    {
        $validation = $this->validatePurchase();
        
        if (!$validation['success']) {
            $this->handleValidationErrors($validation['errors']);
            return false;
        }
        
        try {
            (new PurchaseService())->createPurchase(
                $this->form,
                $this->items,
                $this->totalAmount,
                $this->taxAmount
            );
            
            return redirect()->route('admin.purchases.index')
                ->with('success', 'Purchase created successfully!');
                
        } catch (\Exception $e) {
            $this->handleSaveError($e);
            return false;
        }
    }

    private function validatePurchase(): array
    {
        return (new PurchaseValidationService())->validatePurchaseForm(
            $this->form,
            $this->items,
            $this->totalAmount
        );
    }

    private function handleValidationErrors(array $errors): void
    {
        foreach ($errors as $field => $messages) {
            $messages = is_array($messages) ? $messages : [$messages];
            foreach ($messages as $message) {
                $this->addError($field, $message);
            }
        }
    }

    private function handleSaveError(\Exception $e): void
    {
        $this->addError('general', 'An unexpected error occurred while saving the purchase. Please try again.');
        $this->notify('❌ Error: ' . $e->getMessage(), 'error');
    }

    public function validateAndShowModal(): void
    {
        $validation = $this->validatePurchase();

        if (!$validation['success']) {
            $this->handleValidationErrors($validation['errors']);
            
            if (isset($validation['messages'])) {
                foreach ((array) $validation['messages'] as $message) {
                    $this->notify($message, 'error');
                }
            }

            $this->dispatch('scrollToFirstError');
            return;
        }

        $this->dispatch('showConfirmationModal');
    }

    public function confirmPurchase()
    {
        return $this->save();
    }

    public function cancel()
    {
        $this->resetValidation();
        return $this->redirect(route('admin.purchases.index'));
    }

    public function render()
    {
        $this->updateTotals();

        return view('livewire.purchases.create', [
            'totalAmount' => $this->totalAmount,
            'subtotal' => $this->subtotal,
            'taxAmount' => $this->taxAmount,
        ]);
    }

    private function notify(string $message, string $type = 'info'): void
    {
        $this->dispatch('notify', ['type' => $type, 'message' => $message]);
    }

    private function generateReferenceNumber(): string
    {
        return 'PO-' . now()->format('Ymd') . '-' . str_pad(random_int(1, 999), 3, '0', STR_PAD_LEFT);
    }

    public function setSupplierManually(?int $supplierId = null): void
    {
        if ($supplierId) {
            $this->form['supplier_id'] = $supplierId;
        }
    }

    public function handleSetItemAndCost(array $data): void
    {
        $itemId = $data['itemId'] ?? $data['id'] ?? null;
        $cost = $data['cost'] ?? $data['cost_price'] ?? null;
        
        if (!$itemId || !($item = Item::find($itemId))) {
            return;
        }

        $this->newItem = [
            'item_id' => $item->id,
            'cost' => $cost ?? $item->cost_price,
            'unit' => $item->unit ?? '',
        ];
        
        $this->current_stock = $this->getItemStock($item->id);
    }

    private function transactionNumberExists(string $transactionNumber): bool
    {
        if (empty($transactionNumber)) {
            return false;
        }

        $tables = [
            [Purchase::class, 'transaction_number'],
            [Sale::class, 'transaction_number'],
            [SalePayment::class, 'transaction_number'],
            [CreditPayment::class, 'reference_no'],
        ];

        foreach ($tables as [$model, $column]) {
            if (class_exists($model) && $model::where($column, $transactionNumber)->exists()) {
                return true;
            }
        }

        return false;
    }
}