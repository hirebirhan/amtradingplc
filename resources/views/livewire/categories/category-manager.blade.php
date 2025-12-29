<div class="container-fluid">
    <!-- Branch Context Header -->
    @if($branch)
    <div class="branch-context bg-primary text-white p-2 mb-4 rounded">
        <i class="fas fa-building"></i> {{ $branch->name }}
        <span class="badge bg-light text-dark ms-2">{{ $categories->count() }} Categories</span>
    </div>
    @endif

    <!-- View Toggle -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" wire:model.live="showDeleted" value="false" id="active">
                <label class="btn btn-outline-primary" for="active">Active Categories</label>
                
                <input type="radio" class="btn-check" wire:model.live="showDeleted" value="true" id="deleted">
                <label class="btn btn-outline-secondary" for="deleted">Deleted Categories</label>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Category Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>{{ $editingId ? 'Edit' : 'Add' }} Category</h5>
                </div>
                <div class="card-body">
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" wire:model="name" required>
                            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Code *</label>
                            <input type="text" class="form-control" wire:model="code" required maxlength="15">
                            @error('code') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" wire:model="description" rows="3"></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                {{ $editingId ? 'Update' : 'Create' }} Category
                            </button>
                            @if($editingId)
                            <button type="button" class="btn btn-secondary" wire:click="$set('editingId', null)">
                                Cancel
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Categories List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Categories</h5>
                </div>
                <div class="card-body">
                    @if($categories->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                <tr class="{{ $category->trashed() ? 'table-secondary' : '' }}">
                                    <td>{{ $category->name }}</td>
                                    <td><code>{{ $category->code }}</code></td>
                                    <td>
                                        @if($category->trashed())
                                            <span class="badge bg-danger">Deleted</span>
                                        @elseif($category->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-warning">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($category->trashed())
                                            <button wire:click="restore({{ $category->id }})" class="btn btn-success btn-sm">
                                                <i class="fas fa-undo"></i> Restore
                                            </button>
                                        @else
                                            <button wire:click="edit({{ $category->id }})" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button wire:click="delete({{ $category->id }})" class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No categories found</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>