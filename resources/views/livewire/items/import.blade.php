{{-- Import Items Page --}}
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1 fw-semibold">
                                <i class="fas fa-upload me-2 text-muted"></i>
                                Import Items
                            </h5>
                            <small class="text-muted">
                                Default values: Status: Active | Cost Price: 0 | Selling Price: 0
                            </small>
                        </div>
                        <a href="{{ route('admin.items.import-template.download') }}" 
                           class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-download me-2"></i>
                            Download Template
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- File Upload and Defaults Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-header bg-white border-bottom">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-file-excel me-2 text-muted"></i>
                                        Step 1: Upload File & Set Defaults
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="importFile" class="form-label fw-medium">
                                                    <i class="fas fa-file-excel me-2 text-muted"></i>
                                                    Select Excel File
                                                </label>
                                                <div class="input-group">
                                                    <input type="file" 
                                                           wire:model="importFile" 
                                                           class="form-control @error('importFile') is-invalid @enderror"
                                                           accept=".xlsx,.xls"
                                                           id="importFile">
                                                    @if($importFile)
                                                        <button type="button" 
                                                                wire:click="clearFile" 
                                                                class="btn btn-outline-secondary"
                                                                title="Clear selected file">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                                @error('importFile')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            @if($importFile)
                                                <div class="alert alert-light border alert-dismissible fade show">
                                                    <i class="fas fa-check me-2 text-success"></i>
                                                    <strong>File selected:</strong> {{ $importFile->getClientOriginalName() }}
                                                    ({{ number_format($importFile->getSize() / 1024, 2) }} KB)
                                                    <button type="button" class="btn-close" wire:click="clearFile"></button>
                                                </div>
                                            @endif

                                            <button type="button" 
                                                    wire:click="previewImport" 
                                                    wire:loading.attr="disabled"
                                                    class="btn btn-primary"
                                                    @if(!$importFile) disabled @endif>
                                                <i class="fas fa-eye me-2"></i>
                                                <span wire:loading.remove wire:target="previewImport">Preview Data</span>
                                                <span wire:loading wire:target="previewImport">Loading...</span>
                                            </button>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="defaultCategory" class="form-label fw-medium">
                                                    <i class="fas fa-tags me-2 text-muted"></i>
                                                    Default Category
                                                </label>
                                                <select wire:model="defaultCategory" 
                                                        class="form-select @error('defaultCategory') is-invalid @enderror"
                                                        id="defaultCategory">
                                                    <option value="">Select Category</option>
                                                    @foreach($categories as $id => $name)
                                                        <option value="{{ $name }}">{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('defaultCategory')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Applied when Excel category is empty
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- All Items Already Exist Message -->
                    @if($allItemsExist && count($previewData) > 0)
                        <div class="mt-4">
                            <div class="alert alert-info border">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-info-circle me-3 mt-1 text-info"></i>
                                    <div class="flex-grow-1">
                                        <h6 class="alert-heading mb-2">
                                            <i class="fas fa-check-circle me-2"></i>
                                            All Items Already Exist
                                        </h6>
                                        <p class="mb-3">
                                            Great news! All {{ count($previewData) }} item{{ count($previewData) != 1 ? 's' : '' }} in your Excel file already exist in the system. 
                                            This means your data is up-to-date and no new items need to be imported.
                                        </p>
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h6 class="fw-medium mb-2">What this means:</h6>
                                                <ul class="list-unstyled small mb-3">
                                                    <li class="mb-1">
                                                        <i class="fas fa-check text-success me-2"></i>
                                                        Your Excel file contains valid item data
                                                    </li>
                                                    <li class="mb-1">
                                                        <i class="fas fa-check text-success me-2"></i>
                                                        All items are already in the system
                                                    </li>
                                                    <li class="mb-1">
                                                        <i class="fas fa-check text-success me-2"></i>
                                                        No duplicate items will be created
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="col-md-4">
                                                <h6 class="fw-medium mb-2">Next steps:</h6>
                                                <ul class="list-unstyled small">
                                                    <li class="mb-1">
                                                        <i class="fas fa-arrow-right text-info me-2"></i>
                                                        Upload a different file with new items
                                                    </li>
                                                    <li class="mb-1">
                                                        <i class="fas fa-arrow-right text-info me-2"></i>
                                                        Go to Items list to manage existing items
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2 mt-3">
                                            <button type="button" 
                                                    wire:click="clearFile" 
                                                    class="btn btn-outline-primary">
                                                <i class="fas fa-upload me-2"></i>
                                                Upload Different File
                                            </button>
                                            <a href="{{ route('admin.items.index') }}" 
                                               class="btn btn-outline-secondary">
                                                <i class="fas fa-list me-2"></i>
                                                View All Items
                                            </a>
                                            <a href="{{ route('admin.items.import-template.download') }}" 
                                               class="btn btn-outline-info">
                                                <i class="fas fa-download me-2"></i>
                                                Download Template
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Preview Section - Only for items that can be imported -->
                    @if($showPreview && count($previewData) > 0 && !$allItemsExist)
                        <div class="preview-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-semibold">
                                    <i class="fas fa-table me-2 text-muted"></i>
                                    Step 2: Preview Data ({{ count($previewData) }} items)
                                </h6>
                                
                                <div class="d-flex gap-2">
                                    <button type="button" 
                                            wire:click="importItems" 
                                            wire:loading.attr="disabled"
                                            class="btn btn-success"
                                            @if(count($previewErrors) > 0) disabled @endif>
                                        <i class="fas fa-save me-2"></i>
                                        <span wire:loading.remove wire:target="importItems">Import All Items</span>
                                        <span wire:loading wire:target="importItems">Importing...</span>
                                    </button>
                                    
                                    <button type="button" 
                                            wire:click="cancelPreview" 
                                            class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>
                                        Clear
                                    </button>
                                </div>
                            </div>

                            @if(count($previewErrors) > 0)
                                <div class="alert alert-warning border">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-exclamation-triangle me-3 mt-1 text-warning"></i>
                                        <div>
                                            <h6 class="alert-heading mb-2">
                                                <i class="fas fa-file-excel me-2"></i>
                                                {{ count($previewErrors) }} Item{{ count($previewErrors) != 1 ? 's' : '' }} Cannot Be Imported
                                            </h6>
                                            <p class="mb-2">
                                                The following problems were found in your Excel file:
                                            </p>
                                            <div class="row">
                                                <div class="col-md-8">
                                                    @php
                                                        $errorTypes = [];
                                                        $errorExamples = [];
                                                        foreach($previewErrors as $error) {
                                                            if (strpos($error, 'Name') !== false) {
                                                                $errorTypes['name'] = ($errorTypes['name'] ?? 0) + 1;
                                                                if (!isset($errorExamples['name'])) {
                                                                    $errorExamples['name'] = $error;
                                                                }
                                                            } elseif (strpos($error, 'Category') !== false) {
                                                                $errorTypes['category'] = ($errorTypes['category'] ?? 0) + 1;
                                                                if (!isset($errorExamples['category'])) {
                                                                    $errorExamples['category'] = $error;
                                                                }
                                                            } elseif (strpos($error, 'Price') !== false) {
                                                                $errorTypes['price'] = ($errorTypes['price'] ?? 0) + 1;
                                                                if (!isset($errorExamples['price'])) {
                                                                    $errorExamples['price'] = $error;
                                                                }
                                                            } elseif (strpos($error, 'SKU') !== false) {
                                                                $errorTypes['sku'] = ($errorTypes['sku'] ?? 0) + 1;
                                                                if (!isset($errorExamples['sku'])) {
                                                                    $errorExamples['sku'] = $error;
                                                                }
                                                            } elseif (strpos($error, 'Barcode') !== false) {
                                                                $errorTypes['barcode'] = ($errorTypes['barcode'] ?? 0) + 1;
                                                                if (!isset($errorExamples['barcode'])) {
                                                                    $errorExamples['barcode'] = $error;
                                                                }
                                                            } elseif (strpos($error, 'Brand') !== false) {
                                                                $errorTypes['brand'] = ($errorTypes['brand'] ?? 0) + 1;
                                                                if (!isset($errorExamples['brand'])) {
                                                                    $errorExamples['brand'] = $error;
                                                                }
                                                            } elseif (strpos($error, 'Description') !== false) {
                                                                $errorTypes['description'] = ($errorTypes['description'] ?? 0) + 1;
                                                                if (!isset($errorExamples['description'])) {
                                                                    $errorExamples['description'] = $error;
                                                                }
                                                            } elseif (strpos($error, 'already exists') !== false) {
                                                                $errorTypes['duplicate'] = ($errorTypes['duplicate'] ?? 0) + 1;
                                                                if (!isset($errorExamples['duplicate'])) {
                                                                    $errorExamples['duplicate'] = $error;
                                                                }
                                                            } else {
                                                                $errorTypes['other'] = ($errorTypes['other'] ?? 0) + 1;
                                                                if (!isset($errorExamples['other'])) {
                                                                    $errorExamples['other'] = $error;
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                    
                                                    <h6 class="fw-medium mb-2">Error Summary:</h6>
                                                    <div class="small">
                                                        @if(isset($errorTypes['name']))
                                                            <div class="mb-2">
                                                                <div class="fw-medium text-danger">
                                                                    <i class="fas fa-times me-2"></i>
                                                                    {{ $errorTypes['name'] }} item{{ $errorTypes['name'] != 1 ? 's' : '' }} with name issues
                                                                </div>
                                                                <div class="text-muted ms-4 small">
                                                                    Example: {{ $errorExamples['name'] }}
                                                                </div>
                                                            </div>
                                                        @endif
                                                        
                                                        @if(isset($errorTypes['category']))
                                                            <div class="mb-2">
                                                                <div class="fw-medium text-danger">
                                                                    <i class="fas fa-times me-2"></i>
                                                                    {{ $errorTypes['category'] }} item{{ $errorTypes['category'] != 1 ? 's' : '' }} with category issues
                                                                </div>
                                                                <div class="text-muted ms-4 small">
                                                                    Example: {{ $errorExamples['category'] }}
                                                                </div>
                                                            </div>
                                                        @endif
                                                        
                                                        @if(isset($errorTypes['price']))
                                                            <div class="mb-2">
                                                                <div class="fw-medium text-danger">
                                                                    <i class="fas fa-times me-2"></i>
                                                                    {{ $errorTypes['price'] }} item{{ $errorTypes['price'] != 1 ? 's' : '' }} with price issues
                                                                </div>
                                                                <div class="text-muted ms-4 small">
                                                                    Example: {{ $errorExamples['price'] }}
                                                                </div>
                                                            </div>
                                                        @endif
                                                        
                                                        @if(isset($errorTypes['sku']))
                                                            <div class="mb-2">
                                                                <div class="fw-medium text-danger">
                                                                    <i class="fas fa-times me-2"></i>
                                                                    {{ $errorTypes['sku'] }} item{{ $errorTypes['sku'] != 1 ? 's' : '' }} with SKU issues
                                                                </div>
                                                                <div class="text-muted ms-4 small">
                                                                    Example: {{ $errorExamples['sku'] }}
                                                                </div>
                                                            </div>
                                                        @endif
                                                        
                                                        @if(isset($errorTypes['barcode']))
                                                            <div class="mb-2">
                                                                <div class="fw-medium text-danger">
                                                                    <i class="fas fa-times me-2"></i>
                                                                    {{ $errorTypes['barcode'] }} item{{ $errorTypes['barcode'] != 1 ? 's' : '' }} with barcode issues
                                                                </div>
                                                                <div class="text-muted ms-4 small">
                                                                    Example: {{ $errorExamples['barcode'] }}
                                                                </div>
                                                            </div>
                                                        @endif
                                                        
                                                        @if(isset($errorTypes['brand']))
                                                            <div class="mb-2">
                                                                <div class="fw-medium text-danger">
                                                                    <i class="fas fa-times me-2"></i>
                                                                    {{ $errorTypes['brand'] }} item{{ $errorTypes['brand'] != 1 ? 's' : '' }} with brand issues
                                                                </div>
                                                                <div class="text-muted ms-4 small">
                                                                    Example: {{ $errorExamples['brand'] }}
                                                                </div>
                                                            </div>
                                                        @endif
                                                        
                                                        @if(isset($errorTypes['description']))
                                                            <div class="mb-2">
                                                                <div class="fw-medium text-danger">
                                                                    <i class="fas fa-times me-2"></i>
                                                                    {{ $errorTypes['description'] }} item{{ $errorTypes['description'] != 1 ? 's' : '' }} with description issues
                                                                </div>
                                                                <div class="text-muted ms-4 small">
                                                                    Example: {{ $errorExamples['description'] }}
                                                                </div>
                                                            </div>
                                                        @endif
                                                        
                                                        @if(isset($errorTypes['duplicate']))
                                                            <div class="mb-2">
                                                                <div class="fw-medium text-danger">
                                                                    <i class="fas fa-times me-2"></i>
                                                                    {{ $errorTypes['duplicate'] }} item{{ $errorTypes['duplicate'] != 1 ? 's' : '' }} already exist in system
                                                                </div>
                                                                <div class="text-muted ms-4 small">
                                                                    Example: {{ $errorExamples['duplicate'] }}
                                                                </div>
                                                            </div>
                                                        @endif
                                                        
                                                        @if(isset($errorTypes['other']))
                                                            <div class="mb-2">
                                                                <div class="fw-medium text-danger">
                                                                    <i class="fas fa-times me-2"></i>
                                                                    {{ $errorTypes['other'] }} other error{{ $errorTypes['other'] != 1 ? 's' : '' }}
                                                                </div>
                                                                <div class="text-muted ms-4 small">
                                                                    Example: {{ $errorExamples['other'] }}
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    
                                                    @if(count($previewErrors) > 10)
                                                        <div class="mt-2 text-muted small">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Showing error summary. Check the table below for all specific row details.
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-4">
                                                    <h6 class="fw-medium mb-2">How to fix:</h6>
                                                    <ul class="list-unstyled small">
                                                        <li class="mb-1">
                                                            <i class="fas fa-edit text-warning me-2"></i>
                                                            Edit your Excel file
                                                        </li>
                                                        <li class="mb-1">
                                                            <i class="fas fa-upload text-info me-2"></i>
                                                            Upload the corrected file
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="button" 
                                                        wire:click="clearFile" 
                                                        class="btn btn-outline-warning btn-sm">
                                                    <i class="fas fa-upload me-1"></i>
                                                    Upload Fixed File
                                                </button>
                                                <a href="{{ route('admin.items.import-template.download') }}" 
                                                   class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-download me-1"></i>
                                                    Download Template
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-hover border">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="fw-medium">Row</th>
                                            <th class="fw-medium">Name</th>
                                            <th class="fw-medium">Category</th>
                                            <th class="fw-medium">Cost Price</th>
                                            <th class="fw-medium">Selling Price</th>
                                            <th class="fw-medium">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($previewData as $item)
                                            <tr class="@if(!$item['is_valid']) table-danger @endif">
                                                <td class="fw-medium">{{ $item['row'] }}</td>
                                                <td>
                                                    {{ $item['name'] ?? 'N/A' }}
                                                    @if(!empty($item['errors']))
                                                        <div class="text-danger small mt-1">
                                                            @foreach($item['errors'] as $error)
                                                                @if(strpos($error, 'Name') !== false)
                                                                    {{ $error }}
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item['category'] && $item['category'] !== $defaultCategory)
                                                        {{ $item['category'] }}
                                                    @else
                                                        <span class="text-muted">{{ $item['category'] ?? 'Default' }}</span>
                                                        <i class="fas fa-info-circle text-muted ms-1" title="Default category will be applied"></i>
                                                    @endif
                                                    @if(!empty($item['errors']))
                                                        <div class="text-danger small mt-1">
                                                            @foreach($item['errors'] as $error)
                                                                @if(strpos($error, 'Category') !== false)
                                                                    {{ $error }}
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item['cost_price'] > 0)
                                                        {{ number_format($item['cost_price'], 2) }}
                                                    @else
                                                        <span class="text-muted">0.00</span>
                                                        <i class="fas fa-info-circle text-muted ms-1" title="Default cost price"></i>
                                                    @endif
                                                    @if(!empty($item['errors']))
                                                        <div class="text-danger small mt-1">
                                                            @foreach($item['errors'] as $error)
                                                                @if(strpos($error, 'Cost') !== false)
                                                                    {{ $error }}
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item['selling_price'] > 0)
                                                        {{ number_format($item['selling_price'], 2) }}
                                                    @else
                                                        <span class="text-muted">0.00</span>
                                                        <i class="fas fa-info-circle text-muted ms-1" title="Default selling price"></i>
                                                    @endif
                                                    @if(!empty($item['errors']))
                                                        <div class="text-danger small mt-1">
                                                            @foreach($item['errors'] as $error)
                                                                @if(strpos($error, 'Selling') !== false)
                                                                    {{ $error }}
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ Str::limit($item['description'] ?? '', 50) }}
                                                    @if(!empty($item['errors']))
                                                        <div class="text-danger small mt-1">
                                                            @foreach($item['errors'] as $error)
                                                                @if(strpos($error, 'Description') !== false)
                                                                    {{ $error }}
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Instructions Section -->
                    @if(!$showPreview || count($previewData) == 0)
                        <div class="instructions-section mt-4">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card border">
                                        <div class="card-header bg-white border-bottom">
                                            <h6 class="mb-0 fw-semibold">
                                                <i class="fas fa-info-circle me-2 text-muted"></i>
                                                Import Instructions
                                            </h6>
                                        </div>
                                        <div class="card-body p-4">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="fw-medium mb-3">Required Fields:</h6>
                                                    <ul class="list-unstyled">
                                                        <li class="mb-2">
                                                            <i class="fas fa-check text-success me-2"></i>Item Name
                                                        </li>
                                                        <li class="mb-2">
                                                            <i class="fas fa-check text-success me-2"></i>Category (optional - default will be applied)
                                                        </li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="fw-medium mb-3">Optional Fields:</h6>
                                                    <ul class="list-unstyled">
                                                        <li class="mb-2">
                                                            <i class="fas fa-info text-muted me-2"></i>Description
                                                        </li>
                                                        <li class="mb-2">
                                                            <i class="fas fa-info text-muted me-2"></i>Cost Price (defaults to 0)
                                                        </li>
                                                        <li class="mb-2">
                                                            <i class="fas fa-info text-muted me-2"></i>Selling Price (defaults to 0)
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="mt-4 pt-3 border-top">
                                                <h6 class="fw-medium mb-2">Default Values:</h6>
                                                <p class="mb-0 text-muted small">
                                                    Items without category will use the selected default category. 
                                                    All items will be imported with "Active" status. 
                                                    Prices default to 0 if not specified.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border">
                                        <div class="card-header bg-white border-bottom">
                                            <h6 class="mb-0 fw-semibold">
                                                <i class="fas fa-exclamation-triangle me-2 text-muted"></i>
                                                Important Notes
                                            </h6>
                                        </div>
                                        <div class="card-body p-4">
                                            <ul class="list-unstyled small">
                                                <li class="mb-2">
                                                    <i class="fas fa-arrow-right text-muted me-2"></i>Use Excel format (.xlsx, .xls)
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-arrow-right text-muted me-2"></i>First row should be headers
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-arrow-right text-muted me-2"></i>Preview before importing
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-arrow-right text-muted me-2"></i>Fix any validation errors
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div> 