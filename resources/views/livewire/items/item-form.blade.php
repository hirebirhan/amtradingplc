<div>
    <div class="card border-0 shadow-theme hover-lift">
        <div class="card-header bg-theme-secondary border-bottom border-theme">
            <h3 class="card-title text-theme-primary mb-0">{{ $isEdit ? 'Edit Item' : 'Create New Item' }}</h3>
        </div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-medium text-theme-primary">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control focus-ring @error('item.name') is-invalid @enderror"
                                   id="name" wire:model="item.name">
                            @error('item.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="sku" class="form-label fw-medium text-theme-primary">SKU <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-theme-secondary text-theme-secondary">{{ config('app.sku_prefix', 'CODE-') }}</span>
                                <input type="text" class="form-control focus-ring @error('item.sku') is-invalid @enderror"
                                       id="sku" wire:model="item.sku" placeholder="Enter SKU without prefix">
                            </div>
                            @error('item.sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text text-theme-secondary">The prefix "{{ config('app.sku_prefix', 'CODE-') }}" will be automatically added for display</div>
                        </div>

                        <div class="mb-3">
                            <label for="barcode" class="form-label fw-medium text-theme-primary">Barcode</label>
                            <input type="text" class="form-control focus-ring @error('item.barcode') is-invalid @enderror"
                                   id="barcode" wire:model="item.barcode">
                            @error('item.barcode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label fw-medium text-theme-primary">Category <span class="text-danger">*</span></label>
                            <select class="form-select focus-ring @error('item.category_id') is-invalid @enderror"
                                    id="category_id" wire:model="item.category_id">
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('item.category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="cost_price" class="form-label fw-medium text-theme-primary">Cost Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control focus-ring @error('item.cost_price') is-invalid @enderror"
                                   id="cost_price" wire:model="item.cost_price">
                            @error('item.cost_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="selling_price" class="form-label fw-medium text-theme-primary">Selling Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control focus-ring @error('item.selling_price') is-invalid @enderror"
                                   id="selling_price" wire:model="item.selling_price">
                            @error('item.selling_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="reorder_level" class="form-label fw-medium text-theme-primary">Reorder Level <span class="text-danger">*</span></label>
                            <input type="number" class="form-control focus-ring @error('item.reorder_level') is-invalid @enderror"
                                   id="reorder_level" wire:model="item.reorder_level">
                            @error('item.reorder_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="unit" class="form-label fw-medium text-theme-primary">Unit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control focus-ring @error('item.unit') is-invalid @enderror"
                                   id="unit" wire:model="item.unit">
                            @error('item.unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="brand" class="form-label fw-medium text-theme-primary">Brand</label>
                            <input type="text" class="form-control focus-ring @error('item.brand') is-invalid @enderror"
                                   id="brand" wire:model="item.brand">
                            @error('item.brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label fw-medium text-theme-primary">Image</label>
                            <input type="file" class="form-control focus-ring @error('image') is-invalid @enderror"
                                   id="image" wire:model="image">
                            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @if($item->image_path)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $item->image_path) }}" alt="Item Image" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="description" class="form-label fw-medium text-theme-primary">Description</label>
                            <textarea class="form-control focus-ring @error('item.description') is-invalid @enderror"
                                      id="description" wire:model="item.description" rows="3"></textarea>
                            @error('item.description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input focus-ring" type="checkbox" id="is_active" wire:model="item.is_active">
                                <label class="form-check-label text-theme-primary" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.items.index') }}" class="btn btn-secondary hover-lift">Cancel</a>
                    <button type="submit" class="btn btn-primary hover-lift">
                        {{ $isEdit ? 'Update' : 'Create' }} Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
