<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create New Proforma</h1>
        <a href="{{ route('admin.proformas.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Proformas
        </a>
    </div>

    <form wire:submit.prevent="save">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Customer *</label>
                                    <div class="input-group">
                                        <select wire:model="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                                            <option value="">Select Customer</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-outline-primary">Add</button>
                                    </div>
                                    @error('customer_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Proforma Date *</label>
                                    <input type="date" wire:model="proforma_date" class="form-control @error('proforma_date') is-invalid @enderror" value="{{ date('Y-m-d') }}">
                                    @error('proforma_date')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Valid Until (Optional)</label>
                                    <input type="date" wire:model="valid_until" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contact Person (Optional)</label>
                                    <input type="text" wire:model="contact_person" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contact Email (Optional)</label>
                                    <input type="email" wire:model="contact_email" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contact Phone (Optional)</label>
                                    <input type="text" wire:model="contact_phone" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Search Item</label>
                                <select wire:model="selectedItem" class="form-select @error('selectedItem') is-invalid @enderror">
                                    <option value="">Select Item</option>
                                    @foreach($availableItems as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedItem')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Quantity</label>
                                <input type="number" wire:model="quantity" class="form-control @error('quantity') is-invalid @enderror" step="0.01" min="0.01">
                                @error('quantity')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Unit Price</label>
                                <input type="number" wire:model="unit_price" class="form-control @error('unit_price') is-invalid @enderror" step="0.01" min="0">
                                @error('unit_price')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" wire:click="addItem" class="btn btn-primary w-100">
                                    <i class="bi bi-plus"></i> Add Item
                                </button>
                            </div>
                        </div>

                        @if(count($items) > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $index => $item)
                                            <tr>
                                                <td>{{ $item['item_name'] }}</td>
                                                <td>{{ number_format($item['quantity'], 2) }}</td>
                                                <td>{{ number_format($item['unit_price'], 2) }} ETB</td>
                                                <td>{{ number_format($item['total'], 2) }} ETB</td>
                                                <td>
                                                    <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted">No items added yet. Search and add items above.</p>
                            </div>
                        @endif
                        
                        @error('items')
                            <div class="text-danger small mt-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea wire:model="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Additional notes..."></textarea>
                            @error('notes')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Items Count:</span>
                            <span>{{ count($items) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span><strong>Total Amount:</strong></span>
                            <span><strong>{{ number_format($subtotal, 2) }} ETB</strong></span>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-floppy"></i> Create Proforma
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>