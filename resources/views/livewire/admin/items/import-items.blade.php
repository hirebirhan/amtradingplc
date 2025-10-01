<div>
    <div class="container-fluid px-0">
        <div class="row g-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-body border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Import Items</h5>
                        <a href="{{ route('admin.items.import-template.download') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-download me-1"></i> Download Template
                        </a>
                    </div>
                    <div class="card-body">
                        @if (session()->has('success'))
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                {{ session('success') }}
                            </div>
                        @endif
                        @if (session()->has('info'))
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                {{ session('info') }}
                            </div>
                        @endif
                        @if (session()->has('error') || $errors->any())
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                {{ session('error') ?? $errors->first() }}
                            </div>
                        @endif

                        <form wire:submit.prevent="previewUpload" class="mb-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-6">
                                    <label for="file" class="form-label fw-medium">Upload Excel file (.xlsx)</label>
                                    <input type="file" wire:model="file" id="file" class="form-control" accept=".xlsx,.xls">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="default_category_id" class="form-label fw-medium">Default Category</label>
                                    <select wire:model="default_category_id" id="default_category_id" class="form-select">
                                        <option value="">— None —</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search me-1"></i> Preview
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if ($preview)
                            <div class="mb-3">
                                <h6 class="fw-semibold mb-2">File Preview</h6>
                                <div class="small text-secondary mb-2">
                                    Sheet: <span class="fw-medium">{{ $preview['sheetTitle'] }}</span> • Rows detected: <span class="fw-medium">{{ $preview['rowCount'] }}</span>
                                </div>
                            </div>

                            <form wire:submit.prevent="applyImport" class="mt-4">
                                @php
                                    $allItems = $preview['allItems'] ?? [];
                                @endphp

                                @if(!empty($allItems))
                                    <div class="card border mb-4">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Items to Import ({{ count($allItems) }})</h6>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-check-circle me-1"></i> Apply Import
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted small">
                                                The following items will be created or updated. Review the data before proceeding.
                                                The selected default category will be applied to all items.
                                            </p>

                                            <div class="accordion" id="itemsAccordion">
                                                @foreach($allItems as $index => $item)
                                                    @php
                                                        $itemJson = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                                        $branchQuantities = $item['branches'] ?? [];
                                                    @endphp
                                                    
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="heading{{ $index }}">
                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" aria-expanded="false" aria-controls="collapse{{ $index }}">
                                                                <span class="fw-bold me-2">{{ $item['name'] }}</span>
                                                                <small class="text-muted">(SKU: {{ $item['sku'] }})</small>
                                                            </button>
                                                        </h2>
                                                        <div id="collapse{{ $index }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $index }}" data-bs-parent="#itemsAccordion">
                                                            <div class="accordion-body">
                                                                <div class="row">
                                                                    <div class="col-md-8">
                                                                        <h6 class="fw-semibold">Item JSON Data</h6>
                                                                        <pre><code class="language-json">{{ $itemJson }}</code></pre>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <h6 class="fw-semibold">Branch Quantities</h6>
                                                                        @if(!empty($branchQuantities))
                                                                            <ul class="list-group">
                                                                                @foreach($branchQuantities as $branch => $qty)
                                                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                        {{ ucfirst($branch) }}
                                                                                        <span class="badge bg-primary rounded-pill">{{ $qty }}</span>
                                                                                    </li>
                                                                                @endforeach
                                                                            </ul>
                                                                        @else
                                                                            <p class="text-muted small">No branch quantities specified.</p>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="card-footer bg-light text-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-check-circle me-1"></i> Apply Import
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        No items were detected in the uploaded file.
                                    </div>
                                @endif
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
