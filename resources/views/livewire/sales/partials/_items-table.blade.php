{{-- Items Table --}}
@php
    $itemCount = is_countable($items) ? count($items) : 0;
@endphp
@if($itemCount > 0)
    <div class="mb-4">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="py-3 px-4 fw-semibold text-dark">Item</th>
                        <th class="text-center py-3 px-3 fw-semibold text-dark">Method</th>
                        <th class="text-center py-3 px-3 fw-semibold text-dark">Qty</th>
                        <th class="text-end py-3 px-3 fw-semibold text-dark">Price</th>
                        <th class="text-end py-3 px-3 fw-semibold text-dark">Total</th>
                        <th class="text-end py-3 px-4 fw-semibold text-dark" style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                    <tr>
                        <td class="py-3 px-4">
                            <div class="fw-medium">{{ $item['name'] }}</div>
                        </td>
                        <td class="text-center py-3 px-3">
                            @if(($item['sale_method'] ?? 'piece') === 'piece')
                                <span class="badge bg-primary-subtle text-primary-emphasis">
                                    <i class="bi bi-box me-1"></i>Piece
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success-emphasis">
                                    <i class="bi bi-rulers me-1"></i>Unit
                                </span>
                            @endif
                        </td>
                        <td class="text-center py-3 px-3">
                            {{ $item['quantity'] }} 
                            @if(($item['sale_method'] ?? 'piece') === 'piece')
                                pcs
                            @else
                                {{ $item['item_unit'] ?? 'units' }}
                            @endif
                        </td>
                        <td class="text-end py-3 px-3">
                            {{ number_format($item['price'], 2) }}
                        </td>
                        <td class="text-end py-3 px-3">
                            <span class="fw-semibold">{{ number_format($item['subtotal'], 2) }}</span>
                        </td>
                        <td class="text-end py-3 px-4">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" wire:click="editItem({{ $index }})" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" wire:click="removeItem({{ $index }})" title="Remove">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
