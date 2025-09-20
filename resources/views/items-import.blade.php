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
                            <!-- JSON Data Display -->
                            <h5 class="fw-semibold mb-3">JSON Data Preview</h5>
                            
                            <div class="row mb-4">
                                <div class="col-12">
                                    @php
                                        // Prepare the JSON data structures
                                        $itemsJsonData = [];
                                        $branchQuantitiesJsonData = [];
                                        
                                        foreach($preview['sample'] as $rowIndex => $row) {
                                            // Find column indices based on headers
                                            $headers = $preview['headers'];
                                            $findCol = function($candidates) use ($headers) {
                                                foreach($candidates as $candidate) {
                                                    foreach($headers as $index => $header) {
                                                        $headerLower = strtolower($header);
                                                        if (strpos($headerLower, strtolower($candidate)) !== false) {
                                                            return $index;
                                                        }
                                                    }
                                                }
                                                return null;
                                            };
                                            
                                            // Map columns
                                            $codeIndex = $findCol(['sku', 'code']);
                                            $nameIndex = $findCol(['name', 'item', 'designation']);
                                            $umIndex = $findCol(['u.m', 'unit', 'um']);
                                            $costIndex = $findCol(['cost', 'u.cost', 'ucost']);
                                            $priceIndex = $findCol(['selling', 'price', 'sell']);
                                            
                                            // Create item data
                                            $itemData = [
                                                'code' => isset($codeIndex, $row[$codeIndex]) ? $row[$codeIndex] : '',
                                                'name' => isset($nameIndex, $row[$nameIndex]) ? $row[$nameIndex] : '',
                                                'um' => isset($umIndex, $row[$umIndex]) ? $row[$umIndex] : 'pcs',
                                                'cost' => isset($costIndex, $row[$costIndex]) && is_numeric($row[$costIndex]) ? floatval($row[$costIndex]) : 0,
                                                'price' => isset($priceIndex, $row[$priceIndex]) && is_numeric($row[$priceIndex]) ? floatval($row[$priceIndex]) : 0,
                                            ];
                                            
                                            // Create branch quantities data
                                            $branchData = [];
                                            $branches = ['bicha', 'kemer', 'furi'];
                                            foreach($branches as $branch) {
                                                $idx = $findCol([$branch]);
                                                if ($idx !== null) {
                                                    $qty = isset($row[$idx]) && is_numeric($row[$idx]) ? floatval($row[$idx]) : 0;
                                                    $branchData[$branch] = $qty;
                                                }
                                            }
                                            
                                            // Combine them for the combined JSON
                                            $combinedData = $itemData;
                                            $combinedData['branches'] = $branchData;
                                            
                                            // Add to collections
                                            $itemsJsonData[] = $itemData;
                                            $branchQuantitiesJsonData[] = [
                                                'item_name' => $itemData['name'],
                                                'item_code' => $itemData['code'],
                                                'quantities' => $branchData
                                            ];
                                            
                                            // Add hidden input for form submission
                                            echo '<input type="hidden" name="item_data[]" value=\'' . htmlspecialchars(json_encode($combinedData), ENT_QUOTES, 'UTF-8') . '\' />';
                                        }
                                        
                                        // All items from Excel (all items)
                                        $allItemsJson = isset($preview['allItems']) ? json_encode($preview['allItems'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '[]';
                                    @endphp
                                    
                                    <!-- Complete Items JSON -->
                                    <div class="card shadow-sm border-0 mb-4">
                                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                                            <h5 class="card-title mb-0 d-flex align-items-center">
                                                <i class="bi bi-braces-asterisk me-2"></i>
                                                Complete Items JSON (All {{ count(isset($preview['allItems']) ? $preview['allItems'] : []) }} Items)
                                            </h5>
                                            <button type="button" class="btn btn-light" onclick="copyToClipboard('allItemsJson')">
                                                <i class="bi bi-clipboard me-1"></i> Copy JSON
                                            </button>
                                        </div>
                                        <div class="card-body p-0">
                                            <pre id="allItemsJson" class="language-json mb-0 overflow-auto p-3" style="max-height: 550px;"><code>{{ $allItemsJson }}</code></pre>
                                        </div>
                                        <div class="card-footer bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <strong>Format:</strong> JSON with item details
                                                </small>
                                                <small class="text-muted">
                                                    <i class="bi bi-info-circle me-1"></i> 
                                                    Price equals cost as requested
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Add syntax highlighting -->
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    document.querySelectorAll('pre code').forEach(function(block) {
                                        // Simple highlighting
                                        const html = block.innerHTML
                                            .replace(/"([^"]+)":/g, '<span style="color: #9c27b0">"$1"</span>:')  // Keys
                                            .replace(/: "([^"]+)"/g, ': <span style="color: #2196f3">"$1"</span>')  // String values
                                            .replace(/: ([0-9]+(?:\.[0-9]+)?)/g, ': <span style="color: #ff5722">$1</span>'); // Numbers
                                        
                                        block.innerHTML = html;
                                    });
                                });
                                
                                // Add a function to copy JSON to clipboard
                                function copyToClipboard(elementId) {
                                    const element = document.getElementById(elementId);
                                    const textarea = document.createElement('textarea');
                                    textarea.value = element.textContent;
                                    document.body.appendChild(textarea);
                                    textarea.select();
                                    document.execCommand('copy');
                                    document.body.removeChild(textarea);
                                    
                                    // Show a temporary success message
                                    const btn = event.target.closest('button');
                                    const originalText = btn.innerHTML;
                                    btn.innerHTML = '<i class="bi bi-check-circle"></i> Copied!';
                                    btn.classList.remove('btn-outline-light');
                                    btn.classList.add('btn-success');
                                    
                                    setTimeout(() => {
                                        btn.innerHTML = originalText;
                                        btn.classList.remove('btn-success');
                                        btn.classList.add('btn-outline-light');
                                    }, 2000);
                                }
                            </script>
                            
                            <!-- Branch Quantities Summary -->
                            <h6 class="fw-semibold mb-2">Branch Quantities Summary</h6>
                            <div class="card border mb-4">
                                <div class="card-body">
                                    @php
                                        // Calculate branch totals
                                        $branchTotals = [];
                                        $branchNames = ['bicha', 'kemer', 'furi'];
                                        $totalItems = 0;
                                        $totalValue = 0;
                                        
                                        foreach($preview['sample'] as $row) {
                                            $headers = $preview['headers'];
                                            $findCol = function($candidates) use ($headers) {
                                                foreach($candidates as $candidate) {
                                                    foreach($headers as $index => $header) {
                                                        $headerLower = strtolower($header);
                                                        if (strpos($headerLower, strtolower($candidate)) !== false) {
                                                            return $index;
                                                        }
                                                    }
                                                }
                                                return null;
                                            };
                                            
                                            // Get cost index
                                            $costIndex = $findCol(['cost', 'u.cost', 'ucost']);
                                            $cost = isset($costIndex, $row[$costIndex]) && is_numeric($row[$costIndex]) ? floatval($row[$costIndex]) : 0;
                                            
                                            // Process each branch
                                            foreach($branchNames as $branch) {
                                                $branchIndex = $findCol([$branch]);
                                                if ($branchIndex !== null) {
                                                    $qty = isset($row[$branchIndex]) && is_numeric($row[$branchIndex]) ? floatval($row[$branchIndex]) : 0;
                                                    if (!isset($branchTotals[$branch])) {
                                                        $branchTotals[$branch] = [
                                                            'qty' => 0,
                                                            'value' => 0
                                                        ];
                                                    }
                                                    $branchTotals[$branch]['qty'] += $qty;
                                                    $branchTotals[$branch]['value'] += ($qty * $cost);
                                                    $totalItems += $qty;
                                                    $totalValue += ($qty * $cost);
                                                }
                                            }
                                        }
                                    @endphp
                                    
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Branch</th>
                                                            <th class="text-end">Quantity</th>
                                                            <th class="text-end">Value (ETB)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($branchTotals as $branch => $totals)
                                                            <tr>
                                                                <td>{{ ucfirst($branch) }}</td>
                                                                <td class="text-end">{{ number_format($totals['qty']) }}</td>
                                                                <td class="text-end">{{ number_format($totals['value'], 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                        <tr class="table-secondary fw-bold">
                                                            <td>Total</td>
                                                            <td class="text-end">{{ number_format($totalItems) }}</td>
                                                            <td class="text-end">{{ number_format($totalValue, 2) }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                       
                                    </div>
                                </div>
                            </div>
                        </div>


                        <form method="POST" action="{{ route('admin.items.import.apply') }}" enctype="multipart/form-data" class="mt-4">
                            @csrf
                            <input type="hidden" name="default_category_id" value="{{ $default_category_id ?? '' }}">
                            <input type="hidden" name="use_default" value="{{ request()->boolean('use_default', false) ? 1 : 0 }}">
                            
                            <!-- Item data from JSON -->
                            <input type="hidden" name="json_import" value="1">
                            
                            <div class="card border">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">Apply Import</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-7">
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
                                                    <label for="apply_file" class="form-label fw-bold">Upload Excel File</label>
                                                    <input type="file" name="file" id="apply_file" class="form-control" accept=".xlsx,.xls" required>
                                                    <div class="form-text">Please select a file to import, or check 'Use Default' to use the system default file.</div>
                                                </div>
                                            @endif
                                            
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="use_default_checkbox" name="use_default" value="1" 
                                                    @if(request()->boolean('use_default', false)) checked @endif>
                                                <label class="form-check-label fw-medium" for="use_default_checkbox">
                                                    Use default file (amtradingstock.xlsx)
                                                </label>
                                            </div>
                                        </div>
                                       
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                       
                                        <button type="submit" class="btn btn-lg btn-success">
                                            <i class="bi bi-cloud-upload me-1"></i> Process Import
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @endisset
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
