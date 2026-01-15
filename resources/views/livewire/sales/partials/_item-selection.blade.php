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

    {{-- Item Selection Error Alert --}}
    @if($errors->hasAny(['newItem.item_id', 'newItem.quantity', 'newItem.price', 'newItem.unit_price']))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <div class="flex-grow-1">
                    <strong>Item Selection Error:</strong>
                    <ul class="mb-0 mt-1 small">
                        @error('newItem.item_id')<li>{{ $message }}</li>@enderror
                        @error('newItem.quantity')<li>{{ $message }}</li>@enderror
                        @error('newItem.price')<li>{{ $message }}</li>@enderror
                        @error('newItem.unit_price')<li>{{ $message }}</li>@enderror
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Add Item Form --}}
    <div class="border rounded p-3">
        <div class="row g-2 align-items-end">
            {{-- Item Selection --}}
            <div class="col-12 {{ $selectedItem ? 'col-md-3' : 'col-md-10' }}" wire:key="item-selection-container">
                <label class="form-label fw-medium mb-1">
                    Item <span class="text-primary">*</span>
                </label>
                
                <div class="position-relative" wire:key="item-selection-container-root">
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="bi {{ $selectedItem ? 'bi-box-seam text-primary' : 'bi-search text-secondary' }}"></i>
                        </span>
                        <input type="text" 
                               wire:model.live.debounce.300ms="itemSearch" 
                               class="form-control {{ $selectedItem ? 'bg-light fw-medium' : '' }}" 
                               @if(!$stockWarningType)
                                   placeholder="Search items..."
                               @endif
                               autocomplete="off"
                               {{ $selectedItem ? 'readonly' : '' }}>
                        
                        @if($selectedItem && !$stockWarningType)
                            <button class="btn btn-outline-danger" type="button" wire:click="clearSelectedItem" title="Clear item">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        @endif
                    </div>

                    @if($selectedItem && !$stockWarningType)
                        <small class="text-muted d-block mt-1">
                            <i class="bi bi-tag me-1"></i>SKU: {{ $selectedItem['sku'] }}
                        </small>
                    @elseif(!$stockWarningType)
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
                    @endif
                </div>
            </div>

            @if($selectedItem && !$stockWarningType)
                {{-- Sale Unit Selection --}}
                <div class="col-12 col-md-1">
                    <label class="form-label fw-medium mb-1">Unit</label>
                    @php
                        $itemUnit = $selectedItem['item_unit'] ?? 'each';
                        $unitQuantity = $selectedItem['unit_quantity'] ?? 1;
                        $unitLabels = [
                            'kg' => 'Kilogram',
                            'g' => 'Gram',
                            'ton' => 'Ton',
                            'lb' => 'Pound',
                            'oz' => 'Ounce',
                            'liter' => 'Liter',
                            'ml' => 'Milliliter',
                            'gallon' => 'Gallon',
                            'meter' => 'Meter',
                            'cm' => 'Centimeter',
                            'mm' => 'Millimeter',
                            'inch' => 'Inch',
                            'ft' => 'Foot',
                            'sqm' => 'Sq. Meter',
                            'sqft' => 'Sq. Foot',
                        ];
                    @endphp
                    <select wire:model.live="newItem.sale_unit" class="form-select">
                        <option value="each">Each (Piece)</option>
                        @if($unitQuantity > 1 && $itemUnit !== 'each' && $itemUnit !== 'piece')
                            <option value="{{ $itemUnit }}">{{ $unitLabels[$itemUnit] ?? ucfirst($itemUnit) }}</option>
                        @endif
                    </select>
                    @if($unitQuantity > 1 && $itemUnit !== 'each' && $itemUnit !== 'piece')
                        <small class="text-muted d-block mt-1">
                            <i class="bi bi-info-circle me-1"></i>1 Each = {{ $unitQuantity }} {{ $unitLabels[$itemUnit] ?? ucfirst($itemUnit) }}
                        </small>
                    @endif
                </div>

                {{-- Quantity --}}
                <div class="col-6 col-md-2">
                    <label class="form-label fw-medium mb-1">Qty</label>
                    <div class="input-group">
                        @php
                            $isEach = ($newItem['sale_unit'] ?? 'each') === 'each';
                            $saleUnit = $newItem['sale_unit'] ?? 'each';
                            $step = in_array($saleUnit, ['g', 'ml']) ? '0.01' : '1';
                            $unitLabel = $isEach ? 'Each' : ucfirst($saleUnit);
                        @endphp
                        <input type="number" wire:model.live="newItem.quantity" class="form-control" min="0.01" step="{{ $step }}" placeholder="0">
                        <span class="input-group-text bg-light">{{ $unitLabel }}</span>
                    </div>
                    @php
                        $saleUnit = $newItem['sale_unit'] ?? 'each';
                        $availableStock = 0;
                        try {
                            $availableStock = $this->getAvailableStockForUnit($saleUnit);
                        } catch (\Exception $e) {
                            $availableStock = 0;
                        }
                        $requestedQty = floatval($newItem['quantity'] ?? 0);
                        $willExceed = $requestedQty > $availableStock;
                        $step = in_array($saleUnit, ['g', 'ml']) ? '0.01' : '1';
                        $unitLabel = $saleUnit === 'each' ? 'Each' : ucfirst($saleUnit);
                    @endphp
                    <small class="d-block mt-1 {{ $willExceed ? 'text-warning' : 'text-muted' }} text-truncate">
                        Available: {{ number_format($availableStock, $step === '0.01' ? 2 : 0) }} {{ $unitLabel }}
                        @if($willExceed && $requestedQty > 0)
                            <br><i class="bi bi-exclamation-triangle"></i> Exceeds stock
                        @endif
                    </small>
                </div>

                {{-- Unit Price --}}
                <div class="col-6 col-md-2">
                    <label class="form-label fw-medium mb-1">
                        Price @if(($newItem['sale_unit'] ?? 'each') === 'each')/Each @else/{{ ucfirst($newItem['sale_unit'] ?? 'unit') }} @endif
                    </label>
                    <div class="input-group">
                        <input type="number" wire:model.live="newItem.unit_price" class="form-control" min="0" step="0.01" placeholder="0.00">
                    </div>
                    @if($newItem['sale_method'] === 'unit' && ($selectedItem['unit_quantity'] ?? 1) > 1)
                        <small class="text-muted d-block mt-1">
                            = {{ number_format((floatval($newItem['unit_price'] ?? 0)) * ($selectedItem['unit_quantity'] ?? 1), 2) }} ETB/Each
                        </small>
                    @endif
                </div>

                {{-- Total Price --}}
                <div class="col-6 col-md-2">
                    <label class="form-label fw-medium mb-1">Total</label>
                    <div class="input-group">
                        <input type="text" class="form-control fw-bold" value="{{ number_format((floatval($newItem['quantity'] ?? 0)) * (floatval($newItem['unit_price'] ?? 0)), 2) }}" readonly>
                    </div>
                    @if($newItem['sale_method'] === 'unit' && ($selectedItem['unit_quantity'] ?? 1) > 1)
                        @php
                            $totalEach = (floatval($newItem['quantity'] ?? 0)) / ($selectedItem['unit_quantity'] ?? 1);
                        @endphp
                        <small class="text-muted d-block mt-1">
                            {{ number_format($totalEach, 2) }} Each × {{ number_format((floatval($newItem['price'] ?? 0)) * ($selectedItem['unit_quantity'] ?? 1), 2) }} ETB
                        </small>
                    @endif
                </div>

                <div class="col-6 col-md-2 d-flex align-items-end">
                    @if($editingItemIndex !== null)
                        <div class="d-flex gap-1 w-100">
                            <button type="button" class="btn btn-primary btn-sm flex-fill" wire:click="addItem">
                                Update
                            </button>
                        </div>
                    @else
                        <button type="button" class="btn btn-primary w-100" wire:click="addItem">
                            <i class="bi bi-plus-lg me-1"></i>Add
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
