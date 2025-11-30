{{-- Modern SaaS Sales Creation Form --}}
<div>
    <!-- Notification container -->
    <div id="notification-container" class="position-fixed top-0 end-0 z-3 mt-3 me-3"></div>

    <!-- Main Card Container -->
    <div class="card border-0 shadow-sm">
        <!-- Header -->
        <div class="card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Create New Sale</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary mb-0 small">Add items and complete sale details</span>
                        @php
                            $itemCount = is_countable($items) ? count($items) : 0;
                        @endphp
                        @if($itemCount > 0)
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $itemCount }} item{{ $itemCount > 1 ? 's' : '' }}</span>
                        @endif
                        @if($form['payment_method'] === 'credit_advance')
                            <span class="badge bg-warning">Partial Credit</span>
                        @elseif($form['payment_method'] === 'full_credit')
                            <span class="badge bg-danger">Credit Sale</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Back to Sales</span>
                </a>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body p-0">
            <!-- Form Validation & Error Alerts -->
            <div class="p-4 pb-0">
                <!-- Error Alert -->
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-1 small">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

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
            <form id="salesForm" class="p-4 pt-0">
                <!-- Basic Sale Information -->
                <div class="mb-3">
                    <div class="row g-3">
                        <!-- Sale Date -->
                        <div class="col-12 col-md-4">
                            <label for="sale_date" class="form-label fw-medium">
                                Sale Date <span class="text-primary">*</span>
                            </label>
                            <input 
                                type="date" 
                                wire:model.live="form.sale_date" 
                                id="sale_date" 
                                class="form-control @error('form.sale_date') is-invalid @enderror" 
                            >
                            @error('form.sale_date') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>
                        <!-- Customer Selection -->
                        <div class="col-12 col-md-4">
                            <label for="customer_id" class="form-label fw-medium">
                                Customer <span class="text-primary">*</span>
                            </label>
                            @if($selectedCustomer)
                                <div class="input-group">
                                    <input type="text" readonly class="form-control" value="{{ $selectedCustomer['name'] }}">
                                    <button class="btn btn-outline-danger" type="button" wire:click="clearCustomer" title="Clear customer">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            @else
                                <select 
                                    wire:model.live="form.customer_id" 
                                    id="customer_id" 
                                    class="form-select @error('form.customer_id') is-invalid @enderror" 
                                    required 
                                >
                                    <option value="">Select a customer...</option>
                                    @foreach($this->filteredCustomers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('form.customer_id') 
                                <div class="invalid-feedback d-block">{{ $message }}</div> 
                            @enderror
                        </div>
                        <!-- Selling From Selection -->
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <label for="warehouse_id" class="form-label fw-medium mb-0">
                                    Selling From <span class="text-primary">*</span>
                                </label>
                                @php
                                    $itemCount = is_countable($items) ? count($items) : 0;
                                    $warehouseCount = isset($warehouses) && is_object($warehouses) && method_exists($warehouses, 'count') ? $warehouses->count() : 0;
                                @endphp
                                @if($itemCount > 0)
                                    <small class="text-secondary"><i class="bi bi-lock me-1"></i>Locked</small>
                                @elseif($warehouseCount === 1)
                                    <small class="text-info"><i class="bi bi-info-circle me-1"></i>Auto-selected</small>
                                @elseif($warehouseCount === 0)
                                    <small class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>None available</small>
                                @endif
                            </div>
                            @if(!auth()->user()->branch_id && !auth()->user()->warehouse_id)
                                <select 
                                    wire:model.live="form.warehouse_id" 
                                    id="warehouse_id" 
                                    class="form-select @error('form.warehouse_id') is-invalid @enderror" 
                                    {{ (is_countable($items) && count($items) > 0) ? 'disabled' : '' }} 
                                    required 
                                >
                                    <option value="">Select selling location...</option>
                                    @foreach($warehouses as $warehouse)
                                        @php
                                            $stockCount = \App\Models\Stock::where('warehouse_id', $warehouse->id)->where('quantity', '>', 0)->count();
                                        @endphp
                                        <option value="{{ $warehouse->id }}">
                                            @if($warehouse->branch)
                                                {{ $warehouse->branch->name }} - {{ $warehouse->name }}
                                            @else
                                                {{ $warehouse->name }}
                                            @endif
                                            @if($stockCount > 0)
                                                ({{ $stockCount }} items in stock)
                                            @else
                                                (No stock)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" readonly 
                                    value="{{ auth()->user()->branch ? auth()->user()->branch->name . ' (Branch)' : auth()->user()->warehouse->name . ' (Warehouse)' }}">
                            @endif
                            @error('form.warehouse_id') 
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
                                @foreach(\App\Enums\PaymentMethod::forSales() as $method)
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
                                    @php
                                        $hasBankAccounts = isset($bankAccounts) && is_object($bankAccounts) && method_exists($bankAccounts, 'count') && $bankAccounts->count() > 0;
                                    @endphp
                                    @if($hasBankAccounts)
                                        @foreach($bankAccounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->account_name }} - {{ $account->bank_name }}</option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>No bank accounts available</option>
                                    @endif
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
                                placeholder="Additional notes about this sale..."
                            >
                            @error('form.notes') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>
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
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <h6 class="fw-semibold mb-0">Sale Items</h6>
                            @php
                                $itemCount = is_countable($items) ? count($items) : 0;
                            @endphp
                            @if($itemCount > 0)
                                <div class="d-flex align-items-center gap-3">
                                    <small class="text-muted">Subtotal: <span class="fw-semibold">{{ number_format($subtotal, 2) }}</span></small>
                                    <small class="text-muted">Total: <span class="fw-bold">{{ number_format($totalAmount, 2) }}</span></small>
                                </div>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="loadItemOptions" title="Refresh items">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" wire:click="debugStock" title="Debug stock data">
                                <i class="bi bi-bug"></i>
                            </button>
                            @php
                                $itemCount = is_countable($items) ? count($items) : 0;
                            @endphp
                            @if($itemCount > 0)
                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="clearCart"
                                    wire:confirm="Are you sure you want to clear all items from the cart?"
                                    title="Clear all items">
                                    <i class="bi bi-trash me-1"></i>Clear All
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Add Item Form -->
                    <div class="border rounded p-3">
                        <div class="row g-3 align-items-end">
                            <!-- Item Selection -->
                            <div class="col-12 {{ ($selectedItem && !$stockWarningType) ? 'col-lg-2' : 'col-lg-10' }}">
                                <label class="form-label fw-medium">
                                    Item <span class="text-primary">*</span>
                                </label>
                                @if($selectedItem && !$stockWarningType)
                                    <div class="input-group">
                                        <input type="text" readonly class="form-control" value="{{ $selectedItem['name'] }}">
                                        <button class="btn btn-outline-danger" type="button" wire:click="clearSelectedItem" title="Clear item">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                @elseif(!$stockWarningType)
                                    <div class="position-relative">
                                        <input type="text" 
                                               wire:model.live.debounce.300ms="itemSearch" 
                                               class="form-control" 
                                               placeholder="Search items..."
                                               autocomplete="off">
                                        @if(strlen($itemSearch) >= 2)
                                            <div class="dropdown-menu show w-100" style="max-height: 200px; overflow-y: auto;">
                                                @if(count($this->filteredItemOptions) > 0)
                                                    @foreach($this->filteredItemOptions as $item)
                                                        <button type="button" 
                                                                class="dropdown-item py-2" 
                                                                wire:click="selectItem({{ $item['id'] }})">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div class="flex-grow-1 me-2">
                                                                    <div class="fw-medium text-truncate">{{ $item['name'] }}</div>
                                                                    <small class="text-muted">{{ $item['sku'] }}</small>
                                                                </div>
                                                                <div class="text-end flex-shrink-0">
                                                                    @php
                                                                        $stockQty = floatval($item['quantity'] ?? 0);
                                                                    @endphp
                                                                    @if($stockQty < 0)
                                                                        <span class="badge bg-dark text-white small">
                                                                            Negative:<br>{{ number_format($stockQty, 1) }}
                                                                        </span>
                                                                    @elseif($stockQty == 0)
                                                                        <span class="badge bg-warning text-dark small">
                                                                            Out of<br>Stock
                                                                        </span>
                                                                    @elseif($stockQty <= 5)
                                                                        <span class="badge bg-warning text-dark small">
                                                                            Available:<br>{{ number_format($stockQty, 1) }} ⚠️
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-success small">
                                                                            Available:<br>{{ number_format($stockQty, 1) }}
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </button>
                                                    @endforeach
                                                @else
                                                    <div class="dropdown-item-text text-muted">
                                                        <small>No items found for "{{ $itemSearch }}"</small>
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif(strlen($itemSearch) > 0)
                                            <div class="dropdown-menu show w-100">
                                                <div class="dropdown-item-text text-muted">
                                                    <small>Type at least 2 characters to search...</small>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            @if($selectedItem && !$stockWarningType)
                            <!-- Sale Method Toggle -->
                            <div class="col-12 col-lg-2">
                                <label class="form-label fw-medium">Sale Method</label>
                                <div class="btn-group w-100 d-flex" role="group">
                                    <input type="radio" class="btn-check" wire:model.live="newItem.sale_method" value="piece" id="method_piece" name="sale_method">
                                    <label class="btn btn-outline-primary flex-fill text-center" for="method_piece">
                                        <i class="bi bi-box d-block d-sm-inline me-sm-1"></i>
                                        <span class="d-block d-sm-inline">Piece</span>
                                    </label>
                                    <input type="radio" class="btn-check" wire:model.live="newItem.sale_method" value="unit" id="method_unit" name="sale_method">
                                    <label class="btn btn-outline-primary flex-fill text-center" for="method_unit">
                                        <i class="bi bi-rulers d-block d-sm-inline me-sm-1"></i>
                                        <span class="d-block d-sm-inline">{{ $selectedItem['item_unit'] ?? 'Unit' }}</span>
                                    </label>
                                </div>
                            </div>
                            <!-- Quantity -->
                            <div class="col-6 col-lg-2">
                                <label class="form-label fw-medium">Quantity</label>
                                <div class="input-group">
                                    <input type="number" wire:model.live="newItem.quantity" class="form-control" min="1" step="{{ $newItem['sale_method'] === 'unit' ? '0.01' : '1' }}" placeholder="0">
                                    <span class="input-group-text small">
                                        @if($newItem['sale_method'] === 'piece')
                                            pcs
                                        @else
                                            {{ $selectedItem['item_unit'] ?? 'units' }}
                                        @endif
                                    </span>
                                </div>
                                @php
                                    $availableStock = $this->getAvailableStockForMethod();
                                    $requestedQty = floatval($newItem['quantity'] ?? 0);
                                    $willBeNegative = $requestedQty > $availableStock;
                                @endphp
                                <small class="d-block mt-1 {{ $willBeNegative ? 'text-warning' : 'text-muted' }}">
                                    Available: {{ number_format($availableStock, 2) }}
                                    @if($willBeNegative && $requestedQty > 0)
                                        <br><i class="bi bi-exclamation-triangle"></i> Will result in negative stock
                                    @endif
                                </small>
                            </div>
                            <!-- Unit Price -->
                            <div class="col-6 col-lg-2">
                                <label class="form-label fw-medium">Unit<br>Price</label>
                                <input type="number" wire:model.live="newItem.unit_price" class="form-control" min="0" step="0.01" placeholder="0.00">
                            </div>
                            <!-- Total Price -->
                            <div class="col-6 col-lg-2">
                                <label class="form-label fw-medium">Total<br>Price</label>
                                <input type="text" class="form-control" value="{{ number_format((floatval($newItem['quantity'] ?? 0)) * (floatval($newItem['price'] ?? 0)), 2) }}" readonly>
                            </div>
                            <!-- Add Button -->
                            <div class="col-6 col-lg-2 d-flex align-items-end">
                                @if($editingItemIndex !== null)
                                    <div class="btn-group w-100">
                                        <button type="button" class="btn btn-success" wire:click="addItem">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button type="button" class="btn btn-secondary" wire:click="cancelEdit">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                @else
                                    <button type="button" class="btn btn-primary w-100" wire:click="addItem">
                                        <i class="bi bi-plus-lg me-1"></i>Add
                                    </button>
                                @endif
                            </div>
                            @endif
                        </div>
                        


                    </div>
                </div>

                <!-- Items List -->
                @php
                    $itemCount = is_countable($items) ? count($items) : 0;
                @endphp
                @if($itemCount > 0)
                    <div class="mb-4">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="py-3 px-4 fw-semibold text-dark">Item</th>
                                        <th class="text-center py-3 px-3 fw-semibold text-dark">Method</th>
                                        <th class="text-center py-3 px-3 fw-semibold text-dark">Qty</th>
                                        <th class="text-end py-3 px-3 fw-semibold text-dark">Price</th>
                                        <th class="text-end py-3 px-3 fw-semibold text-dark">Total</th>
                                        <th class="text-end py-3 px-4 fw-semibold text-dark" style="width: 80px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $index => $item)
                                    <tr>
                                        <td class="py-3 px-4">
                                            <div class="fw-medium">{{ $item['name'] }}</div>
                                        </td>
                                        <td class="text-center py-3 px-3">
                                            @if(($item['sale_method'] ?? 'piece') === 'piece')
                                                <span class="badge bg-primary-subtle text-primary-emphasis">
                                                    <i class="bi bi-box me-1"></i>Piece
                                                </span>
                                            @else
                                                <span class="badge bg-success-subtle text-success-emphasis">
                                                    <i class="bi bi-rulers me-1"></i>Unit
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center py-3 px-3">
                                            {{ $item['quantity'] }} 
                                            @if(($item['sale_method'] ?? 'piece') === 'piece')
                                                pcs
                                            @else
                                                {{ $item['item_unit'] ?? 'units' }}
                                            @endif
                                        </td>
                                        <td class="text-end py-3 px-3">
                                            {{ number_format($item['price'], 2) }}
                                        </td>
                                        <td class="text-end py-3 px-3">
                                            <span class="fw-semibold">{{ number_format($item['subtotal'], 2) }}</span>
                                        </td>
                                        <td class="text-end py-3 px-4">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary" wire:click="editItem({{ $index }})" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" wire:click="removeItem({{ $index }})" title="Remove">
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
                    @php
                        $itemCount = is_countable($items) ? count($items) : 0;
                    @endphp
                    @if($itemCount > 0)
                        <button type="button" class="btn btn-primary" wire:click="validateAndShowModal" wire:loading.attr="disabled">
                            <i class="bi bi-check-lg me-1"></i>
                            <span wire:loading.remove>Complete Sale</span>
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
                        <i class="bi bi-check-circle me-2 text-success"></i>Confirm Sale
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
                                <span class="text-muted small">Customer:</span>
                                <span class="fw-semibold ms-1">
                                    @if($selectedCustomer)
                                        {{ $selectedCustomer['name'] }}
                                    @else
                                        <span class="text-muted">Not selected</span>
                                    @endif
                                </span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small">Location:</span>
                                <span class="fw-semibold ms-1">
                                    @php
                                        $selectedWarehouse = null;
                                        if (isset($warehouses) && is_object($warehouses) && method_exists($warehouses, 'firstWhere')) {
                                            $selectedWarehouse = $warehouses->firstWhere('id', $form['warehouse_id']);
                                        }
                                    @endphp
                                    @if($selectedWarehouse)
                                        @if(isset($selectedWarehouse->branch) && $selectedWarehouse->branch)
                                            {{ $selectedWarehouse->branch->name }} - {{ $selectedWarehouse->name }}
                                        @else
                                            {{ $selectedWarehouse->name }}
                                        @endif
                                    @else
                                        <span class="text-muted">Not selected</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="text-muted small">Date:</span>
                                <span class="fw-semibold ms-1">{{ \Carbon\Carbon::parse($form['sale_date'])->format('M d, Y') }}</span>
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
                            @php
                                $itemCount = is_countable($items) ? count($items) : 0;
                            @endphp
                            <h6 class="fw-semibold mb-0">Items ({{ $itemCount }})</h6>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th class="border-0 small fw-medium text-dark">Item</th>
                                        <th class="border-0 text-center small fw-medium text-dark" style="width: 80px;">Qty</th>
                                        <th class="border-0 text-end small fw-medium text-dark" style="width: 100px;">Price (ETB)</th>
                                        <th class="border-0 text-end small fw-medium text-dark" style="width: 120px;">Total (ETB)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $item)
                                        <tr>
                                            <td class="small">
                                                <div class="fw-medium">{{ $item['name'] }}</div>
                                            </td>
                                            <td class="text-center small">{{ $item['quantity'] }} pcs</td>
                                            <td class="text-end small">{{ number_format($item['price'], 2) }}</td>
                                            <td class="text-end fw-semibold small">{{ number_format($item['subtotal'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="border-0"></td>
                                        <td class="border-0 text-end small text-muted">Subtotal:</td>
                                        <td class="border-0 text-end small">{{ number_format($subtotal, 2) }}</td>
                                    </tr>
                                    @if($taxAmount > 0)
                                        <tr>
                                            <td colspan="2" class="border-0"></td>
                                            <td class="border-0 text-end small text-muted">Tax ({{ $form['tax'] }}%):</td>
                                            <td class="border-0 text-end small">{{ number_format($taxAmount, 2) }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td colspan="2" class="border-0"></td>
                                        <td class="border-0 text-end fw-bold small">Total:</td>
                                        <td class="border-0 text-end fw-bold small">{{ number_format($totalAmount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="text-muted small">
                        <i class="bi bi-check-circle me-1"></i>
                        Ready to create sale! Please review the details above.
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-success" wire:click="confirmSale" wire:loading.attr="disabled">
                            <i class="bi bi-check-lg me-1"></i>
                            <span wire:loading.remove>Confirm Sale</span>
                            <span wire:loading>Creating...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($stockWarningType)
    <!-- Stock Warning Modal -->
    <div class="modal fade show" id="stockWarningModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @if($stockWarningType === 'out_of_stock')
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-x-circle me-2"></i>Out of Stock
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p><strong>{{ $stockWarningItem['name'] ?? '' }}</strong> is completely out of stock.</p>
                        
                        <div class="alert alert-danger mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Current Stock:</strong> {{ $stockWarningItem['stock'] ?? 0 }}
                                </div>
                                <div class="col-6">
                                    <strong>Unit Price:</strong> ETB {{ number_format($stockWarningItem['price'] ?? 0, 2) }}
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-danger mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>Warning:</strong> Selling this item will create negative inventory. Consider restocking first.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cancelStockWarning">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-danger" wire:click="proceedWithWarning">
                            <i class="bi bi-exclamation-triangle me-1"></i>Sell Anyway
                        </button>
                    </div>
                @else
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle me-2"></i>Stock Warning
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p><strong>{{ $stockWarningItem['name'] ?? '' }}</strong> has insufficient stock.</p>
                        
                        <div class="row mb-3">
                            <div class="col-4">
                                <div class="text-center">
                                    <div class="fw-bold">Available</div>
                                    <div class="fs-4 text-success">{{ $stockWarningItem['available'] ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <div class="fw-bold">Requested</div>
                                    <div class="fs-4 text-primary">{{ $stockWarningItem['requested'] ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <div class="fw-bold">Deficit</div>
                                    <div class="fs-4 text-danger">{{ $stockWarningItem['deficit'] ?? 0 }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Proceeding will result in negative inventory.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cancelStockWarning">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-warning" wire:click="proceedWithWarning">
                            Proceed
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('livewire:init', () => {
            const confirmationModal = document.getElementById('confirmationModal');
            if (confirmationModal) {
                const modal = new bootstrap.Modal(confirmationModal);
                
                Livewire.on('showConfirmationModal', () => {
                    modal.show();
                });
                
                Livewire.on('closeSaleModal', () => {
                    modal.hide();
                });
            }
        });
    </script>
</div>