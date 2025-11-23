<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h5 class="card-title mb-0">Create New Expense</h5>
            <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Expenses
            </a>
        </div>

        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="reference_no" class="form-label">Reference Number</label>
                            <input type="text" wire:model="form.reference_no" id="reference_no" class="form-control" readonly>
                            <small class="text-muted">Auto-generated reference number</small>
                            @error('form.reference_no') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="expense_type_id" class="form-label">Expense Type <span class="text-danger">*</span></label>
                            <select wire:model="form.expense_type_id" id="expense_type_id" class="form-select @error('form.expense_type_id') is-invalid @enderror" required>
                                <option value="">-- Select Expense Type --</option>
                                @foreach($expenseTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @error('form.expense_type_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            <div class="d-flex justify-content-end mt-1">
                                <a href="{{ route('admin.settings.expense-types') }}" target="_blank" class="text-primary small">
                                    <i class="fas fa-plus-circle"></i> Manage Expense Types
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="amount" class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" wire:model="form.amount" id="amount" class="form-control" min="0" step="0.01" required>
                            </div>
                            @error('form.amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select wire:model="form.payment_method" id="payment_method" class="form-select" required>
                                <option value="">Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                                <option value="credit_card">Credit Card</option>
                            </select>
                            @error('form.payment_method') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="expense_date" class="form-label">Expense Date</label>
                            <div wire:ignore>
                                <div class="input-group">
                                    <input type="text" id="expense_date" class="form-control datepicker" placeholder="YYYY-MM-DD" autocomplete="off">
                                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                </div>
                            </div>
                            @error('form.expense_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea wire:model="form.description" id="description" class="form-control" rows="3"></textarea>
                            @error('form.description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input type="checkbox" wire:model="form.is_recurring" id="is_recurring" class="form-check-input">
                            <label for="is_recurring" class="form-check-label">This is a recurring expense</label>
                        </div>
                    </div>

                    @if($form['is_recurring'])
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="recurring_frequency" class="form-label">Recurring Frequency</label>
                                <select wire:model="form.recurring_frequency" id="recurring_frequency" class="form-select" required>
                                    <option value="">Select Frequency</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                                @error('form.recurring_frequency') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="recurring_end_date" class="form-label">End Date</label>
                                <div wire:ignore>
                                    <div class="input-group">
                                        <input type="text" id="recurring_end_date" class="form-control datepicker" placeholder="YYYY-MM-DD" autocomplete="off">
                                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                    </div>
                                </div>
                                @error('form.recurring_end_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    @endif
                </div>

                <div class="d-flex justify-content-end">
                    <button type="button" wire:click="cancel" class="btn btn-secondary me-2">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', function () {
        initDatepickers();
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        initDatepickers();
    });
    
    function initDatepickers() {
        // Initialize expense date picker
        $('#expense_date').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true,
            todayBtn: 'linked',
            clearBtn: true
        }).on('changeDate', function(e) {
            @this.set('form.expense_date', $(this).val());
        });
        
        // Initialize recurring end date picker if it exists
        if ($('#recurring_end_date').length) {
            $('#recurring_end_date').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true,
                todayBtn: 'linked',
                clearBtn: true
            }).on('changeDate', function(e) {
                @this.set('form.recurring_end_date', $(this).val());
            });
        }
        
        // Set initial values from Livewire model
        if (@this.form.expense_date) {
            $('#expense_date').datepicker('update', @this.form.expense_date);
        }
        
        if (@this.form.recurring_end_date) {
            $('#recurring_end_date').datepicker('update', @this.form.recurring_end_date);
        }
    }
    
    // Reinitialize datepickers when is_recurring changes
    document.addEventListener('livewire:update', function() {
        setTimeout(function() {
            initDatepickers();
        }, 200);
    });
</script>