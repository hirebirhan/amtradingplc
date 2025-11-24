<div wire:ignore.self class="modal fade" id="createItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form wire:submit.prevent="save">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Item Name *</label>
                            <input type="text" wire:model="form.name" class="form-control @error('form.name') is-invalid @enderror">
                            @error('form.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category *</label>
                            <select wire:model="form.category_id" class="form-select @error('form.category_id') is-invalid @enderror">
                                <option value="">Select category...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('form.category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Items per Piece *</label>
                            <input type="number" wire:model="form.unit_quantity" class="form-control @error('form.unit_quantity') is-invalid @enderror" min="1">
                            @error('form.unit_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Item Unit *</label>
                            <select wire:model="form.item_unit" class="form-select @error('form.item_unit') is-invalid @enderror">
                                <option value="">Select unit...</option>
                                @foreach($itemUnits as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('form.item_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" wire:model="form.reorder_level" class="form-control @error('form.reorder_level') is-invalid @enderror" min="0">
                            @error('form.reorder_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea wire:model="form.description" class="form-control @error('form.description') is-invalid @enderror" rows="2"></textarea>
                            @error('form.description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        <!-- Pricing Section -->
                        <div class="col-12"><hr><h6>Pricing</h6></div>
                        <div class="col-md-6">
                            <label class="form-label">Cost Price per {{ $form['item_unit'] ?: 'item' }} *</label>
                            <div class="input-group">
                                <span class="input-group-text">ETB</span>
                                <input type="number" step="0.01" wire:model="form.cost_price_per_unit" class="form-control @error('form.cost_price_per_unit') is-invalid @enderror">
                            </div>
                            @error('form.cost_price_per_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selling Price per {{ $form['item_unit'] ?: 'item' }} *</label>
                            <div class="input-group">
                                <span class="input-group-text">ETB</span>
                                <input type="number" step="0.01" wire:model="form.selling_price_per_unit" class="form-control @error('form.selling_price_per_unit') is-invalid @enderror">
                            </div>
                            @error('form.selling_price_per_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Total Cost per Piece</label>
                            <div class="input-group">
                                <span class="input-group-text">ETB</span>
                                <input type="text" class="form-control" value="{{ number_format(((float)($form['cost_price_per_unit'] ?? 0)) * ((int)($form['unit_quantity'] ?? 1)), 2) }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Total Selling per Piece</label>
                            <div class="input-group">
                                <span class="input-group-text">ETB</span>
                                <input type="text" class="form-control" value="{{ number_format(((float)($form['selling_price_per_unit'] ?? 0)) * ((int)($form['unit_quantity'] ?? 1)), 2) }}" readonly>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" wire:click="save" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Create Item</span>
                    <span wire:loading>Creating...</span>
                </button>
            </div>
        </div>
    </div>
</div>