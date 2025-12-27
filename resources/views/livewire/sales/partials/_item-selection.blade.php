{{-- Item Selection and Add Item Form --}}
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-3">
            <h6 class="fw-semibold mb-0">Sale Items</h6>
            @php
                $itemCount = is_countable($items) ? count($items) : 0;
            @endphp
            @if($itemCount > 0)
                <div class="d-flex align-items-center gap-3">
                    <small class="text-muted">Subtotal: <span class="fw-semibold">{{ number_format($subtotal, 2) }}</span></small>
                    <small class="text-muted">Total: <span class="fw-bold">{{ number_format($totalAmount, 2) }}</span></small>
                </div>
            @endif
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="loadItemOptions" title="Refresh items">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            @php
                $itemCount = is_countable($items) ? count($items) : 0;
            @endphp
            @if($itemCount > 0)
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#clearCartModal"
                    title="Clear all items">
                    <i class="bi bi-trash me-1"></i>Clear All
                </button>
            @endif
        </div>
    </div>

    {{-- Add Item Form --}}
    <div class="border rounded p-3">
        <div class="row g-3">
            {{-- Item Selection --}}
            <div class="col-12 {{ ($selectedItem && !$stockWarningType) ? 'col-lg-3' : 'col-lg-12' }}">
                <label class="form-label fw-medium">
                    Item <span class="text-primary">*</span>
                </label>
                @if($selectedItem && !$stockWarningType)
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="bi bi-box-seam text-primary"></i>
                        </span>
                        <input type="text" readonly class="form-control form-control-lg fw-medium" value="{{ $selectedItem['name'] }}">
                        <button class="btn btn-outline-danger" type="button" wire:click="clearSelectedItem" title="Clear item">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1">
                        <i class="bi bi-tag me-1"></i>SKU: {{ $selectedItem['sku'] }}
                    </small>
                @elseif(!$stockWarningType)
                    <div class="position-relative">
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-search text-secondary"></i>
                            </span>
                            <input type="text" 
                                   wire:model.live.debounce.300ms="itemSearch" 
                                   class="form-control form-control-lg" 
                                   placeholder="Search by name, SKU, or barcode..."
                                   autocomplete="off">
                        </div>
                        @if(strlen($itemSearch) >= 2)
                            <div class="dropdown-menu show w-100 shadow-sm border" style="max-height: 300px; overflow-y: auto; z-index: 1050;">
                                @if(count($this->filteredItemOptions) > 0)
                                    @foreach($this->filteredItemOptions as $item)
                                        <button type="button" 
                                                class="dropdown-item py-2 px-3" 
                                                wire:click="selectItem({{ $item['id'] }})">
                                            <div class="d-flex justify-content-between align-items-start gap-3">
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="fw-medium text-dark mb-1">{{ $item['name'] }}</div>
                                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                                        <small class="text-muted">
                                                            <i class="bi bi-tag me-1"></i>{{ $item['sku'] }}
                                                        </small>
                                                        @if(($item['unit_quantity'] ?? 1) > 1)
                                                            <small class="text-muted">
                                                                <i class="bi bi-box me-1"></i>1 pcs = {{ $item['unit_quantity'] }} {{ $item['item_unit'] ?? 'units' }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    @php
                                                        $stockQty = floatval($item['quantity'] ?? 0);
                                                        $unitQty = $item['unit_quantity'] ?? 1;
                                                        $totalUnits = $stockQty * $unitQty;
                                                    @endphp
                                                    @if($stockQty < 0)
                                                        <span class="badge bg-dark text-white">
                                                            <i class="bi bi-exclamation-circle me-1"></i>
                                                            <div class="small">Negative</div>
                                                            <div class="fw-bold">{{ number_format($stockQty, 0) }} pcs</div>
                                                        </span>
                                                    @elseif($stockQty == 0)
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="bi bi-x-circle me-1"></i>
                                                            <div class="small">Out of Stock</div>
                                                        </span>
                                                    @elseif($stockQty <= 5)
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                                            <div class="fw-bold">{{ number_format($stockQty, 0) }} pcs</div>
                                                            @if($unitQty > 1)
                                                                <div class="small">{{ number_format($totalUnits, 1) }} {{ $item['item_unit'] ?? 'units' }}</div>
                                                            @endif
                                                        </span>
                                                    @else
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-check-circle me-1"></i>
                                                            <div class="fw-bold">{{ number_format($stockQty, 0) }} pcs</div>
                                                            @if($unitQty > 1)
                                                                <div class="small">{{ number_format($totalUnits, 1) }} {{ $item['item_unit'] ?? 'units' }}</div>
                                                            @endif
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </button>
                                    @endforeach
                                @else
                                    <div class="dropdown-item-text text-center py-3">
                                        <i class="bi bi-search text-muted d-block mb-2" style="font-size: 2rem;"></i>
                                        <small class="text-muted">No items found for "{{ $itemSearch }}"</small>
                                    </div>
                                @endif
                            </div>
                        @elseif(strlen($itemSearch) > 0)
                            <div class="dropdown-menu show w-100 shadow-sm border">
                                <div class="dropdown-item-text text-center py-3">
                                    <i class="bi bi-keyboard text-muted d-block mb-2" style="font-size: 2rem;"></i>
                                    <small class="text-muted">Type at least 2 characters to search...</small>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            @if($selectedItem && !$stockWarningType)
                {{-- Sale Method Toggle --}}
                <div class="col-12 col-lg-2">
                    <label class="form-label fw-medium">Sale Method</label>
                    <div class="btn-group w-100 d-flex" role="group">
                        <input type="radio" class="btn-check" wire:model.live="newItem.sale_method" value="piece" id="method_piece" name="sale_method">
                        <label class="btn btn-outline-primary btn-lg flex-fill text-center px-1" for="method_piece">
                            <i class="bi bi-box d-block d-xl-inline me-xl-1"></i>
                            <span class="d-block d-xl-inline">Piece</span>
                        </label>
                        <input type="radio" class="btn-check" wire:model.live="newItem.sale_method" value="unit" id="method_unit" name="sale_method">
                        <label class="btn btn-outline-primary btn-lg flex-fill text-center px-1" for="method_unit">
                            <i class="bi bi-rulers d-block d-xl-inline me-xl-1"></i>
                            <span class="d-block d-xl-inline">{{ $selectedItem['item_unit'] ?? 'Unit' }}</span>
                        </label>
                    </div>
                </div>

                {{-- Quantity --}}
                <div class="col-6 col-lg-2">
                    <label class="form-label fw-medium">Quantity</label>
                    <div class="input-group">
                        <input type="number" wire:model.live="newItem.quantity" class="form-control form-control-lg" min="1" step="{{ $newItem['sale_method'] === 'unit' ? '0.01' : '1' }}" placeholder="0">
                        <span class="input-group-text">
                            @if($newItem['sale_method'] === 'piece')
                                pcs
                            @else
                                {{ $selectedItem['item_unit'] ?? 'units' }}
                            @endif
                        </span>
                    </div>
                    @php
                        $availableStock = $this->getAvailableStockForMethod();
                        $requestedQty = floatval($newItem['quantity'] ?? 0);
                        $willBeNegative = $requestedQty > $availableStock;
                    @endphp
                    <small class="d-block mt-1 {{ $willBeNegative ? 'text-warning' : 'text-muted' }} text-truncate">
                        Available: {{ number_format($availableStock, 2) }}
                        @if($willBeNegative && $requestedQty > 0)
                            <br><i class="bi bi-exclamation-triangle"></i> Negative
                        @endif
                    </small>
                </div>

                {{-- Unit Price --}}
                <div class="col-6 col-lg-2">
                    <label class="form-label fw-medium">
                        @if($newItem['sale_method'] === 'piece')
                            Price/pc
                        @else
                            Price/{{ $selectedItem['item_unit'] ?? 'unit' }}
                        @endif
                    </label>
                    <input type="number" wire:model.live="newItem.unit_price" class="form-control form-control-lg" min="0" step="0.01" placeholder="0.00">
                </div>

                {{-- Total Price --}}
                <div class="col-6 col-lg">
                    <label class="form-label fw-medium">Total</label>
                    <input type="text" class="form-control form-control-lg" value="{{ number_format((floatval($newItem['quantity'] ?? 0)) * (floatval($newItem['price'] ?? 0)), 2) }}" readonly>
                </div>

                {{-- Add Button --}}
                <div class="col-6 col-lg-auto">
                    <label class="form-label d-block">&nbsp;</label>
                    @if($editingItemIndex !== null)
                        <div class="btn-group w-100">
                            <button type="button" class="btn btn-success btn-lg" wire:click="addItem">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg" wire:click="cancelEdit">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    @else
                        <button type="button" class="btn btn-primary btn-lg w-100" wire:click="addItem">
                            <i class="bi bi-plus-lg me-1"></i>Add
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
