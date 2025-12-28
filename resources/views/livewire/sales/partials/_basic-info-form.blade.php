{{-- Basic Sale Information Form --}}
<div class="mb-3">
    <div class="row g-3">
        {{-- Sale Date --}}
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

        {{-- Customer Selection --}}
        <div class="col-12 col-md-4">
            <label for="customer_id" class="form-label fw-medium">
                Customer <span class="text-primary {{ $form['is_walking_customer'] ? 'd-none' : '' }}">*</span>
            </label>
            
            @if($form['is_walking_customer'])
                <input type="text" class="form-control" value="Walking Customer" readonly>
            @elseif($selectedCustomer)
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
                    {{ $form['is_walking_customer'] ? 'disabled' : 'required' }}
                >
                    <option value="">Select a customer...</option>
                    @foreach($this->filteredCustomers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            @endif
            
            {{-- Walking Customer Checkbox --}}
            <div class="form-check mt-2">
                <input 
                    class="form-check-input" 
                    type="checkbox" 
                    wire:model.live="form.is_walking_customer" 
                    id="walking_customer"
                >
                <label class="form-check-label small text-muted" for="walking_customer">
                    <i class="bi bi-person-walking me-1"></i>Walking Customer
                </label>
            </div>
            
            @error('form.customer_id') 
                <div class="invalid-feedback d-block">{{ $message }}</div> 
            @enderror
        </div>

        {{-- Selling From Selection --}}
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

        {{-- Payment Method --}}
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
                    @php
                        $isCreditMethod = in_array($method->value, ['full_credit', 'credit_advance']);
                        $isDisabled = $form['is_walking_customer'] && $isCreditMethod;
                        $isHidden = $form['is_walking_customer'] && $isCreditMethod;
                    @endphp
                    @if(!$isHidden)
                        <option value="{{ $method->value }}" {{ $isDisabled ? 'disabled' : '' }}>
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
                    @endif
                @endforeach
            </select>
            @error('form.payment_method') 
                <div class="invalid-feedback">{{ $message }}</div> 
            @enderror
        </div>

        {{-- Bank Transfer Details --}}
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
                    <option value="">Select Bank Account</option>
                    @if(isset($bankAccounts) && $bankAccounts->count() > 0)
                        @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->account_name }} - {{ $account->account_number }}</option>
                        @endforeach
                    @else
                        <option disabled>No bank accounts available</option>
                    @endif
                </select>
                @error('form.bank_account_id') 
                    <div class="invalid-feedback">{{ $message }}</div> 
                @enderror
            </div>
        @endif

        {{-- Payment Method Specific Fields --}}
        @if(in_array($form['payment_method'], ['telebirr', 'bank_transfer']))
            <div class="col-12 col-md-4">
                <label for="transaction_number" class="form-label fw-medium">
                    Transaction Number <span class="text-primary">*</span>
                </label>
                <input 
                    type="text" 
                    wire:model.live="form.transaction_number" 
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

        {{-- Tax Rate --}}
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

        {{-- Notes --}}
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

        {{-- Advance Amount (if credit_advance and not walking customer) --}}
        @if($form['payment_method'] === 'credit_advance' && !$form['is_walking_customer'])
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
        
        {{-- Walking Customer Credit Warning --}}
        @if($form['is_walking_customer'] && in_array($form['payment_method'], ['full_credit', 'credit_advance']))
            <div class="col-12">
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Credit payments are not available for walking customers.</strong> Please select a different payment method.
                </div>
            </div>
        @endif
    </div>
</div>
