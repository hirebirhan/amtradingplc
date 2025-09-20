@extends('layouts.app')

@section('title', 'Import Items')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.items.index') }}">Items</a></li>
    <li class="breadcrumb-item active">Import</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row g-3">
        <div class="col-12 col-xl-8">
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
                            <div class="table-responsive border rounded">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            @foreach($preview['headers'] as $h)
                                                <th class="text-nowrap">{{ $h ?: '—' }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($preview['sample'] as $row)
                                            <tr>
                                                @foreach($row as $cell)
                                                    <td class="text-nowrap">{{ is_scalar($cell) ? $cell : '' }}</td>
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
                        </div>

                        <div class="border rounded p-3">
                            <h6 class="fw-semibold mb-2">Proposed Field Mapping</h6>
                            <div class="row g-3">
                                @php
                                    $targets = [
                                        'name' => 'Item Name',
                                        'sku' => 'SKU',
                                        'barcode' => 'Barcode',
                                        'category' => 'Category',
                                        'unit' => 'Unit',
                                        'unit_quantity' => 'Unit Quantity',
                                        'cost_price' => 'Cost Price (ETB)',
                                        'selling_price' => 'Selling Price (ETB)',
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

                        <form method="POST" action="{{ route('admin.items.import.apply') }}" class="mt-3">
                            @csrf
                            <input type="hidden" name="default_category_id" value="{{ $default_category_id ?? '' }}">
                            <input type="hidden" name="use_default" value="{{ request()->boolean('use_default', false) ? 1 : 0 }}">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-upload me-1"></i> Apply Import (awaiting configuration)
                            </button>
                            <div class="form-text">Note: Applying with "Use Default" will import from amtradingstock.xlsx in project root. To import an uploaded file, re-upload it and submit directly.</div>
                        </form>
                    @endisset
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-body border-0">
                    <h6 class="mb-0">Next Steps</h6>
                </div>
                <div class="card-body">
                    <ol class="mb-3">
                        <li>Confirm which columns map to item fields.</li>
                        <li>Define defaults for missing values.</li>
                        <li>Choose duplicate handling strategy.</li>
                        <li>Confirm rounding and pricing rules.</li>
                    </ol>
                    <div class="small text-secondary">
                        Once you confirm the details, we’ll enable the import button and create items accordingly.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
