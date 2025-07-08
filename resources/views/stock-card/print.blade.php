@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Stock Card</h2>
        <button onclick="window.print()" class="btn btn-outline-primary d-print-none">
            <i class="bi bi-printer me-2"></i> Print
        </button>
    </div>

    @if($filters['itemName'])
        <div class="mb-3">
            <span class="fw-semibold">Item:</span> {{ $filters['itemName'] }}
        </div>
    @endif
    @if($filters['dateFrom'] || $filters['dateTo'])
        <div class="mb-4">
            <span class="fw-semibold">Date Range:</span>
            {{ $filters['dateFrom'] ? $filters['dateFrom'] : 'Any' }} - {{ $filters['dateTo'] ? $filters['dateTo'] : 'Any' }}
        </div>
    @endif
    @if(request('typeFilter'))
        <div class="mb-4">
            <span class="fw-semibold">Type:</span>
            <span class="badge bg-primary-subtle text-primary-emphasis">{{ ucfirst(request('typeFilter')) }}</span>
        </div>
    @endif

    @php
        // Group stockMovements by date
        $grouped = $stockMovements->groupBy(fn($m) => \Carbon\Carbon::parse($m->created_at)->format('Y-m-d'));
    @endphp

    @forelse($grouped as $date => $movements)
        <div class="mb-4">
            <h5 class="fw-bold mb-3">{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Amount</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $movement)
                            <tr>
                                <td>{{ $movement->item->name ?? '-' }}</td>
                                <td>{{ $movement->quantity_change }}</td>
                                <td>
                                    @if($movement->reference_type === 'sale' && $movement->reference && isset($movement->reference->total_amount))
                                        {{ number_format($movement->reference->total_amount, 2) }}
                                    @elseif($movement->reference_type === 'purchase' && $movement->reference && isset($movement->reference->total_amount))
                                        {{ number_format($movement->reference->total_amount, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($movement->reference_type === 'sale' && $movement->reference)
                                        Sale #{{ $movement->reference->reference_no ?? '' }}
                                    @elseif($movement->reference_type === 'purchase' && $movement->reference)
                                        Purchase #{{ $movement->reference->reference_no ?? '' }}
                                    @else
                                        {{ ucfirst($movement->reference_type) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="alert alert-info">No stock movements found for the selected filters.</div>
    @endforelse
</div>
@endsection 