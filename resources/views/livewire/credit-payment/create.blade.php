<div>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-12 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="mb-1">
                                    Make Payment for 
                                    @if ($credit->credit_type === 'receivable')
                                        {{ $credit->customer->name ?? 'Unknown Customer' }}
                                    @else
                                        {{ $credit->supplier->name ?? 'Unknown Supplier' }}
                                    @endif
                                    for 
                                    @if ($credit->reference_type === 'purchase' && $credit->purchase)
                                        <a href="{{ route('admin.purchases.show', $credit->purchase->id) }}" class="text-decoration-none">
                                            Purchase #{{ $credit->purchase->reference_no }}
                                        </a>
                                    @elseif ($credit->reference_type === 'sale' && $credit->sale)
                                        <a href="{{ route('admin.sales.show', $credit->sale->id) }}" class="text-decoration-none">
                                            Sale #{{ $credit->sale->reference_no }}
                                        </a>
                                    @else
                                        {{ $credit->reference_no }}
                                    @endif
                                </h5>
                            </div>
                            <a href="{{ route('admin.credits.show', $credit->id) }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-4">
                                <div>
                                    <small class="text-muted">Total Amount:</small>
                                    <div class="fw-bold">{{ number_format($credit->amount, 2) }} ETB</div>
                                </div>
                                <div>
                                    <small class="text-muted">Paid Amount:</small>
                                    <div class="fw-bold text-success">{{ number_format($credit->paid_amount, 2) }} ETB</div>
                                </div>
                                <div>
                                    <small class="text-muted">Payment Progress:</small>
                                    <div class="fw-bold text-info">{{ number_format(($credit->paid_amount / $credit->amount) * 100, 1) }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <!-- Global Validation Messages (excluding notifications handled by JavaScript) -->
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>There were errors with your submission:</strong>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <!-- End Global Messages -->
                        <!-- Add loading indicator during form processing -->
                        <div wire:loading wire:target="store" class="alert alert-info mb-4">
                            <i class="fas fa-spinner fa-spin me-2"></i> Processing payment, please wait...
                        </div>
                        


                        <!-- Early Closure Offer Section - Only for Payable Credits --

                        <!-- Accept Form - Show Purchased Items with Negotiated Prices -->
                        @if($showNegotiationForm)
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-shopping-cart me-2"></i>
                                        Purchased Items - Enter {{ isset($closingOffer['is_fully_paid']) && $closingOffer['is_fully_paid'] ? 'Closing' : 'Negotiated' }} Prices (per base unit)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @php
                                        $purchase = $credit->purchase;
                                    @endphp
                                    
                                    @if($purchase)
                                        <form wire:submit.prevent="calculateSavings">
                                            <!-- Desktop Table -->
                                            <div class="d-none d-lg-block">
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Item Name</th>
                                                                <th>Quantity</th>
                                                                <th>Original Unit Cost (per piece)</th>
                                                                <th>Original Unit Cost (per item)</th>
                                                                <th>Closing Unit Price (per piece)</th>
                                                                <th>Closing Unit Cost (per item)</th>
                                                                <th>Total Original Cost</th>
                                                                <th>Total Closing Cost</th>
                                                                <th>Profit/Loss</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($purchase->items as $item)
                                                                @php
                                                                    $unitQuantity = $item->item->unit_quantity ?: 1;
                                                                    $originalUnitCostPerItem = $item->unit_cost / $unitQuantity;
                                                                    $negotiatedUnitCostPerItem = isset($negotiatedPrices[$item->item_id]) ? $negotiatedPrices[$item->item_id] / $unitQuantity : $originalUnitCostPerItem;
                                                                    $originalTotalCost = $item->unit_cost * $item->quantity;
                                                                    $negotiatedTotalCost = isset($negotiatedPrices[$item->item_id]) ? $negotiatedPrices[$item->item_id] * $item->quantity : $originalTotalCost;
                                                                    $profitLoss = $originalTotalCost - $negotiatedTotalCost;
                                                                @endphp
                                                                <tr>
                                                                    <td><strong>{{ $item->item->name }}</strong></td>
                                                                    <td>{{ $item->quantity }}</td>
                                                                    <td>{{ number_format($item->unit_cost, 2) }} ETB</td>
                                                                    <td>{{ number_format($originalUnitCostPerItem, 2) }} ETB</td>
                                                                    <td>
                                                                        <input type="number" 
                                                                            wire:model.defer="negotiatedPrices.{{ $item->item_id }}" 
                                                                            class="form-control form-control-sm" 
                                                                            step="0.01" 
                                                                            min="0" 
                                                                            placeholder="Enter closing price"
                                                                            style="width: 120px;">
                                                                    </td>
                                                                    <td>{{ number_format($negotiatedUnitCostPerItem, 2) }} ETB</td>
                                                                    <td>{{ number_format($originalTotalCost, 2) }} ETB</td>
                                                                    <td>{{ number_format($negotiatedTotalCost, 2) }} ETB</td>
                                                                    <td>
                                                                        @if(isset($negotiatedPrices[$item->item_id]))
                                                                            <span class="{{ $profitLoss >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                                                                {{ $profitLoss >= 0 ? '+' : '' }}{{ number_format($profitLoss, 2) }} ETB
                                                                            </span>
                                                                        @else
                                                                            <span class="text-muted">-</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Mobile Cards -->
                                            <div class="d-lg-none">
                                                @foreach($purchase->items as $item)
                                                    @php
                                                        $unitQuantity = $item->item->unit_quantity ?: 1;
                                                        $originalUnitCostPerItem = $item->unit_cost / $unitQuantity;
                                                        $negotiatedUnitCostPerItem = isset($negotiatedPrices[$item->item_id]) ? $negotiatedPrices[$item->item_id] / $unitQuantity : $originalUnitCostPerItem;
                                                        $originalTotalCost = $item->unit_cost * $item->quantity;
                                                        $negotiatedTotalCost = isset($negotiatedPrices[$item->item_id]) ? $negotiatedPrices[$item->item_id] * $item->quantity : $originalTotalCost;
                                                        $profitLoss = $originalTotalCost - $negotiatedTotalCost;
                                                    @endphp
                                                    <div class="card mb-3">
                                                        <div class="card-body p-3">
                                                            <div class="row g-2">
                                                                <div class="col-12">
                                                                    <h6 class="mb-2">{{ $item->item->name }}</h6>
                                                                </div>
                                                                <div class="col-6">
                                                                    <small class="text-muted">Quantity:</small>
                                                                    <div class="fw-bold">{{ $item->quantity }}</div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <small class="text-muted">Original Unit Cost (per piece):</small>
                                                                    <div class="fw-bold">{{ number_format($item->unit_cost, 2) }} ETB</div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <small class="text-muted">Original Unit Cost (per item):</small>
                                                                    <div class="fw-bold">{{ number_format($originalUnitCostPerItem, 2) }} ETB</div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <small class="text-muted">Total Original Cost:</small>
                                                                    <div class="fw-bold">{{ number_format($originalTotalCost, 2) }} ETB</div>
                                                                </div>
                                                                <div class="col-12">
                                                                    <small class="text-muted">Closing Unit Price (per piece):</small>
                                                                    <input type="number" 
                                                                        wire:model.defer="negotiatedPrices.{{ $item->item_id }}" 
                                                                        class="form-control" 
                                                                        step="0.01" 
                                                                        min="0" 
                                                                        placeholder="Enter closing price">
                                                                </div>
                                                                @if(isset($negotiatedPrices[$item->item_id]))
                                                                    <div class="col-6">
                                                                        <small class="text-muted">Closing Unit Cost (per item):</small>
                                                                        <div class="fw-bold">{{ number_format($negotiatedUnitCostPerItem, 2) }} ETB</div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <small class="text-muted">Total Closing Cost:</small>
                                                                        <div class="fw-bold">{{ number_format($negotiatedTotalCost, 2) }} ETB</div>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <small class="text-muted">Profit/Loss:</small>
                                                                        <div class="{{ $profitLoss >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                                                            {{ $profitLoss >= 0 ? '+' : '' }}{{ number_format($profitLoss, 2) }} ETB
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            
                                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3">
                                                <button type="button" wire:click="calculateSavings" class="btn btn-info btn-sm">
                                                    <i class="fas fa-calculator me-1"></i> Recalculate Profit/Loss
                                                </button>
                                                <button type="button" wire:click="declineClosingOffer" class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-times me-1"></i> Cancel
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Profit/Loss Calculation Results -->
                        @if($savingsCalculation && $savingsCalculation['success'])
                            <div class="card mb-4">
                                <div class="card-header {{ $savingsCalculation['total_savings'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-chart-line me-2"></i>
                                        Profit/Loss Analysis Results
                                        @if(isset($savingsCalculation['can_close']) && $savingsCalculation['can_close'])
                                            <span class="badge bg-success ms-2">Credit Can Be Closed</span>
                                        @elseif(isset($savingsCalculation['shortfall']))
                                            <span class="badge bg-warning ms-2">Shortfall: {{ number_format($savingsCalculation['shortfall'], 2) }} ETB</span>
                                        @endif
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <h6 class="text-muted mb-2">Purchase Analysis</h6>
                                            <div class="d-flex flex-column gap-1">
                                                <div><strong>Total Original Cost:</strong> {{ number_format($savingsCalculation['total_original_cost'], 2) }} ETB</div>
                                                <div><strong>Total Closing Cost:</strong> {{ number_format($savingsCalculation['total_closing_cost'], 2) }} ETB</div>
                                                <div>
                                                    <strong>Profit/Loss:</strong> 
                                                    @if($savingsCalculation['total_savings'] >= 0)
                                                        <span class="text-success fw-bold">+{{ number_format($savingsCalculation['total_savings'], 2) }} ETB (Profit)</span>
                                                    @else
                                                        <span class="text-danger fw-bold">{{ number_format($savingsCalculation['total_savings'], 2) }} ETB (Loss)</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <strong>Profit/Loss %:</strong> 
                                                    @if($savingsCalculation['overall_savings_percentage'] >= 0)
                                                        <span class="text-success fw-bold">+{{ number_format($savingsCalculation['overall_savings_percentage'], 1) }}%</span>
                                                    @else
                                                        <span class="text-danger fw-bold">{{ number_format($savingsCalculation['overall_savings_percentage'], 1) }}%</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <h6 class="text-muted mb-2">Credit Closure Details</h6>
                                            <div class="d-flex flex-column gap-1">
                                                <div><strong>Current Balance:</strong> {{ number_format($savingsCalculation['current_balance'], 2) }} ETB</div>
                                                <div><strong>Final Payment:</strong> <span class="text-success fw-bold">{{ number_format($savingsCalculation['final_closing_amount'], 2) }} ETB</span></div>
                                                <div>
                                                    <strong>Amount Saved:</strong> 
                                                    @if($savingsCalculation['total_savings'] >= 0)
                                                        <span class="text-success fw-bold">+{{ number_format($savingsCalculation['total_savings'], 2) }} ETB</span>
                                                    @else
                                                        <span class="text-danger fw-bold">{{ number_format($savingsCalculation['total_savings'], 2) }} ETB</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex flex-column flex-sm-row gap-2 mt-3">
                                        <button type="button" wire:click="acceptClosingOffer" class="btn btn-success btn-sm" {{ isset($savingsCalculation['can_close']) && !$savingsCalculation['can_close'] ? 'disabled' : '' }}>
                                            <i class="fas fa-check me-1"></i> Accept & Close Credit
                                        </button>
                                        <button type="button" wire:click="declineClosingOffer" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-times me-1"></i> Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <form wire:submit.prevent="confirmPayment">
                            @if($credit->credit_type === 'payable')
                                <div class="row g-3 mb-3">
                                    <div class="col-12">
                                        <label for="paymentType" class="form-label">Payment Type</label>
                                        <select wire:model.live="paymentType" id="paymentType" class="form-select @error('paymentType') is-invalid @enderror" required>
                                            <option value="down_payment">Down Payment (Partial)</option>
                                            <option value="closing_payment">Closing Payment (Final with closing prices)</option>
                                        </select>
                                        @error('paymentType')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            @if($paymentType === 'down_payment')
                                                <span class="text-muted">Make a partial payment without closing prices.</span>
                                                @if($amount >= $credit->balance)
                                                    <br><span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>You're paying the full amount. Consider selecting "Closing Payment" instead.</span>
                                                @endif
                                            @else
                                                <span class="text-muted">Close the credit by entering final closing prices for all items.</span>
                                                <br><span class="text-info"><i class="fas fa-info-circle me-1"></i>You'll be asked to enter closing prices for each item to calculate savings.</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="amount" class="form-label">Payment Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">ETB</span>
                                        <input type="number" 
                                            wire:model.defer="amount" 
                                            id="amount" 
                                            class="form-control @error('amount') is-invalid @enderror" 
                                            step="0.01" 
                                            min="0.01" 
                                            max="{{ $credit->balance }}" 
                                            required>
                                    </div>
                                    @error('amount')
                                        @if(!strpos($message, 'closing prices must be entered'))
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @endif
                                    @enderror
                                    <div class="form-text">Maximum payment amount: {{ number_format($credit->balance, 2) }} ETB</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="payment_date" class="form-label">Payment Date</label>
                                    <input type="date" 
                                        wire:model.defer="payment_date" 
                                        id="payment_date" 
                                        class="form-control @error('payment_date') is-invalid @enderror" 
                                        required>
                                    @error('payment_date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="payment_method" class="form-label">Payment Method</label>
                                    <select wire:model.live="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="telebirr">Telebirr</option>
                                        <option value="check">Check/Cheque</option>
                                    </select>
                                    @error('payment_method')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Payment Method Specific Fields -->
                                <div class="col-12 col-md-6">
                                    <!-- Show loading indicator when changing payment methods -->
                                    <div wire:loading wire:target="payment_method" class="mb-2">
                                        <small><i class="fas fa-spinner fa-spin"></i> Loading fields...</small>
                                    </div>
                                    
                                    <div wire:loading.remove wire:target="payment_method">
                                        @if($payment_method === 'cash')
                                            <!-- For Cash -->
                                            <label for="reference_no" class="form-label">Receipt Number (Optional)</label>
                                            <input type="text" 
                                                wire:model.defer="reference_no" 
                                                id="reference_no" 
                                                class="form-control @error('reference_no') is-invalid @enderror" 
                                                placeholder="Receipt Number">
                                            @error('reference_no')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        @elseif($payment_method === 'bank_transfer')
                                            <!-- For Bank Transfer -->
                                            <label for="bank_account_id" class="form-label">Bank Account</label>
                                            <select wire:model.defer="bank_account_id" id="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror" required>
                                                <option value="">Select Bank Account</option>
                                                @foreach($bankAccounts as $account)
                                                    <option value="{{ $account->id }}">{{ $account->account_name }} - {{ $account->bank_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('bank_account_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        @elseif($payment_method === 'telebirr')
                                            <!-- For Telebirr -->
                                            <label for="transaction_number" class="form-label">Transaction Number</label>
                                            <input type="text" 
                                                wire:model.defer="transaction_number" 
                                                id="transaction_number" 
                                                class="form-control @error('transaction_number') is-invalid @enderror" 
                                                placeholder="Telebirr Transaction ID"
                                                required>
                                            @error('transaction_number')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        @elseif($payment_method === 'check')
                                            <!-- For Check -->
                                            <label for="reference_no" class="form-label">Check Number</label>
                                            <input type="text" 
                                                wire:model.defer="reference_no" 
                                                id="reference_no" 
                                                class="form-control @error('reference_no') is-invalid @enderror" 
                                                placeholder="Check Number"
                                                required>
                                            @error('reference_no')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($payment_method === 'bank_transfer')
                                <div class="row">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Please ensure that the bank transfer has been completed before recording this payment.
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Bank Details Section - Only for Bank Transfer and Telebirr -->
                            @if(in_array($payment_method, ['bank_transfer', 'telebirr']))
                                <div class="row g-3">
                                    <div class="col-12 col-md-4">
                                        <label for="receiver_bank_name" class="form-label">Receiver Bank Name</label>
                                        <select wire:model.defer="receiver_bank_name" 
                                            id="receiver_bank_name" 
                                            class="form-select @error('receiver_bank_name') is-invalid @enderror">
                                            <option value="">Select Bank</option>
                                            @foreach($this->banks as $bank)
                                                <option value="{{ $bank }}">{{ $bank }}</option>
                                            @endforeach
                                        </select>
                                        @error('receiver_bank_name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <label for="receiver_account_holder" class="form-label">Account Holder Name</label>
                                        <input type="text" 
                                            wire:model.defer="receiver_account_holder" 
                                            id="receiver_account_holder" 
                                            class="form-control @error('receiver_account_holder') is-invalid @enderror" 
                                            placeholder="Account holder name">
                                        @error('receiver_account_holder')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <label for="receiver_account_number" class="form-label">Account Number</label>
                                        <input type="text" 
                                            wire:model.defer="receiver_account_number" 
                                            id="receiver_account_number" 
                                            class="form-control @error('receiver_account_number') is-invalid @enderror" 
                                            placeholder="Account number">
                                        @error('receiver_account_number')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            <!-- Reference Field - Available for all payment methods -->
                            <div class="mb-3">
                                <label for="reference" class="form-label">Reference (Optional)</label>
                                <textarea wire:model.defer="reference" id="reference" class="form-control @error('reference') is-invalid @enderror" rows="3" placeholder="Payment reference or additional notes"></textarea>
                                @error('reference')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                                <a href="{{ route('admin.credits.show', $credit->id) }}" class="btn btn-secondary" wire:loading.attr="disabled">Cancel</a>
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="confirmPayment">
                                    <span wire:loading.remove wire:target="confirmPayment"><i class="fas fa-check me-1"></i> Review Payment</span>
                                    <span wire:loading wire:target="confirmPayment"><i class="fas fa-spinner fa-spin me-1"></i> Validating...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    @if($showConfirmation)
    <div class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Confirm Payment
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        Please review the payment details before confirming. This action cannot be undone.
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <h6 class="text-muted mb-2">Payment Summary</h6>
                            <div class="d-flex flex-column gap-1">
                                <div><strong>Amount:</strong> {{ number_format($amount, 2) }} ETB</div>
                                <div><strong>Method:</strong> {{ ucfirst(str_replace('_', ' ', $payment_method)) }}</div>
                                <div><strong>Date:</strong> {{ \Carbon\Carbon::parse($payment_date)->format('M d, Y') }}</div>
                                
                                @if($payment_method === 'bank_transfer' && $bank_account_id)
                                    @php
                                        $selectedAccount = $bankAccounts->firstWhere('id', $bank_account_id);
                                    @endphp
                                    @if($selectedAccount)
                                        <div><strong>Bank Account:</strong> {{ $selectedAccount->account_name }} - {{ $selectedAccount->bank_name }}</div>
                                    @endif
                                @endif
                                
                                @if($payment_method === 'telebirr' && $transaction_number)
                                    <div><strong>Transaction ID:</strong> {{ $transaction_number }}</div>
                                @endif
                                
                                @if(in_array($payment_method, ['cash', 'check']) && $reference_no)
                                    <div><strong>{{ $payment_method === 'check' ? 'Check Number' : 'Receipt Number' }}:</strong> {{ $reference_no }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <h6 class="text-muted mb-2">Credit Information</h6>
                            <div class="d-flex flex-column gap-1">
                                <div><strong>Credit #:</strong> {{ $credit->reference_no }}</div>
                                <div><strong>Current Balance:</strong> {{ number_format($credit->balance, 2) }} ETB</div>
                                <div><strong>Remaining After Payment:</strong> 
                                    <span class="text-success">{{ number_format($credit->balance - $amount, 2) }} ETB</span>
                                </div>
                                @if($credit->balance - $amount <= 0)
                                    <div><span class="badge bg-success">Credit will be fully paid</span></div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    @if($receiver_bank_name || $receiver_account_holder || $receiver_account_number)
                        <div class="mt-3">
                            <h6 class="text-muted mb-2">Bank Details</h6>
                            @if($receiver_bank_name)
                                <p class="mb-1"><strong>Bank Name:</strong> {{ $receiver_bank_name }}</p>
                            @endif
                            @if($receiver_account_holder)
                                <p class="mb-1"><strong>Account Holder:</strong> {{ $receiver_account_holder }}</p>
                            @endif
                            @if($receiver_account_number)
                                <p class="mb-1"><strong>Account Number:</strong> {{ $receiver_account_number }}</p>
                            @endif
                        </div>
                    @endif

                    @if($reference)
                        <div class="mt-3">
                            <h6 class="text-muted mb-1">Reference</h6>
                            <p class="mb-0">{{ $reference }}</p>
                        </div>
                    @endif

                    <!-- Early Closure Information -->
                    @if($savingsCalculation && $savingsCalculation['success'])
                        <div class="mt-3">
                            <div class="alert {{ $savingsCalculation['total_savings'] >= 0 ? 'alert-success' : 'alert-danger' }}">
                                <h6 class="mb-2">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Early Closure Summary
                                </h6>
                                <div class="row g-2">
                                    <div class="col-12 col-md-6">
                                        <div><strong>Total Original Cost:</strong> {{ number_format($savingsCalculation['total_original_cost'], 2) }} ETB</div>
                                        <div><strong>Total Closing Cost:</strong> {{ number_format($savingsCalculation['total_closing_cost'], 2) }} ETB</div>
                                        <div>
                                            <strong>Profit/Loss:</strong> 
                                            @if($savingsCalculation['total_savings'] >= 0)
                                                <span class="text-success fw-bold">+{{ number_format($savingsCalculation['total_savings'], 2) }} ETB</span>
                                            @else
                                                <span class="text-danger fw-bold">{{ number_format($savingsCalculation['total_savings'], 2) }} ETB</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div><strong>Current Balance:</strong> {{ number_format($savingsCalculation['current_balance'], 2) }} ETB</div>
                                        <div><strong>Final Payment:</strong> <span class="text-success fw-bold">{{ number_format($savingsCalculation['final_closing_amount'], 2) }} ETB</span></div>
                                        <div><strong>Amount Saved:</strong> 
                                            @if($savingsCalculation['total_savings'] >= 0)
                                                <span class="text-success fw-bold">+{{ number_format($savingsCalculation['total_savings'], 2) }} ETB</span>
                                            @else
                                                <span class="text-danger fw-bold">{{ number_format($savingsCalculation['total_savings'], 2) }} ETB</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <div class="d-flex flex-column flex-sm-row gap-2 w-100">
                        <button type="button" class="btn btn-secondary" wire:click="cancelConfirmation" wire:loading.attr="disabled">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-success" wire:click="store" wire:loading.attr="disabled" wire:target="store">
                            <span wire:loading.remove wire:target="store"><i class="fas fa-check me-1"></i> Yes, Record Payment</span>
                            <span wire:loading wire:target="store"><i class="fas fa-spinner fa-spin me-1"></i> Processing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Closing Prices Modal -->
    @if($showClosingPricesModal)
    <div class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calculator text-primary me-2"></i>
                        Close Credit with Final Prices
                    </h5>
                </div>
                <div class="modal-body">
                    <!-- Show validation errors within modal -->
                    @if ($errors->any())
                        @php
                            $closingPriceErrors = array_filter($errors->all(), function($error) {
                                return strpos($error, 'closingPrices') !== false;
                            });
                        @endphp
                        @if(count($closingPriceErrors) > 0)
                            <div class="alert alert-danger mb-3">
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($closingPriceErrors as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Do you want to close this credit?</strong> Enter the final closing prices to calculate savings and complete the payment.
                    </div>
                    
                    @if($credit->reference_type === 'purchase' && $credit->purchase)
                        
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th class="fw-semibold">Item</th>
                                        <th class="fw-semibold text-end">Original Cost</th>
                                        <th class="fw-semibold text-center">Closing Price</th>
                                        <th class="fw-semibold text-end">Saving</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($credit->purchase->items as $item)
                                        @php
                                            $unitQuantity = $item->item->unit_quantity ?: 1;
                                            $originalCostPerLiter = $item->unit_cost / $unitQuantity;
                                            $closingPricePerLiter = isset($closingPrices[$item->item_id]) && is_numeric($closingPrices[$item->item_id]) 
                                                ? (float) $closingPrices[$item->item_id] 
                                                : $originalCostPerLiter;
                                            $saving = ($originalCostPerLiter - $closingPricePerLiter) * $unitQuantity * $item->quantity;
                                        @endphp
                                        <tr>
                                            <td class="fw-medium">{{ $item->item->name }}</td>
                                            <td class="text-end">{{ number_format($originalCostPerLiter, 2) }} ETB</td>
                                            <td class="text-center">
                                                <input type="number" 
                                                    wire:model.live="closingPrices.{{ $item->item_id }}" 
                                                    class="form-control form-control-sm text-center @error('closingPrices.'.$item->item_id) is-invalid @enderror" 
                                                    step="0.01" 
                                                    min="0" 
                                                    placeholder="{{ number_format($originalCostPerLiter, 2) }}"
                                                    style="width: 120px; margin: 0 auto;">
                                                @error('closingPrices.'.$item->item_id)
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            <td class="text-end">
                                                @if(isset($closingPrices[$item->item_id]) && is_numeric($closingPrices[$item->item_id]))
                                                    <span class="{{ $saving >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                                        {{ $saving >= 0 ? '+' : '' }}{{ number_format($saving, 2) }} ETB
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-top">
                                    <tr>
                                        <th colspan="3" class="text-end">Total Saving:</th>
                                        <th class="text-end">
                                            @php
                                                $totalSaving = 0;
                                                $totalClosingCost = 0;
                                                foreach($credit->purchase->items as $item) {
                                                    if(isset($closingPrices[$item->item_id]) && is_numeric($closingPrices[$item->item_id])) {
                                                        $unitQuantity = $item->item->unit_quantity ?: 1;
                                                        $originalCostPerLiter = $item->unit_cost / $unitQuantity;
                                                        $closingPricePerLiter = (float) $closingPrices[$item->item_id];
                                                        $totalSaving += ($originalCostPerLiter - $closingPricePerLiter) * $unitQuantity * $item->quantity;
                                                        $totalClosingCost += $closingPricePerLiter * $unitQuantity * $item->quantity;
                                                    }
                                                }
                                                $finalPaymentAmount = max(0, $totalClosingCost - $credit->paid_amount);
                                            @endphp
                                            <span class="{{ $totalSaving >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                                {{ $totalSaving >= 0 ? '+' : '' }}{{ number_format($totalSaving, 2) }} ETB
                                            </span>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end">Total Closing Cost:</th>
                                        <th class="text-end">
                                            <span class="fw-bold">{{ number_format($totalClosingCost, 2) }} ETB</span>
                                        </th>
                                    </tr>
                                    <tr class="table-success">
                                        <th colspan="3" class="text-end">Final Payment Amount:</th>
                                        <th class="text-end">
                                            <span class="fw-bold text-success">{{ number_format($finalPaymentAmount, 2) }} ETB</span>
                                            <br><small class="text-muted">(Closing Cost - Paid: {{ number_format($credit->paid_amount, 2) }} ETB)</small>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <div class="d-flex gap-2 w-100">
                        <button type="button" class="btn btn-secondary" wire:click="cancelClosingPrices">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-success" wire:click="processClosingPayment" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="processClosingPayment">
                                <i class="fas fa-check me-1"></i> Close Credit
                            </span>
                            <span wire:loading wire:target="processClosingPayment">
                                <i class="fas fa-spinner fa-spin me-1"></i> Processing...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
