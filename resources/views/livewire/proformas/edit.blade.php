<div>
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 border rounded">
        <h1 class="h3 mb-0">Edit Proforma</h1>
        <a href="{{ route('admin.proformas.show', $proforma) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Details
        </a>
    </div>

    <form wire:submit.prevent="save">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Proforma Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Customer *</label>
                            <select wire:model="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea wire:model="notes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Add Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <select wire:model="selectedItem" class="form-select @error('selectedItem') is-invalid @enderror">
                                    <option value="">Select Item</option>
                                    @foreach($availableItems as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedItem')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-2">
                                <input type="number" wire:model="quantity" class="form-control @error('quantity') is-invalid @enderror" placeholder="Qty" step="0.01" min="0.01">
                                @error('quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <input type="number" wire:model="unit_price" class="form-control @error('unit_price') is-invalid @enderror" placeholder="Unit Price" step="0.01" min="0">
                                @error('unit_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <button type="button" wire:click="addItem" class="btn btn-primary w-100">
                                    <i class="bi bi-plus"></i> Add Item
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Items List</h5>
                    </div>
                    <div class="card-body">
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
                                    <tfoot>
                                        <tr class="table-dark">
                                            <th colspan="3">Total Amount</th>
                                            <th colspan="2">{{ number_format(collect($items)->sum('total'), 2) }} ETB</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted">No items added yet. Add items using the form above.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Items Count:</span>
                            <span>{{ count($items) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span><strong>Total Amount:</strong></span>
                            <span><strong>{{ number_format(collect($items)->sum('total'), 2) }} ETB</strong></span>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-floppy"></i> Update Proforma
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>