<div>
    <!-- Main Card Container -->
    <div class="card border-0 shadow-sm">
        <!-- Header -->
        <div class="card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Edit Item</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary mb-0 small">{{ $item->name }}</span>
                    </div>
                </div>
                <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Back to Items</span>
                </a>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body p-0">
            <!-- Error Alert -->
            @if($errors->any())
                <div class="p-4 pb-0">
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
                </div>
            @endif

            <!-- Form Content -->
            <form id="itemEditForm" wire:submit="save" class="p-4 pt-0">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-medium">
                            Item Name <span class="text-primary">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('form.name') is-invalid @enderror" 
                               id="name" 
                               wire:model="form.name">
                        @error('form.name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="category_id" class="form-label fw-medium">
                            Category <span class="text-primary">*</span>
                        </label>
                        <select class="form-select @error('form.category_id') is-invalid @enderror" 
                                id="category_id" 
                                wire:model="form.category_id">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('form.category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <label for="unit_quantity" class="form-label fw-medium">
                            Items per Piece <span class="text-primary">*</span>
                        </label>
                        <input type="number" 
                               class="form-control @error('form.unit_quantity') is-invalid @enderror" 
                               id="unit_quantity" 
                               wire:model="form.unit_quantity" 
                               min="1" 
                               placeholder="1">
                        @error('form.unit_quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Number of items in one piece</small>
                    </div>
                    <div class="col-md-4">
                        <label for="item_unit" class="form-label fw-medium">
                            Item Unit <span class="text-primary">*</span>
                        </label>
                        <select class="form-select @error('form.item_unit') is-invalid @enderror" 
                                id="item_unit" 
                                wire:model="form.item_unit">
                            <option value="">Select unit...</option>
                            @foreach($itemUnits as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('form.item_unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="reorder_level" class="form-label fw-medium">Reorder Level</label>
                        <input type="number" 
                               class="form-control @error('form.reorder_level') is-invalid @enderror" 
                               id="reorder_level" 
                               wire:model="form.reorder_level" 
                               min="0">
                        @error('form.reorder_level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Pricing Section -->
                <div class="row g-3 mt-4">
                    <div class="col-12">
                        <h6 class="fw-medium mb-3 text-secondary">Pricing</h6>
                    </div>

                    <!-- Cost Price per Unit -->
                    <div class="col-12 col-md-6">
                        <label for="cost_price_per_unit" class="form-label fw-medium">
                            Cost Price per {{ $form['item_unit'] ?: 'item' }} <span class="text-primary">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">ETB</span>
                            <input type="number" 
                                   step="0.01" 
                                   min="0"
                                   max="999999.99"
                                   class="form-control @error('form.cost_price_per_unit') is-invalid @enderror" 
                                   id="cost_price_per_unit" 
                                   wire:model.live.debounce.500ms="form.cost_price_per_unit"
                                   placeholder="0.00">
                        </div>
                        @error('form.cost_price_per_unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Selling Price per Unit -->
                    <div class="col-12 col-md-6">
                        <label for="selling_price_per_unit" class="form-label fw-medium">
                            Selling Price per {{ $form['item_unit'] ?: 'item' }} <span class="text-primary">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">ETB</span>
                            <input type="number" 
                                   step="0.01" 
                                   min="0"
                                   max="999999.99"
                                   class="form-control @error('form.selling_price_per_unit') is-invalid @enderror" 
                                   id="selling_price_per_unit" 
                                   wire:model.live.debounce.500ms="form.selling_price_per_unit"
                                   placeholder="0.00">
                        </div>
                        @error('form.selling_price_per_unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Total Cost Price (Calculated) -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-medium text-muted">Total Cost per Piece</label>
                        <div class="input-group">
                            <span class="input-group-text">ETB</span>
                            <input type="text" 
                                   class="form-control" 
                                   value="{{ number_format(((float)($form['cost_price_per_unit'] ?? 0)) * ((int)($form['unit_quantity'] ?? 1)), 2) }}"
                                   readonly>
                        </div>
                        <small class="text-muted">{{ $form['cost_price_per_unit'] ?? 0 }} × {{ $form['unit_quantity'] ?? 1 }} {{ $form['item_unit'] ?: 'items' }}</small>
                    </div>

                    <!-- Total Selling Price (Calculated) -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-medium text-muted">Total Selling per Piece</label>
                        <div class="input-group">
                            <span class="input-group-text">ETB</span>
                            <input type="text" 
                                   class="form-control" 
                                   value="{{ number_format(((float)($form['selling_price_per_unit'] ?? 0)) * ((int)($form['unit_quantity'] ?? 1)), 2) }}"
                                   readonly>
                        </div>
                        <small class="text-muted">{{ $form['selling_price_per_unit'] ?? 0 }} × {{ $form['unit_quantity'] ?? 1 }} {{ $form['item_unit'] ?: 'items' }}</small>
                    </div>
                </div>

                <div class="row g-3 mt-4">
                    <div class="col-12">
                        <label for="description" class="form-label fw-medium">Description</label>
                        <textarea class="form-control @error('form.description') is-invalid @enderror" 
                                  id="description" 
                                  wire:model="form.description" 
                                  rows="3" 
                                  placeholder="Brief description of the item..."></textarea>
                        @error('form.description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </form>
        </div>

        <!-- Card Footer -->
        <div class="card-footer border-top-0 px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary" wire:click="cancel">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <button type="submit" 
                        class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        form="itemEditForm">
                    <span wire:loading.remove wire:target="save">
                        <i class="bi bi-check-lg me-1"></i>Update Item
                    </span>
                    <span wire:loading wire:target="save">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                        Updating...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
