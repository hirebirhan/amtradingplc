<div>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Create Expense Type</h5>
                <a href="{{ route('admin.settings.expense-types') }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to List
                </a>
            </div>
            
            <div class="card-body">
                <form wire:submit.prevent="store">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" wire:model="name" id="name" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea wire:model="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3"></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="branch_id" class="form-label">Branch</label>
                        <select wire:model="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted">Leave empty if this expense type applies to all branches</div>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" wire:model="is_active" id="is_active" class="form-check-input">
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" wire:click="cancel" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> 