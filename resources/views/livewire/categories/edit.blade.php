<div>
    <x-partials.main title="Edit Category: {{ $category->name }}">
        <div class="card shadow-sm border-0">
            <div class="card-header p-2 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px">
                        <i class="fas fa-folder-open text-primary"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">{{ $category->name }}</h5>
                        <p class="text-muted small mb-0">Last updated: {{ $category->updated_at->diffForHumans() }}</p>
                    </div>
                </div>
                <div class="d-flex gap-1">
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>

            <div class="card-body p-0">
                <livewire:categories.form :category="$category" :isEdit="true" />
            </div>
        </div>
    </x-partials.main>
</div>
