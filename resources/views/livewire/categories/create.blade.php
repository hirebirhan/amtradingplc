<div>
    <x-partials.main title="Create Category">
        <div class="card-header p-2 p-md-4 border-bottom">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                <div class="d-flex align-items-center">
                    <div class="rounded bg-primary bg-opacity-10 p-2 me-2 d-flex align-items-center justify-content-center d-none d-md-flex" style="width: 42px; height: 42px">
                        <i class="bi bi-plus-circle text-primary"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1 h6">Add New Category</h5>
                        <p class="text-muted small mb-0 d-none d-md-block">Create a new category for organizing your inventory items</p>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="cancel">
                    <i class="bi bi-arrow-left me-1"></i> 
                    <span class="d-none d-sm-inline">Back to List</span>
                    <span class="d-sm-none">Back</span>
                </button>
            </div>
        </div>

        <livewire:categories.form :isEdit="false" />
    </x-partials.main>
</div>
