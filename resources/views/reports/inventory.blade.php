<x-app-layout>
    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
            <div class="flex-grow-1">
                <h4 class="fw-bold mb-0">Inventory Reports</h4>
                <p class="text-secondary mb-0 small">Real-time stock monitoring and analysis</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <button class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>Print
                </button>
                <button class="btn btn-primary" id="exportBtn">
                    <i class="bi bi-download me-2"></i>Export
                </button>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <!-- Filters Section -->
                <div class="p-4 border-bottom">
                    <form action="{{ route('admin.reports.inventory') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="warehouse_id" class="form-label fw-semibold">Warehouse</label>
                                <select class="form-select" id="warehouse_id" name="warehouse_id">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                            {{ $warehouse->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="category_id" class="form-label fw-semibold">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="stock_status" class="form-label fw-semibold">Stock Status</label>
                                <select class="form-select" id="stock_status" name="stock_status">
                                    <option value="">All Status</option>
                                    <option value="in_stock" {{ request('stock_status') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                                    <option value="low_stock" {{ request('stock_status') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                                    <option value="out_of_stock" {{ request('stock_status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search me-2"></i>Apply Filters
                                    </button>
                                    <a href="{{ route('admin.reports.inventory') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise me-2"></i>Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Stats Row -->
                <div class="p-4 border-bottom">
                    <div class="row g-3">
                        <div class="col-6 col-lg-3">
                            <div class="text-center">
                                <div class="h3 fw-bold text-primary mb-1">{{ $total_items ?? 0 }}</div>
                                <div class="small text-secondary">Total Items</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="text-center">
                                <div class="h3 fw-bold text-success mb-1">{{ $in_stock_items ?? 0 }}</div>
                                <div class="small text-secondary">In Stock</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="text-center">
                                <div class="h3 fw-bold text-warning mb-1">{{ $low_stock_items ?? 0 }}</div>
                                <div class="small text-secondary">Low Stock</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="text-center">
                                <div class="h3 fw-bold text-danger mb-1">{{ $out_of_stock_items ?? 0 }}</div>
                                <div class="small text-secondary">Out of Stock</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert Section -->
                @if(isset($low_stock_items) && $low_stock_items > 0 && isset($low_stock_items_list) && $low_stock_items_list->count() > 0)
                    <div class="p-4 border-bottom">
                        <h6 class="fw-semibold mb-3">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            Low Stock Alert ({{ $low_stock_items_list->count() }} items)
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-secondary">
                                    <tr>
                                        <th class="fw-semibold">Item</th>
                                        <th class="fw-semibold text-center">Current Stock</th>
                                        <th class="fw-semibold text-center">Alert Level</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($low_stock_items_list->take(5) as $item)
                                        <tr>
                                            <td>
                                                <div class="fw-medium">{{ $item->name }}</div>
                                                <small class="text-secondary">{{ $item->sku ?? 'N/A' }}</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning bg-opacity-15 text-warning">
                                                    {{ $item->current_stock }}
                                                </span>
                                            </td>
                                            <td class="text-center text-secondary">{{ $item->alert_quantity }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($low_stock_items_list->count() > 5)
                            <div class="text-center mt-2">
                                <small class="text-secondary">Showing first 5 items. Total: {{ $low_stock_items_list->count() }} items</small>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Inventory Table -->
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-semibold mb-0">Inventory Items</h6>
                        <small class="text-secondary">{{ isset($items) ? $items->count() : 0 }} items</small>
                    </div>
                    
                    @if(isset($items) && $items->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover" id="inventoryTable">
                                <thead class="table-secondary">
                                    <tr>
                                        <th class="fw-semibold">Item Details</th>
                                        <th class="fw-semibold">Category</th>
                                        <th class="fw-semibold">Location</th>
                                        <th class="fw-semibold text-end">Unit Price</th>
                                        <th class="fw-semibold text-center">Stock</th>
                                        <th class="fw-semibold text-center">Alert Level</th>
                                        <th class="fw-semibold">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $item)
                                        <tr>
                                            <td>
                                                <div class="fw-medium">{{ $item->name }}</div>
                                                <small class="text-secondary">{{ $item->sku ?? 'No SKU' }}</small>
                                            </td>
                                            <td>
                                                @if($item->category)
                                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                                        {{ $item->category->name }}
                                                    </span>
                                                @else
                                                    <small class="text-secondary">Uncategorized</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item->warehouse)
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-building text-secondary me-1"></i>
                                                        <span>{{ $item->warehouse->name }}</span>
                                                    </div>
                                                @else
                                                    <small class="text-secondary">No warehouse</small>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-medium">ETB {{ number_format($item->unit_price, 2) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold">{{ $item->current_stock }}</span>
                                            </td>
                                            <td class="text-center text-secondary">{{ $item->alert_quantity }}</td>
                                            <td>
                                                @if($item->current_stock == 0)
                                                    <span class="badge bg-danger bg-opacity-15 text-danger">
                                                        Out of Stock
                                                    </span>
                                                @elseif($item->current_stock <= $item->alert_quantity)
                                                    <span class="badge bg-warning bg-opacity-15 text-warning">
                                                        Low Stock
                                                    </span>
                                                @else
                                                    <span class="badge bg-success bg-opacity-15 text-success">
                                                        In Stock
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="text-secondary mb-3">
                                <i class="bi bi-box fs-1"></i>
                            </div>
                            <h6 class="fw-semibold mb-2">No Items Found</h6>
                            <p class="text-secondary mb-0">No inventory items match your search criteria</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Export functionality
            $('#exportBtn').click(function() {
                const button = $(this);
                const originalText = button.html();
                
                button.html('<i class="bi bi-arrow-clockwise spin me-2"></i>Exporting...');
                button.prop('disabled', true);
                
                let table = document.getElementById('inventoryTable');
                if (!table) {
                    alert('No data to export');
                    button.html(originalText);
                    button.prop('disabled', false);
                    return;
                }
                
                let rows = table.querySelectorAll('tr');
                let csv = [];
                
                for (let i = 0; i < rows.length; i++) {
                    let row = [], cols = rows[i].querySelectorAll('td, th');
                    
                    for (let j = 0; j < cols.length; j++) {
                        let data = cols[j].textContent.replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
                        data = data.replace(/"/g, '""');
                        row.push('"' + data + '"');
                    }
                    csv.push(row.join(','));
                }
                
                let csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
                let encodedUri = encodeURI(csvContent);
                let link = document.createElement('a');
                link.setAttribute('href', encodedUri);
                link.setAttribute('download', `inventory_report_${new Date().toISOString().split('T')[0]}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                setTimeout(() => {
                    button.html(originalText);
                    button.prop('disabled', false);
                }, 1000);
            });

            // Table hover effects
            $('#inventoryTable tbody tr').hover(
                function() {
                    $(this).addClass('bg-secondary bg-opacity-25');
                },
                function() {
                    $(this).removeClass('bg-secondary bg-opacity-25');
                }
            );
        });
    </script>
    @endpush
</x-app-layout> 