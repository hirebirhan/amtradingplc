{{-- Import Items Page --}}
<div class="w-100">
    <div class="card shadow-sm border-0 w-100">
        <div class="card-header bg-white border-bottom p-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">
                    <i class="fas fa-upload me-2 text-muted"></i>
                    ***MODIFIED*** Import Items ***MODIFIED***
                </h5>
                <a href="{{ route('admin.items.import-template.download') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-download me-2"></i>
                    Download Template
                </a>
            </div>
        </div>

        <div class="card-body p-lg-4">
            <!-- Stepper -->
            <div class="steps mb-4">
                <a href="#" wire:click.prevent="cancelPreview" class="step-item @if(!$showPreview) active @endif">
                    <div class="step-marker">1</div>
                    <div class="step-details">
                        <div class="step-title">Upload File</div>
                    </div>
                </a>
                <div class="step-item @if($showPreview) active @endif">
                    <div class="step-marker">2</div>
                    <div class="step-details">
                        <div class="step-title">Preview & Import</div>
                    </div>
                </div>
            </div>

            <!-- Step 1: Upload File -->
            <div class="@if($showPreview) d-none @endif">
                <div class="text-center p-4 mb-4 border-2 border-dashed rounded-3">
                    <div class="icon-xl bg-light text-primary rounded-circle mx-auto mb-3">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <h5 class="fw-semibold">Upload Your Excel File</h5>
                    <p class="text-muted small">Download the template, fill it, and upload it here.</p>
                    
                    <input type="file" wire:model="importFile" id="importFile" class="d-none" accept=".xlsx,.xls">
                    
                    <label for="importFile" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>
                        Choose File
                    </label>

                    @error('importFile')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>

                @if($importFile && !$errors->has('importFile'))
                    <div class="alert alert-light-success border-2 d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle me-3"></i>
                            <div>
                                <span class="fw-medium">{{ $importFile->getClientOriginalName() }}</span>
                                <small class="text-muted d-block">({{ number_format($importFile->getSize() / 1024, 1) }} KB)</small>
                            </div>
                        </div>
                        <button type="button" wire:click="clearFile" class="btn-close" title="Remove file"></button>
                    </div>
                @endif

                <div class="row align-items-end">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="defaultCategory" class="form-label fw-medium">Default Category</label>
                            <select wire:model="defaultCategory" class="form-select" id="defaultCategory">
                                <option value="">No default category</option>
                                @foreach($categories as $id => $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">This will be used if the category column in your file is empty.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-grid">
                            <button type="button" wire:click="previewImport" wire:loading.attr="disabled" class="btn btn-dark" @if(!$importFile) disabled @endif>
                                <span wire:loading.remove wire:target="previewImport">
                                    <i class="fas fa-eye me-2"></i>Preview Data
                                </span>
                                <span wire:loading wire:target="previewImport">Analyzing...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Step 2: Preview & Import -->
            @if($showPreview && count($previewData) > 0)
                <div class="preview-section w-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-semibold mb-0">Preview Data</h5>
                            <p class="text-muted mb-0 small">Review your data before importing</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" wire:click.prevent="cancelPreview" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Back
                            </button>
                            <button type="button" wire:click.prevent="importItems" wire:loading.attr="disabled" class="btn btn-success" @if(count($previewErrors) > 0) disabled @endif>
                                <span wire:loading.remove wire:target="importItems">
                                    <i class="fas fa-save me-2"></i>Import Items
                                </span>
                                <span wire:loading wire:target="importItems">Importing...</span>
                            </button>
                        </div>
                    </div>

                    @if(count($previewErrors) > 0)
                        <div class="alert alert-light-danger border-2 d-flex mb-4">
                            <i class="fas fa-exclamation-triangle me-3 mt-1"></i>
                            <div>
                                <h6 class="fw-semibold">{{ count($previewErrors) }} Item(s) Have Errors</h6>
                                <p class="mb-0 small">Please review the highlighted rows in the table below. The import button will be enabled once all errors are resolved.</p>
                            </div>
                        </div>
                    @endif

                    <!-- Main Items Table -->
                    <div class="card mb-4 border">
                        <div class="card-header bg-light">
                            <h6 class="card-title fw-bold mb-0">Item Details</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-striped w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Category</th>
                                        <th class="text-end">U.COST (Cost)</th>
                                        <th class="text-end">U.COST (Selling)</th>
                                        <th>U.M</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($previewData as $index => $item)
                                        <tr class="@if(!$item['is_valid']) table-danger-light @endif"
                                            @if(!$item['is_valid'])
                                                tabindex="0"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="top"
                                                title="{{ implode(', ', $item['errors']) }}"
                                            @endif
                                        >
                                            <td class="fw-medium text-muted">{{ $item['row'] }}</td>
                                            <td>{{ $item['name'] ?? '' }}</td>
                                            <td>{{ $item['sku'] ?? '' }}</td>
                                            <td>
                                                <select wire:model.lazy="previewData.{{ $index }}.category_id" class="form-select form-select-sm">
                                                    <option value="">Select Category</option>
                                                    @foreach($categories as $id => $name)
                                                        <option value="{{ $id }}">{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="text-end">{{ number_format($item['cost_price'] ?? 0, 2) }}</td>
                                            <td class="text-end">{{ number_format($item['selling_price'] ?? 0, 2) }}</td>
                                            <td>{{ $item['unit'] ?? 'pcs' }}</td>
                                            <td>
                                                @if($item['is_valid'])
                                                    <span class="badge bg-light-success text-success">Ready</span>
                                                @else
                                                    <span class="badge bg-light-danger text-danger">Error</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Branch Quantities Table -->
                    <div class="card mb-4 border">
                        <div class="card-header bg-light">
                            <h6 class="card-title fw-bold mb-0">Quantities Per Branch</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter w-100">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        @foreach($warehouses as $warehouse)
                                            <th class="text-center">{{ $warehouse->name }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($previewData as $item)
                                        @if($item['is_valid'])
                                            <tr>
                                                <td class="fw-medium">{{ $item['name'] }}</td>
                                                @foreach($warehouses as $warehouse)
                                                    <td class="text-center">
                                                        <input type="number" 
                                                               wire:model.lazy="branchQuantities.{{ $item['row'] }}.{{ $warehouse->id }}" 
                                                               class="form-control form-control-sm" 
                                                               style="width: 100px; margin: auto;"
                                                               min="0">
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Next Steps Section in its own row -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="card-title fw-bold mb-0">Next Steps</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Review all items in the table above</p>
                                            <p class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Verify category selections for each item</p>
                                            <p class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Set initial quantities for each branch if needed</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-2"><i class="fas fa-arrow-right text-primary me-2"></i> Click <strong>Import Items</strong> to save all valid items</p>
                                            <p class="mb-2"><i class="fas fa-arrow-right text-primary me-2"></i> Or click <strong>Back</strong> to select a different file</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($allItemsExist)
                <div class="text-center p-5">
                    <div class="icon-xl bg-light-info text-info rounded-circle mx-auto mb-3">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4 class="fw-semibold">All Items Already Exist</h4>
                    <p class="text-muted">
                        Good news! All {{ count($previewData) }} items in your file already exist in the system.
                    </p>
                    <div class="d-flex justify-content-center gap-2 mt-4">
                        <button type="button" wire:click="clearFile" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload a Different File
                        </button>
                        <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i>View All Items
                        </a>
                    </div>
                </div>
            @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .steps {
        display: flex;
        border-bottom: 2px solid #e9ecef;
    }
    .step-item {
        display: flex;
        align-items: center;
        padding: 1rem 1.5rem;
        color: #6c757d;
        text-decoration: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: all 0.15s ease-in-out;
    }
    .step-item.active {
        color: var(--bs-primary);
        border-bottom-color: var(--bs-primary);
    }
    .step-item .step-marker {
        width: 2rem;
        height: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: #e9ecef;
        color: #6c757d;
        font-weight: 600;
        margin-right: 1rem;
        transition: all 0.15s ease-in-out;
    }
    .step-item.active .step-marker {
        background-color: var(--bs-primary);
        color: white;
    }
    .step-item .step-title {
        font-weight: 600;
    }
    .border-dashed {
        border-style: dashed !important;
    }
    .alert-light-success {
        background-color: #e6f7f0;
        border-color: #a6d9c1 !important;
        color: #0d6b42;
    }
    .alert-light-danger {
        background-color: #fdeaea;
        border-color: #f5c0c0 !important;
        color: #842029;
    }
    .alert-light-info {
        background-color: #e7f5fd;
        border-color: #b8e0f9 !important;
        color: #0c5460;
    }
    .table-danger-light {
        --bs-table-bg: #fdeaea;
        --bs-table-striped-bg: #f6e1e1;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Re-initialize tooltips after Livewire updates
        Livewire.hook('message.processed', (message, component) => {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
        
        // Handle successful import and redirect
        Livewire.on('itemsImportedSuccessfully', () => {
            // Wait a moment for the notification to be visible
            setTimeout(() => {
                window.location.href = '{{ route("admin.items.index") }}';
            }, 1500);
        });
    });
</script>
@endpush
</div>