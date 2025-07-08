<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Price History - {{ $item->name }}</h2>
            <a href="{{ route('admin.price-history.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </x-slot>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Item Details</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> {{ $item->name }}</p>
                    <p><strong>Current Price:</strong> {{ number_format($item->selling_price, 2) }}</p>
                    <p><strong>Cost Price:</strong> {{ number_format($item->cost_price, 2) }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>SKU:</strong> {{ $item->sku }}</p>
                    <p><strong>Category:</strong> {{ $item->category->name }}</p>
                    <p><strong>Stock:</strong> {{ $item->stock }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Price History</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Old Price</th>
                            <th>New Price</th>
                            <th>Change</th>
                            <th>Type</th>
                            <th>User</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($priceHistories as $history)
                            <tr>
                                <td>{{ $history->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ number_format($history->old_price, 2) }}</td>
                                <td>{{ number_format($history->new_price, 2) }}</td>
                                <td>
                                    @php
                                        $change = $history->new_price - $history->old_price;
                                        $percentage = $history->old_price != 0 ? ($change / $history->old_price) * 100 : 0;
                                    @endphp
                                    <span class="badge bg-{{ $change >= 0 ? 'success' : 'danger' }}">
                                        {{ number_format($change, 2) }} ({{ number_format($percentage, 1) }}%)
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ ucfirst($history->change_type) }}</span>
                                </td>
                                <td>{{ $history->user->name }}</td>
                                <td>{{ $history->notes }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No price history records found for this item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $priceHistories->links() }}
            </div>
        </div>
    </div>
</x-app-layout>