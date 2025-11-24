{{-- Modern SaaS Purchase Creation Form --}}
<div>
    <!-- Notification container -->
    <div id="notification-container" class="position-fixed top-0 end-0 z-3 mt-3 me-3"></div>

    <!-- Main Card Container -->
    <div class="card border-0 shadow-sm">
        <!-- Header -->
        <div class="card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Create New Purchase</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary mb-0 small">Add items and complete purchase details</span>
                        @if(count($items) > 0)
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ count($items) }} item{{ count($items) > 1 ? 's' : '' }}</span>
                        @endif
                        @if($form['payment_method'] === 'credit_advance')
                            <span class="badge bg-warning">Partial Credit</span>
                        @elseif($form['payment_method'] === 'full_credit')
                            <span class="badge bg-danger">Credit Purchase</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Back to Purchases</span>
                </a>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body p-0">
            <!-- Form Validation & Error Alerts -->
            <div class="p-4 pb-0">


                <!-- General Error Alert -->
                @if($errors->has('general'))
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Error:</strong> {{ $errors->first('general') }}
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Success Alert -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle me-2"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            </div>

            <!-- Form Content -->
            <form id="purchaseForm" class="p-4 pt-0">
                <!-- Basic Purchase Information -->
                <div class="mb-3">
                    <div class="row g-3">
                        <!-- Purchase Date -->
                        <div class="col-12 col-md-4">
                            <label for="purchase_date" class="form-label fw-medium">
                                Purchase Date <span class="text-primary">*</span>
                            </label>
                            <input 
                                type="date" 
                                wire:model.live="form.purchase_date" 
                                id="purchase_date" 
                                class="form-control @error('form.purchase_date') is-invalid @enderror" 
                            >
                            @error('form.purchase_date') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>
                        <!-- Supplier Selection -->
                        <div class="col-12 col-md-4">
                            <label for="supplier_id" class="form-label fw-medium">
                                Supplier <span class="text-primary">*</span>
                            </label>
                            @if($selectedSupplier)
                                <div class="input-group">
                                    <input type="text" readonly class="form-control" value="{{ $selectedSupplier['name'] }}">
                                    <button class="btn btn-outline-danger" type="button" wire:click="clearSupplier" title="Clear supplier">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            @else
                                <select 
                                    wire:model.live="form.supplier_id" 
                                    id="supplier_id" 
                                    class="form-select @error('form.supplier_id') is-invalid @enderror" 
                                    required 
                                >
                                    <option value="">Select a supplier...</option>
                                    @foreach($this->filteredSuppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('form.supplier_id') 
                                <div class="text-danger small mt-1">{{ $message }}</div> 
                            @enderror
                        </div>
                        <!-- Branch Selection (branch-only mode) -->
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <label for="branch_id" class="form-label fw-medium mb-0">
                                    Branch <span class="text-primary">*</span>
                                </label>
                                @if(count($items) > 0 && $form['branch_id'])
                                    <small class="text-secondary"><i class="bi bi-lock me-1"></i>Locked</small>
                                @elseif(($branches->count() ?? 0) === 1)
                                    <small class="text-info"><i class="bi bi-info-circle me-1"></i>Auto-selected</small>
                                @elseif(($branches->count() ?? 0) === 0)
                                    <small class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>None available</small>
                                @endif
                            </div>
                            <select 
                                wire:model.live="form.branch_id" 
                                id="branch_id" 
                                class="form-select @error('form.branch_id') is-invalid @enderror" 
                                {{ count($items) > 0 && $form['branch_id'] ? 'disabled' : '' }} 
                                required 
                            >
                                <option value="">Select branch...</option>
                                @foreach(($branches ?? collect()) as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('form.branch_id') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>
                        <!-- Payment Method -->
                        <div class="col-12 col-md-4">
                            <label for="payment_method" class="form-label fw-medium">
                                Payment Method <span class="text-primary">*</span>
                            </label>
                            <select 
                                wire:model.live="form.payment_method" 
                                id="payment_method" 
                                class="form-select @error('form.payment_method') is-invalid @enderror" 
                                required
                            >
                                @foreach(\App\Enums\PaymentMethod::forPurchases() as $method)
                                    <option value="{{ $method->value }}">
                                        @switch($method)
                                            @case(\App\Enums\PaymentMethod::CASH)
                                                💵 Cash Payment
                                                @break
                                            @case(\App\Enums\PaymentMethod::BANK_TRANSFER)
                                                🏦 Bank Transfer
                                                @break
                                            @case(\App\Enums\PaymentMethod::TELEBIRR)
                                                📱 Telebirr
                                                @break
                                            @case(\App\Enums\PaymentMethod::CREDIT_ADVANCE)
                                                💳 Credit with Advance
                                                @break
                                            @case(\App\Enums\PaymentMethod::FULL_CREDIT)
                                                📋 Full Credit
                                                @break
                                            @default
                                                {{ $method->label() }}
                                        @endswitch
                                    </option>
                                @endforeach
                            </select>
                            @error('form.payment_method') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>
                        <!-- Payment Method Specific Fields (show only if needed) -->
                        @if($form['payment_method'] === 'telebirr')
                            <div class="col-12 col-md-4">
                                <label for="transaction_number" class="form-label fw-medium">
                                    Transaction Number <span class="text-primary">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="form.transaction_number" 
                                    id="transaction_number" 
                                    class="form-control @error('form.transaction_number') is-invalid @enderror" 
                                    placeholder="Enter transaction number" 
                                    required
                                >
                                @error('form.transaction_number') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                            </div>
                        @endif
                        @if($form['payment_method'] === 'bank_transfer')
                            <div class="col-12 col-md-4">
                                <label for="bank_account_id" class="form-label fw-medium">
                                    Bank Account <span class="text-primary">*</span>
                                </label>
                                <select 
                                    wire:model="form.bank_account_id" 
                                    id="bank_account_id" 
                                    class="form-select @error('form.bank_account_id') is-invalid @enderror" 
                                    required
                                >
                                    <option value="">Select bank account...</option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_name }} - {{ $account->bank_name }}</option>
                                    @endforeach
                                </select>
                                @error('form.bank_account_id') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                            </div>
                        @endif
                        <!-- Tax Rate -->
                        <div class="col-12 col-md-4">
                            <label for="tax" class="form-label fw-medium">
                                Tax Rate (%)
                            </label>
                            <input 
                                type="number" 
                                wire:model.live="form.tax" 
                                id="tax" 
                                class="form-control @error('form.tax') is-invalid @enderror" 
                                placeholder="0" 
                                step="0.01"
                                min="0" 
                                max="100" 
                            >
                            @error('form.tax') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>
                        <!-- Notes -->
                        <div class="col-12 col-md-4">
                            <label for="notes" class="form-label fw-medium">
                                Notes
                            </label>
                            <input 
                                type="text" 
                                wire:model="form.notes" 
                                id="notes" 
                                class="form-control @error('form.notes') is-invalid @enderror" 
                                placeholder="Additional notes about this purchase..."
                            >
                            @error('form.notes') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>
                        <!-- Receipt URL (if bank transfer) -->
                        @if($form['payment_method'] === 'bank_transfer')
                            <div class="col-12 col-md-4">
                                <label for="receipt_url" class="form-label fw-medium">
                                    Receipt URL
                                </label>
                                <input 
                                    type="url" 
                                    wire:model="form.receipt_url" 
                                    id="receipt_url" 
                                    class="form-control @error('form.receipt_url') is-invalid @enderror" 
                                    placeholder="Receipt URL (optional)"
                                >
                                @error('form.receipt_url') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                            </div>
                        @endif
                        <!-- Advance Amount (if credit_advance) -->
                        @if($form['payment_method'] === 'credit_advance')
                            <div class="col-12 col-md-4">
                                <label for="advance_amount" class="form-label fw-medium">
                                    Advance Amount <span class="text-primary">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    wire:model="form.advance_amount" 
                                    id="advance_amount" 
                                    class="form-control @error('form.advance_amount') is-invalid @enderror" 
                                    placeholder="Enter advance amount" 
                                    step="0.01" 
                                    min="0.01"
                                    max="{{ $totalAmount }}"
                                    required
                                >
                                @error('form.advance_amount') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Items Section -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <h6 class="fw-semibold mb-0">Purchase Items</h6>
                            @if(count($items) > 0)
                                <div class="d-flex align-items-center gap-3">
                                    <small class="text-muted">Subtotal: <span class="fw-semibold">{{ number_format($subtotal, 2) }}</span></small>
                                    <small class="text-muted">Total: <span class="fw-semibold">{{ number_format($totalAmount, 2) }}</span></small>
                                </div>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="loadItemOptions" title="Refresh items">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            @if(count($items) > 0)
                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="clearCart"
                                    wire:confirm="Are you sure you want to clear all items from the cart?"
                                    title="Clear all items">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    @error('items') 
                        <div class="text-danger small mt-1">{{ $message }}</div> 
                    @enderror

                    <!-- Add Item Form -->
                    <div class="border rounded p-3">
                        <div class="row g-2 align-items-end">
                            <!-- Item Selection -->
                            <div class="col-12 {{ $selectedItem ? 'col-md-4' : 'col-md-10' }}">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-medium mb-0">
                                        Item <span class="text-primary">*</span>
                                    </label>
                                    <button type="button" 
                                            class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#createItemModal"
                                            title="Create new item">
                                        <i class="bi bi-plus-lg me-1"></i>New Item
                                    </button>
                                </div>
                                @if($selectedItem)
                                    <div class="input-group">
                                        <input type="text" readonly class="form-control" value="{{ $selectedItem['name'] }}">
                                        <button class="btn btn-outline-danger" type="button" wire:click="clearSelectedItem" title="Clear item">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                @else
                                    <div class="position-relative">
                                        <input type="text" 
                                               wire:model.live.debounce.300ms="itemSearch" 
                                               class="form-control" 
                                               placeholder="Search items..."
                                               autocomplete="off">
                                        @if(!empty($itemSearch) && count($this->filteredItemOptions) > 0)
                                            <div class="dropdown-menu show w-100" style="max-height: 200px; overflow-y: auto;">
                                                @foreach($this->filteredItemOptions as $item)
                                                    <button type="button" 
                                                            class="dropdown-item" 
                                                            wire:click="selectItem({{ $item['id'] }})">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <div class="fw-medium">{{ $item['name'] }}</div>
                                                                <small class="text-muted">{{ $item['sku'] }}</small>
                                                            </div>
                                                            <div class="text-end">
                                                                @if($item['current_stock'] <= 0)
                                                                    <span class="badge bg-danger">Out of Stock</span>
                                                                @elseif($item['current_stock'] <= 5)
                                                                    <span class="badge bg-warning">Low Stock: {{ $item['current_stock'] }}</span>
                                                                @else
                                                                    <span class="badge bg-success">Stock: {{ $item['current_stock'] }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            @if($selectedItem)
                            <!-- Quantity -->
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-medium">Qty</label>
                                <div class="input-group">
                                    <input type="number" wire:model.live="newItem.quantity" class="form-control" min="1" step="1" placeholder="0">
                                    <span class="input-group-text">pcs</span>
                                </div>
                            </div>
                            <!-- Unit Cost Input -->
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-medium">Cost/{{ $selectedItem['item_unit'] ?? 'unit' }}</label>
                                <input type="number" 
                                       wire:model.live="newItem.unit_cost" 
                                       class="form-control" 
                                       min="0" 
                                       step="0.01" 
                                       placeholder="0.00">
                            </div>
                            <!-- Calculated Piece Cost -->
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-medium">Cost/piece</label>
                                <input type="text" 
                                       class="form-control" 
                                       value="{{ number_format($newItem['cost'] ?? 0, 2) }}"
                                       readonly>
                            </div>
                            @endif
                            @if($selectedItem)
                            <!-- Add Button -->
                            <div class="col-6 col-md-2 d-flex align-items-end">
                                @if($editingItemIndex !== null)
                                    <div class="d-flex gap-1 w-100">
                                        <button type="button" class="btn btn-success btn-sm flex-fill" wire:click="updateExistingItem">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" wire:click="cancelEdit" title="Cancel editing">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                @else
                                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="addItem">
                                        <i class="bi bi-plus-lg me-1"></i>Add
                                    </button>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Items List -->
                @if(count($items) > 0)
                    <div class="mb-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th class="py-2 px-3 fw-semibold text-dark">Item</th>
                                        <th class="text-center py-2 px-3 fw-semibold text-dark">Qty</th>
                                        <th class="text-center py-2 px-3 fw-semibold text-dark">Unit Qty</th>
                                        <th class="text-end py-2 px-3 fw-semibold text-dark">Cost</th>
                                        <th class="text-end py-2 px-3 fw-semibold text-dark">Total</th>
                                        <th class="text-end py-2 px-3 fw-semibold text-dark" style="width: 80px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $index => $item)
                                    <tr>
                                        <td class="py-2 px-3">
                                            <div class="fw-medium">{{ $item['name'] }}</div>
                                        </td>
                                        <td class="text-center py-2 px-3">
                                            {{ $item['quantity'] }} pcs
                                        </td>
                                        <td class="text-center py-2 px-3">
                                            {{ $item['unit_quantity'] ?? 1 }}
                                        </td>
                                        <td class="text-end py-2 px-3">
                                            {{ number_format($item['cost'], 2) }}
                                        </td>
                                        <td class="text-end py-2 px-3 fw-semibold">
                                            {{ number_format($item['quantity'] * $item['cost'], 2) }}
                                        </td>
                                        <td class="text-end py-2 px-3">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="editItem({{ $index }})" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeItem({{ $index }})" title="Remove">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </form>
        </div>

        <!-- Card Footer -->
        <div class="card-footer border-top-0 px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <button type="button" wire:click="cancel" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <div class="d-flex gap-2">
                    @if(count($items) > 0)
                        <button type="button" class="btn btn-primary" wire:click="validateAndShowModal" wire:loading.attr="disabled">
                            <i class="bi bi-check-lg me-1"></i>
                            <span wire:loading.remove>Complete Purchase</span>
                            <span wire:loading>Processing...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="bi bi-check-circle me-2 text-success"></i>Confirm Purchase Order
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Modal Error Alert -->
                    @if($errors->any())
                        <div class="alert alert-danger mb-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-1 small">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Key Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="text-muted small">Reference:</span>
                                <span class="fw-semibold ms-1">{{ $form['reference_no'] }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small">Supplier:</span>
                                <span class="fw-semibold ms-1">
                                    @if($selectedSupplier)
                                        {{ $selectedSupplier['name'] }}
                                    @else
                                        <span class="text-muted">Not selected</span>
                                    @endif
                                </span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small">Branch:</span>
                                <span class="fw-semibold ms-1">
                                    @php
                                        $selectedBranch = ($branches ?? collect())->firstWhere('id', $form['branch_id'] ?? null);
                                    @endphp
                                    @if($selectedBranch)
                                        {{ $selectedBranch->name }}
                                    @else
                                        <span class="text-muted">Not selected</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="text-muted small">Date:</span>
                                <span class="fw-semibold ms-1">{{ \Carbon\Carbon::parse($form['purchase_date'])->format('M d, Y') }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small">Payment Method:</span>
                                <span class="fw-semibold ms-1">
                                    @switch($form['payment_method'])
                                        @case('cash')
                                            Cash Payment
                                            @break
                                        @case('bank_transfer')
                                            Bank Transfer
                                            @break
                                        @case('telebirr')
                                            Telebirr
                                            @break
                                        @case('credit_advance')
                                            Credit with Advance
                                            @break
                                        @case('full_credit')
                                            Full Credit
                                            @break
                                        @default
                                            {{ ucfirst($form['payment_method']) }}
                                    @endswitch
                                </span>
                            </div>
                            @if($form['payment_method'] === 'credit_advance' && $form['advance_amount'] > 0)
                                <div class="mb-2">
                                    <span class="text-muted small">Advance:</span>
                                    <span class="fw-semibold ms-1">ETB {{ number_format($form['advance_amount'], 2) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Items Summary -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-semibold mb-0">Items ({{ count($items) }})</h6>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th class="border-0 small fw-medium px-3">Item</th>
                                        <th class="border-0 text-center small fw-medium px-3" style="width: 80px;">Qty</th>
                                        <th class="border-0 text-end small fw-medium px-3" style="width: 100px;">Cost (ETB)</th>
                                        <th class="border-0 text-end small fw-medium px-3" style="width: 120px;">Total (ETB)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $item)
                                        <tr>
                                            <td class="small px-3">
                                                <div class="fw-medium">{{ $item['name'] }}</div>
                                            </td>
                                            <td class="text-center small px-3">{{ $item['quantity'] }} pcs</td>
                                            <td class="text-end small px-3">{{ number_format($item['cost'], 2) }}</td>
                                            <td class="text-end fw-semibold small px-3">{{ number_format($item['quantity'] * $item['cost'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="border-0"></td>
                                        <td class="border-0 text-end small text-muted px-3">Subtotal:</td>
                                        <td class="border-0 text-end small px-3">{{ number_format($subtotal, 2) }}</td>
                                    </tr>
                                    @if($taxAmount > 0)
                                        <tr>
                                            <td colspan="2" class="border-0"></td>
                                            <td class="border-0 text-end small text-muted px-3">Tax ({{ $form['tax'] }}%):</td>
                                            <td class="border-0 text-end small px-3">{{ number_format($taxAmount, 2) }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td colspan="2" class="border-0"></td>
                                        <td class="border-0 text-end fw-semibold small px-3">Total:</td>
                                        <td class="border-0 text-end fw-semibold small px-3">{{ number_format($totalAmount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="text-muted small">
                        <i class="bi bi-check-circle me-1"></i>
                        Ready to create purchase order! Please review the details above.
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-success" wire:click="confirmPurchase" wire:loading.attr="disabled">
                            <i class="bi bi-check-lg me-1"></i>
                            <span wire:loading.remove>Confirm Purchase</span>
                            <span wire:loading>Creating...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Modal Handling -->
    <script>
        document.addEventListener('livewire:init', () => {
            // Handle modal events
            const confirmationModal = document.getElementById('confirmationModal');
            if (confirmationModal) {
                const modal = new bootstrap.Modal(confirmationModal);
                
                // Listen for Livewire events to show modal
                Livewire.on('showConfirmationModal', () => {
                    modal.show();
                });
                
                // Listen for Livewire events to close modal
                Livewire.on('closePurchaseModal', () => {
                    modal.hide();
                });
                
                // Listen for successful purchase to close modal and redirect
                Livewire.on('purchaseCompleted', () => {
                    modal.hide();
                });
            }
            
            // Handle scroll to first error
            Livewire.on('scrollToFirstError', () => {
                setTimeout(() => {
                    const firstError = document.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center' 
                        });
                        firstError.focus();
                    }
                }, 100);
            });

            // Handle validation errors in modal
            Livewire.on('validationError', (errors) => {
                console.log('Validation errors:', errors);
            });
            
            // Handle item creation modal
            const createItemModal = document.getElementById('createItemModal');
            if (createItemModal) {
                const modal = new bootstrap.Modal(createItemModal);
                
                Livewire.on('closeModal', () => {
                    modal.hide();
                });
            }
        });
    </script>



    <!-- Create Item Modal -->
    <livewire:purchases.create-item-modal />
</div>
