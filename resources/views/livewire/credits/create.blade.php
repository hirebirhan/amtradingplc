<div>
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-2 border-bottom">
            <div>
                <h5 class="card-title mb-0 d-none d-md-block">Create New Credit</h5>
                <h6 class="card-title mb-0 d-md-none">Create Credit</h6>
            </div>
            <a href="{{ route('admin.credits.index') }}" class="btn btn-secondary btn-sm">
                <span class="d-none d-sm-inline"><i class="fas fa-arrow-left me-1"></i> Back to Credits</span>
                <span class="d-sm-none">← Back</span>
            </a>
        </div>

        <div class="card-body p-3">
            <form wire:submit.prevent="save">
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-6 mb-2">
                        <div class="form-group">
                            <label for="credit_type" class="form-label fw-medium small">Credit Type</label>
                            <select wire:model.live="form.credit_type" id="credit_type" class="form-select form-select-sm" required>
                                <option value="">Select Type</option>
                                <option value="receivable">Receivable</option>
                                <option value="payable">Payable</option>
                            </select>
                            @error('form.credit_type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-2">
                        <div class="form-group">
                            <label for="reference_no" class="form-label fw-medium small">Reference Number</label>
                            <input type="text" wire:model="form.reference_no" id="reference_no" class="form-control form-control-sm" required>
                            @error('form.reference_no') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <!-- Customer/Supplier Selection -->
                    <div class="col-12 col-md-6 mb-2">
                        <div class="form-group">
                            <label for="party_id" class="form-label fw-medium small">
                                {{ $form['credit_type'] === 'receivable' ? 'Customer' : 'Supplier' }}
                            </label>
                            <select wire:model="form.party_id" id="party_id" class="form-select form-select-sm" required>
                                <option value="">Select {{ $form['credit_type'] === 'receivable' ? 'Customer' : 'Supplier' }}</option>
                                @if($form['credit_type'] === 'receivable')
                                    @foreach($this->customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                @else
                                    @foreach($this->suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('form.party_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-2">
                        <div class="form-group">
                            <label for="amount" class="form-label fw-medium small">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" wire:model="form.amount" id="amount" class="form-control form-control-sm" min="0" step="0.01" required>
                            </div>
                            @error('form.amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-2">
                        <div class="form-group">
                            <label for="credit_date" class="form-label fw-medium small">Credit Date</label>
                            <input type="date" wire:model="form.credit_date" id="credit_date" class="form-control form-control-sm" required>
                            @error('form.credit_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-2">
                        <div class="form-group">
                            <label for="due_date" class="form-label fw-medium small">Due Date</label>
                            <input type="date" wire:model="form.due_date" id="due_date" class="form-control form-control-sm" required>
                            @error('form.due_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-2">
                        <div class="form-group">
                            <label for="reference_type" class="form-label fw-medium small">Reference Type</label>
                            <select wire:model="form.reference_type" id="reference_type" class="form-select form-select-sm">
                                <option value="">Select Type</option>
                                <option value="sale">Sale</option>
                                <option value="purchase">Purchase</option>
                                <option value="manual">Manual</option>
                            </select>
                            @error('form.reference_type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-2">
                        <div class="form-group">
                            <label for="reference_id" class="form-label fw-medium small">Reference ID</label>
                            <input type="text" wire:model="form.reference_id" id="reference_id" class="form-control form-control-sm">
                            @error('form.reference_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12 mb-2">
                        <div class="form-group">
                            <label for="description" class="form-label fw-medium small">Description</label>
                            <textarea wire:model="form.description" id="description" class="form-control form-control-sm" rows="2"></textarea>
                            @error('form.description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <!-- Mobile-First Footer -->
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                    <button type="button" wire:click="cancel" class="btn btn-secondary btn-sm order-2 order-sm-1">
                        <span class="d-none d-sm-inline"><i class="fas fa-times me-1"></i> Cancel</span>
                        <span class="d-sm-none">❌ Cancel</span>
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm order-1 order-sm-2">
                        <span class="d-none d-sm-inline"><i class="fas fa-save me-1"></i> Save Credit</span>
                        <span class="d-sm-none">💾 Save</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
