<x-app-layout>
    <!-- Modern 2-Row Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <!-- Row 1: Title -->
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-2">
                <h4 class="fw-bold mb-0">Price History</h4>
            </div>
            <!-- Row 2: Description -->
            <p class="text-secondary mb-0 small">
                Track price changes, monitor cost fluctuations, and analyze pricing trends over time
            </p>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <button class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-download me-1"></i>Export
            </button>
            <button class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex flex-column flex-md-row gap-3">
                <form action="{{ route('admin.price-history.index') }}" method="GET" class="d-flex flex-column flex-md-row gap-2 w-100">
                    <div class="flex-grow-1">
                        <select name="item_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Items</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" placeholder="Start Date">
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" placeholder="End Date">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3 py-3">
                                <i class="bi bi-calendar me-1"></i>Date
                            </th>
                            <th class="py-3">
                                <i class="bi bi-box me-1"></i>Item
                            </th>
                            <th class="py-3 text-end">
                                <i class="bi bi-currency-dollar me-1"></i>Old Price
                            </th>
                            <th class="py-3 text-end">
                                <i class="bi bi-currency-dollar me-1"></i>New Price
                            </th>
                            <th class="py-3 text-center">
                                <i class="bi bi-graph-up me-1"></i>Change
                            </th>
                            <th class="py-3 text-center">
                                <i class="bi bi-tag me-1"></i>Type
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($priceHistories as $history)
                            <tr>
                                <td class="px-3 py-3">
                                    <div class="fw-medium">{{ $history->created_at->format('M d, Y') }}</div>
                                    <div class="text-secondary small">{{ $history->created_at->format('H:i') }}</div>
                                </td>
                                <td class="py-3">
                                    <a href="{{ route('admin.price-history.show', $history->item) }}" class="text-decoration-none">
                                        <div class="fw-medium text-primary">{{ $history->item->name }}</div>
                                        @if($history->item->code)
                                            <div class="text-secondary small">{{ $history->item->code }}</div>
                                        @endif
                                    </a>
                                </td>
                                <td class="py-3 text-end">
                                    <span class="fw-medium">ETB {{ number_format($history->old_price, 2) }}</span>
                                </td>
                                <td class="py-3 text-end">
                                    <span class="fw-medium">ETB {{ number_format($history->new_price, 2) }}</span>
                                </td>
                                <td class="py-3 text-center">
                                    @php
                                        $change = $history->new_price - $history->old_price;
                                        $percentage = $history->old_price != 0 ? ($change / $history->old_price) * 100 : 0;
                                    @endphp
                                    <span class="badge bg-{{ $change >= 0 ? 'success' : 'danger' }}-subtle text-{{ $change >= 0 ? 'success' : 'danger' }}-emphasis">
                                        <i class="bi bi-arrow-{{ $change >= 0 ? 'up' : 'down' }} me-1"></i>
                                        {{ number_format($change, 2) }} ({{ number_format($percentage, 1) }}%)
                                    </span>
                                </td>
                                <td class="py-3 text-center">
                                    <span class="badge bg-info-subtle text-info-emphasis">
                                        {{ ucfirst($history->change_type) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="mb-3">
                                            <i class="bi bi-clock-history text-secondary" style="font-size: 3rem;"></i>
                                        </div>
                                        <h5 class="text-secondary">No price history records found</h5>
                                        <p class="text-secondary">Price changes will appear here once items are updated</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($priceHistories->hasPages())
            <div class="d-flex justify-content-center py-3 border-top">
                {{ $priceHistories->links() }}
            </div>
        @endif
    </div>
</x-app-layout>