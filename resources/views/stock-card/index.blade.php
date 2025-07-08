@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Standard Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <h4 class="fw-bold mb-0">Stock Card</h4>
            <p class="text-secondary mb-0 small">Track stock movements and transaction history</p>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm">
        <!-- Filter Section -->
        <div class="card-header bg-transparent border-bottom px-4 py-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="item_id" class="form-label small">Item</label>
                    <select name="item_id" id="item_id" class="form-select">
                        <option value="">Select an item</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" {{ ($filters['item_id'] ?? '') == $item->id ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label for="date_from" class="form-label small">From Date</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-12 col-md-3">
                    <label for="date_to" class="form-label small">To Date</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button class="btn btn-primary" type="submit">Filter</button>
                </div>
            </form>
        </div>

        <!-- Content Section -->
        <div class="card-body p-0">
            @if($filters['item_id'])
                @php
                    $selectedItem = $items->find($filters['item_id']);
                @endphp
                @if($selectedItem)
                    <!-- Item Info -->
                    <div class="px-4 py-3 border-bottom">
                        <h6 class="fw-semibold mb-1">{{ $selectedItem->name }}</h6>
                        <span class="text-secondary small">SKU: {{ $selectedItem->sku }}</span>
                    </div>

                    <!-- Data Tables -->
                    <div class="p-4">
                        <!-- Purchases Table -->
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-2">Purchases</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="px-2 py-2 fw-semibold small">Date</th>
                                            <th class="px-2 py-2 fw-semibold small">Item</th>
                                            <th class="px-2 py-2 fw-semibold small">Quantity</th>
                                            <th class="px-2 py-2 fw-semibold small">Amount</th>
                                            <th class="px-2 py-2 fw-semibold small">Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($purchasesByDate->count() > 0)
                                            @foreach($purchasesByDate as $date => $purchases)
                                                @foreach($purchases as $purchase)
                                                    @php
                                                        $selectedItemPurchases = $purchase->purchaseItems->where('item_id', $filters['item_id']);
                                                    @endphp
                                                    @foreach($selectedItemPurchases as $item)
                                                        <tr>
                                                            <td class="px-2 py-2 small">
                                                                {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                                                            </td>
                                                            <td class="px-2 py-2">
                                                                <div class="small fw-medium">{{ $item->item->name }}</div>
                                                            </td>
                                                            <td class="px-2 py-2 small">
                                                                {{ $item->quantity }} pc
                                                            </td>
                                                            <td class="px-2 py-2 small">
                                                                {{ number_format($purchase->total_amount, 2) }} ETB
                                                            </td>
                                                            <td class="px-2 py-2">
                                                                <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="text-decoration-none text-primary small">
                                                                    <i class="bi bi-eye me-1"></i>View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="5" class="px-2 py-3 text-center text-muted small">
                                                    No purchases found for this item.
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Sales Table -->
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-2">Sales</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="px-2 py-2 fw-semibold small">Date</th>
                                            <th class="px-2 py-2 fw-semibold small">Item</th>
                                            <th class="px-2 py-2 fw-semibold small">Quantity</th>
                                            <th class="px-2 py-2 fw-semibold small">Amount</th>
                                            <th class="px-2 py-2 fw-semibold small">Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($salesByDate->count() > 0)
                                            @foreach($salesByDate as $date => $sales)
                                                @foreach($sales as $sale)
                                                    @php
                                                        $selectedItemSales = $sale->saleItems->where('item_id', $filters['item_id']);
                                                    @endphp
                                                    @foreach($selectedItemSales as $item)
                                                        <tr>
                                                            <td class="px-2 py-2 small">
                                                                {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                                                            </td>
                                                            <td class="px-2 py-2">
                                                                <div class="small fw-medium">{{ $item->item->name }}</div>
                                                            </td>
                                                            <td class="px-2 py-2 small">
                                                                {{ $item->quantity }} pc
                                                            </td>
                                                            <td class="px-2 py-2 small">
                                                                {{ number_format($sale->total_amount, 2) }} ETB
                                                            </td>
                                                            <td class="px-2 py-2">
                                                                <a href="{{ route('admin.sales.show', $sale->id) }}" class="text-decoration-none text-success small">
                                                                    <i class="bi bi-eye me-1"></i>View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="5" class="px-2 py-3 text-center text-muted small">
                                                    No sales found for this item.
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <!-- Empty State -->
                <div class="text-center py-5">
                    <h6 class="text-muted mb-2">Select an Item</h6>
                    <p class="text-muted small">Choose an item from the dropdown to view its stock card</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 