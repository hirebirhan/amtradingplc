@extends('layouts.app')

@section('title', 'Import Items')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.items.index') }}">Items</a></li>
    <li class="breadcrumb-item active">Import</li>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-body border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Import Items</h5>
                    <a href="{{ route('admin.items.import-template.download') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-download me-1"></i> Template
                    </a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ $errors->first() }}
                        </div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('info'))
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            {{ session('info') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.items.import.preview') }}" enctype="multipart/form-data" class="mb-3">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-6">
                                <label for="file" class="form-label fw-medium">Upload Excel file (.xlsx)</label>
                                <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="default_category_id" class="form-label fw-medium">Default Category</label>
                                <select name="default_category_id" id="default_category_id" class="form-select">
                                    <option value="">— None —</option>
                                    @foreach(($categories ?? collect()) as $cat)
                                        <option value="{{ $cat->id }}" @selected(($default_category_id ?? null)===$cat->id)>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Used when a row has no category.</div>
                            </div>
                            <div class="col-12 col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="bi bi-search me-1"></i> Preview Upload
                                </button>
                                @if(!empty($defaultFileExists))
                                <button type="submit" name="use_default" value="1" class="btn btn-outline-secondary" title="Use amtradingstock.xlsx from project root">
                                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Use Default
                                </button>
                                @endif
                            </div>
                        </div>
                    </form>

                    @isset($preview)
                        <div class="mb-3">
                            <h6 class="fw-semibold mb-2">File Preview</h6>
                            <div class="small text-secondary mb-2">
                                Sheet: <span class="fw-medium">{{ $preview['sheetTitle'] }}</span> • Rows detected: <span class="fw-medium">{{ $preview['rowCount'] }}</span>
                            </div>
                            <!-- Item Details Table -->
                            <h6 class="fw-semibold mb-2">Item Details</h6>
                            <div class="table-responsive border rounded mb-4">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            @foreach($preview['headers'] as $index => $h)
                                                <th class="text-nowrap">
                                                    {{ $h ?: '—' }}
                                                    @if(strtolower($h) == 'sku' || strtolower($h) == 'code')
                                                        (Code)
                                                    @elseif(strtolower($h) == 'unit' || strtolower($h) == 'u.m')
                                                        (U.M)
                                                    @elseif(strpos(strtolower($h), 'cost') !== false)
                                                        (U.COST)
                                                    @elseif(strpos(strtolower($h), 'sell') !== false || strpos(strtolower($h), 'price') !== false)
                                                        (U.COST)
                                                    @endif
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($preview['sample'] as $row)
                                            <tr>
                                                @foreach($preview['headers'] as $index => $h)
                                                    <td class="text-nowrap">{{ isset($row[$index]) && is_scalar($row[$index]) ? $row[$index] : '' }}</td>
                                                @endforeach
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ count($preview['headers']) }}" class="text-center text-secondary py-4">No sample rows found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Branch Quantities Table -->
                            <h6 class="fw-semibold mb-2">Branch Quantities</h6>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item Name</th>
                                            @php
                                                // Find branch/quantity columns for the branch quantities table
                                                $branchColumns = [];
                                                foreach($preview['headers'] as $index => $h) {
                                                    $lower = strtolower($h);
                                                    if (in_array($lower, ['bicha', 'kemer', 'furi']) || 
                                                        str_contains($lower, 'quantity') || 
                                                        str_contains($lower, 'branch')) {
                                                        $branchColumns[$index] = $h;
                                                    }
                                                }
                                            @endphp
                                            @foreach($branchColumns as $h)
                                                <th class="text-center">{{ $h ?: '—' }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($preview['sample'] as $row)
                                            <tr>
                                                @php
                                                    // Find name column
                                                    $nameIndex = array_search('Name', $preview['headers']) ?? array_search('name', $preview['headers']);
                                                    $itemName = $nameIndex !== false ? ($row[$nameIndex] ?? 'Unknown Item') : 'Unknown Item';
                                                @endphp
                                                <td>{{ $itemName }}</td>
                                                @foreach($branchColumns as $index => $h)
                                                    <td class="text-center">{{ is_scalar($row[$index] ?? '') ? $row[$index] : '0' }}</td>
                                                @endforeach
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ count($branchColumns) + 1 }}" class="text-center text-secondary py-4">No sample rows found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="border rounded p-3">
                            <h6 class="fw-semibold mb-2">Proposed Field Mapping</h6>
                            <div class="row g-3">
                                @php
                                    $targets = [
                                        'name' => 'Item Name',
                                        'sku' => 'Code',
                                        'barcode' => 'Barcode',
                                        'category' => 'Category',
                                        'unit' => 'U.M',
                                        'unit_quantity' => 'Unit Quantity',
                                        'cost_price' => 'U.COST',
                                        'selling_price' => 'U.COST',
                                        'reorder_level' => 'Reorder Level',
                                        'brand' => 'Brand',
                                        'description' => 'Description',
                                    ];
                                @endphp
                                @foreach($targets as $key => $label)
                                <div class="col-12 col-md-6">
                                    <label class="form-label small text-muted">{{ $label }}</label>
                                    <select class="form-select form-select-sm" disabled>
                                        <option value="">— Not mapped —</option>
                                        @foreach($preview['headers'] as $h)
                                            <option value="{{ $h }}" @selected(($preview['suggestions'][$key] ?? null) === $h)>{{ $h }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endforeach
                            </div>
                            <div class="small text-secondary mt-2">These are initial guesses. We’ll finalize mapping and rules next.</div>
                        </div>

                        <form method="POST" action="{{ route('admin.items.import.apply') }}" enctype="multipart/form-data" class="mt-3">
                            @csrf
                            <input type="hidden" name="default_category_id" value="{{ $default_category_id ?? '' }}">
                            <input type="hidden" name="use_default" value="{{ request()->boolean('use_default', false) ? 1 : 0 }}">
                            
                            @if(isset($preview) && !request()->boolean('use_default', false))
                                <!-- Include the original file as hidden input -->
                                <input type="hidden" name="file_already_uploaded" value="1">
                                <div class="mb-3">
                                    <div class="alert alert-light border">
                                        <i class="bi bi-info-circle me-2"></i>
                                        The preview file will be used for import. If you need to upload a different file, go back to the previous step.
                                    </div>
                                </div>
                            @else
                                <!-- Allow uploading a new file for import -->
                                <div class="mb-3">
                                    <label for="apply_file" class="form-label">Upload Excel File</label>
                                    <input type="file" name="file" id="apply_file" class="form-control" accept=".xlsx,.xls" required>
                                    <div class="form-text">Please select a file to import, or check 'Use Default' to use the system default file.</div>
                                </div>
                            @endif
                            
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-upload me-1"></i> Apply Import
                                    </button>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="use_default_checkbox" name="use_default" value="1" 
                                        @if(request()->boolean('use_default', false)) checked @endif>
                                    <label class="form-check-label" for="use_default_checkbox">
                                        Use default file (amtradingstock.xlsx)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-text mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Note: You must either upload a file or check "Use Default" to proceed with the import.
                            </div>
                        </form>
                    @endisset
                </div>
            </div>
        </div>
    </div>
    
    <!-- Next Steps Section in its own row -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-body border-0">
                    <h6 class="mb-0">Next Steps</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ol class="mb-3">
                                <li>Confirm which columns map to item fields.</li>
                                <li>Define defaults for missing values.</li>
                                <li>Choose duplicate handling strategy.</li>
                                <li>Confirm rounding and pricing rules.</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                Once you confirm the details, we'll enable the import button and create items accordingly.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
