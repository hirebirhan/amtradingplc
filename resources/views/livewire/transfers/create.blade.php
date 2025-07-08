<div>
    <!-- Responsive Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-semibold mb-1 text-dark d-none d-md-block">Create Transfer</h4>
            <h5 class="fw-semibold mb-1 text-dark d-md-none">Create Transfer</h5>
            <p class="text-muted mb-0 small d-none d-sm-block">Move items between warehouses and branches</p>
        </div>
        <a href="{{ route('admin.transfers.index') }}" class="btn btn-outline-secondary btn-sm">
            <span class="d-none d-sm-inline">Back to Transfers</span>
            <span class="d-sm-none">← Back</span>
        </a>
    </div>

    <!-- Error/Success Messages -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <h6 class="alert-heading">Please fix the following errors:</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @error('general')
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @enderror

    <!-- Transfer Details Card -->
    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header border-bottom">
            <h6 class="mb-0 fw-semibold">Transfer Details</h6>
        </div>
        <div class="card-body p-3">
            <div class="row g-2">
                <!-- Source Location -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium small">Source Type</label>
                    <select class="form-select form-select-sm @error('form.source_type') is-invalid @enderror" 
                            wire:model.live="form.source_type"
                            {{ count($items) > 0 ? 'disabled' : '' }}>
                        <option value="warehouse">Warehouse</option>
                        <option value="branch">Branch</option>
                    </select>
                    @error('form.source_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium small">Source Location</label>
                    <select class="form-select form-select-sm @error('form.source_id') is-invalid @enderror" 
                            wire:model.live="form.source_id"
                            {{ count($items) > 0 ? 'disabled' : '' }}>
                        <option value="">Select {{ ucfirst($form['source_type']) }}</option>
                        @if($form['source_type'] === 'warehouse')
                            @foreach($availableSourceWarehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        @else
                            @foreach($availableSourceBranches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('form.source_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Destination Location -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium small">Destination Type</label>
                    <select class="form-select form-select-sm @error('form.destination_type') is-invalid @enderror" 
                            wire:model.live="form.destination_type"
                            {{ count($items) > 0 ? 'disabled' : '' }}>
                        <option value="warehouse">Warehouse</option>
                        <option value="branch">Branch</option>
                    </select>
                    @error('form.destination_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium small">Destination Location</label>
                    <select class="form-select form-select-sm @error('form.destination_id') is-invalid @enderror" 
                            wire:model.live="form.destination_id"
                            {{ count($items) > 0 ? 'disabled' : '' }}>
                        <option value="">Select {{ ucfirst($form['destination_type']) }}</option>
                        @if($form['destination_type'] === 'warehouse')
                            @foreach($availableDestinationWarehouses as $warehouse)
                                @if($form['source_type'] !== 'warehouse' || $warehouse->id != $form['source_id'])
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endif
                            @endforeach
                        @else
                            @foreach($availableDestinationBranches as $branch)
                                @if($form['source_type'] !== 'branch' || $branch->id != $form['source_id'])
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endif
                            @endforeach
                        @endif
                    </select>
                    @error('form.destination_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Notes -->
                <div class="col-12">
                    <label class="form-label fw-medium small">Notes</label>
                    <textarea class="form-control form-control-sm @error('form.note') is-invalid @enderror" 
                              wire:model="form.note"
                              rows="2"
                              placeholder="Add any additional notes about this transfer (optional)"></textarea>
                    @error('form.note')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Add Items Card -->
    @if(!empty($form['source_id']))
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    📦 Transfer Items
                </h6>
                <div class="d-flex align-items-center gap-2">
                    @if(count($items) > 0)
                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="clearCart" 
                                wire:confirm="Are you sure you want to clear all items?" title="Clear all items">
                            🗑️ Clear Items
                        </button>
                    @endif
                    @if($itemOptions && count($itemOptions) > 0)
                        <small class="badge bg-success bg-opacity-10 text-success">
                            {{ count($itemOptions) }} available
                        </small>
                    @endif
                </div>
            </div>
            
            <div class="card-body p-3">
                @if(!$itemOptions || count($itemOptions) == 0)
                    <div class="alert alert-warning p-2 mb-0 small">
                        <strong>⚠️ No Items Available</strong><br>
                        <small>
                            @if(count($items) > 0)
                                All available items from this location have been added.
                            @else
                                No items with available stock found at this location.
                            @endif
                        </small>
                    </div>
                @else
                    <!-- Add Item Form -->
                    <div class="row g-3 mb-3">
                        <!-- Item Selection -->
                        <div class="col-md-6">
                            <label class="form-label fw-medium">
                                🔍 Select Item
                                @if($editingItemIndex !== null)
                                    <span class="badge ms-2 bg-info bg-opacity-10 text-info">Editing #{{ $editingItemIndex + 1 }}</span>
                                @endif
                            </label>
                            @if($selectedItem)
                                <div class="input-group">
                                    <input 
                                        type="text" 
                                        readonly 
                                        class="form-control bg-light" 
                                        value="{{ $selectedItem['label'] }}{{ $editingItemIndex !== null ? ' - EDITING' : '' }}"
                                    >
                                    <button class="btn btn-outline-danger" type="button" wire:click="clearSelectedItem" title="Clear item">
                                        ×
                                    </button>
                                </div>
                            @else
                                <div class="position-relative" x-data="{ showItemDropdown: false }">
                                    <input 
                                        type="text" 
                                        class="form-control @error('newItem.item_id') is-invalid @enderror" 
                                        wire:model.live.debounce.300ms="itemSearchTerm" 
                                        placeholder="Search item by name or SKU..."
                                        autocomplete="off"
                                        @focus="showItemDropdown = true"
                                        @click="showItemDropdown = true"
                                        @click.away="showItemDropdown = false"
                                    >
                                    <div x-show="showItemDropdown && {{ count($this->filteredItemOptions ?? []) }} > 0" 
                                         class="position-absolute w-100 border rounded shadow-sm bg-body" 
                                         style="top: 100%; z-index: 1000; max-height: 200px; overflow-y: auto;">
                                        @foreach($this->filteredItemOptions as $option)
                                            <div class="px-3 py-2 border-bottom cursor-pointer small" 
                                                 wire:click="selectItem({{ $option['id'] }})"
                                                 @click="showItemDropdown = false">
                                                <div class="fw-medium">
                                                    @php
                                                        $labelParts = explode(' (', $option['label']);
                                                        $itemName = $labelParts[0] ?? $option['label'];
                                                    @endphp
                                                    {{ $itemName }}
                                                </div>
                                                <small class="text-muted">Available: {{ number_format($option['available_stock'], 2) }}</small>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @error('newItem.item_id') 
                                <div class="invalid-feedback d-block">{{ $message }}</div> 
                            @enderror
                            @if($duplicateWarning)
                                <small class="text-warning">{{ $duplicateWarning }}</small>
                            @endif
                        </div>

                        @if($newItem['item_id'])
                            <!-- Quantity -->
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Quantity</label>
                                <input 
                                    type="number" 
                                    wire:model.live.debounce.300ms="newItem.quantity" 
                                    class="form-control @error('newItem.quantity') is-invalid @enderror" 
                                    min="0.01" 
                                    step="0.01" 
                                    max="{{ $availableStock }}"
                                    placeholder="0.00"
                                >
                                @error('newItem.quantity') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                                @if($availableStock > 0)
                                    <small class="text-muted">Available: {{ number_format($availableStock, 2) }}</small>
                                    @if($availableStock < 10)
                                        <span class="badge bg-warning ms-1" title="Low stock warning">Low Stock</span>
                                    @endif
                                @else
                                    <small class="text-danger">⚠️ No stock available</small>
                                @endif
                            </div>

                            <!-- Add/Update Button -->
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="d-flex gap-1 w-100">
                                    @if($editingItemIndex !== null)
                                        <button 
                                            type="button" 
                                            wire:click="updateExistingItem" 
                                            class="btn btn-success btn-sm"
                                            title="Update item"
                                            {{ empty($newItem['item_id']) || empty($newItem['quantity']) ? 'disabled' : '' }}
                                        >
                                            <span wire:loading.remove wire:target="updateExistingItem">✅ Update</span>
                                            <span wire:loading wire:target="updateExistingItem">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                            </span>
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" wire:click="cancelEdit" title="Cancel editing">
                                            ❌
                                        </button>
                                    @else
                                        <button 
                                            type="button" 
                                            wire:click="addItem" 
                                            class="btn btn-primary w-100 btn-sm"
                                            {{ empty($newItem['item_id']) || empty($newItem['quantity']) ? 'disabled' : '' }}
                                        >
                                            <span wire:loading.remove wire:target="addItem">➕ Add Item</span>
                                            <span wire:loading wire:target="addItem">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                            </span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Item Info Display -->
                    @if($newItem['item_id'] && $newItem['quantity'])
                        <div class="alert alert-info p-2 mb-0 small">
                            <strong>📋 Transfer Quantity: {{ number_format($newItem['quantity'], 2) }}</strong>
                            @if($selectedItem && $selectedItem['unit'])
                                | Unit: {{ $selectedItem['unit'] }}
                            @endif
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    <!-- Items List -->
    @if(count($items) > 0)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Transfer Items</h6>
                <span class="badge bg-primary">{{ count($items) }} items</span>
            </div>
            <div class="card-body p-0">
                <!-- Desktop Table View -->
                <div class="d-none d-lg-block">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                                                            <thead>
                                <tr>
                                    <th class="fw-semibold border-0 px-3 py-2">Item</th>
                                    <th class="fw-semibold border-0 px-3 py-2">Quantity</th>
                                    <th class="fw-semibold border-0 px-3 py-2">Available Stock</th>
                                    <th class="fw-semibold border-0 px-3 py-2 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $index => $item)
                                    <tr>
                                        <td class="px-3 py-2">
                                            <div class="fw-medium">{{ $item['item_name'] }}</div>
                                        </td>
                                        <td class="px-3 py-2 fw-medium">{{ number_format($item['quantity'], 2) }}</td>
                                        <td class="px-3 py-2">
                                            <div class="d-flex align-items-center">
                                                <span class="text-muted">{{ number_format($item['available_stock'], 2) }}</span>
                                                @if($item['available_stock'] > 0)
                                                    <i class="fas fa-check-circle text-success ms-1" title="Stock available"></i>
                                                @else
                                                    <i class="fas fa-times-circle text-danger ms-1" title="No stock available"></i>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-end d-flex gap-2 justify-content-end">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary" 
                                                    wire:click="editItem({{ $index }})">
                                                Edit
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    wire:click="removeItem({{ $index }})">
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile Card View -->
                <div class="d-lg-none">
                    @foreach($items as $index => $item)
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-medium">{{ $item['item_name'] }}</div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="text-muted small">{{ number_format($item['available_stock'], 2) }}</span>
                                    @if($item['available_stock'] > 0)
                                        <i class="fas fa-check-circle text-success ms-1" title="Stock available"></i>
                                    @else
                                        <i class="fas fa-times-circle text-danger ms-1" title="No stock available"></i>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <div class="small text-muted">Quantity</div>
                                    <div class="fw-medium">{{ number_format($item['quantity'], 2) }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">Available</div>
                                    <div class="fw-medium">{{ number_format($item['available_stock'], 2) }}</div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary flex-fill" 
                                        wire:click="editItem({{ $index }})">
                                    ✏️ Edit
                                </button>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger flex-fill" 
                                        wire:click="removeItem({{ $index }})">
                                    🗑 Remove
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    @if(count($items) > 0)
        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('admin.transfers.index') }}" class="btn btn-outline-secondary btn-sm">
                <span class="d-none d-sm-inline">Cancel</span>
                <span class="d-sm-none">Cancel</span>
            </a>
            <button type="button" 
                    class="btn btn-success btn-sm" 
                    data-bs-toggle="modal" 
                    data-bs-target="#confirmTransferModal"
                    {{ count($items) === 0 || empty($form['destination_id']) || empty($form['source_id']) ? 'disabled' : '' }}>
                <i class="fas fa-check me-1"></i>
                <span class="d-none d-sm-inline">Complete Transfer</span>
                <span class="d-sm-none">Complete</span>
            </button>
        </div>
    @endif

    <!-- Enhanced Confirmation Modal -->
    <div class="modal fade" id="confirmTransferModal" tabindex="-1" aria-labelledby="confirmTransferModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content bg-body border">
                <div class="modal-header bg-primary text-white border-bottom">
                    <h5 class="modal-title fw-semibold" id="confirmTransferModalLabel">
                        📋 Review Transfer Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-3 overflow-auto" style="max-height: 70vh;">
                    @error('general')
                        <div class="alert alert-danger mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ $message }}
                        </div>
                    @enderror
                    
                    <!-- Transfer Info Summary -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="fw-medium mb-2">Transfer Information</h6>
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <span class="text-muted">From:</span>
                                    <span class="fw-medium d-block">
                                        {{ $this->sourceLocationName }}
                                    </span>
                                    <small class="text-muted">{{ ucfirst($form['source_type']) }}</small>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted">To:</span>
                                    <span class="fw-medium d-block">
                                        {{ $this->destinationLocationName }}
                                    </span>
                                    <small class="text-muted">{{ ucfirst($form['destination_type']) }}</small>
                                </div>
                                @if(!empty($form['note']))
                                <div class="col-12 mt-2">
                                    <span class="text-muted">Notes:</span>
                                    <span class="fw-medium d-block">
                                        {{ $form['note'] }}
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Items Summary -->
                    <div class="mb-3">
                        <h6 class="fw-medium mb-2">Items to Transfer ({{ count($items) }})</h6>
                        <div class="border rounded overflow-auto" style="max-height: 250px;">
                            @foreach($items as $item)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2 px-3">
                                <div class="flex-grow-1">
                                    <div class="fw-medium small">{{ $item['item_name'] }}</div>
                                    <small class="text-muted">Available: {{ number_format($item['available_stock'], 2) }}</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold">{{ number_format($item['quantity'], 2) }}</div>
                                    <small class="text-muted">{{ $item['unit'] ?? 'pcs' }}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Transfer Summary -->
                    <div class="row g-2 p-3 rounded bg-light border">
                        <div class="col-6">
                            <div class="text-muted small">Total Items:</div>
                            <div class="fw-semibold h5 mb-0">{{ count($items) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Total Quantity:</div>
                            <div class="fw-semibold h5 mb-0 text-primary">{{ number_format(collect($items)->sum('quantity'), 2) }}</div>
                        </div>
                    </div>

                    <!-- Warning Note -->
                    <div class="alert alert-info mt-3 p-2 small">
                        <strong>ℹ️ Important:</strong> This transfer will be completed immediately. Stock levels will be updated in real-time.
                    </div>

                    <!-- Stock Movement Preview -->
                    <div class="alert alert-success mt-2 p-2 small">
                        <strong>📦 Stock Movement:</strong>
                        <ul class="mb-0 mt-1 small">
                            <li><strong>Deduct</strong> items from {{ $this->sourceLocationName }}</li>
                            <li><strong>Add</strong> items to {{ $this->destinationLocationName }}</li>
                            <li><strong>Record</strong> complete audit trail</li>
                            <li><strong>Update</strong> inventory levels immediately</li>
                        </ul>
                    </div>
                </div>
                
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        ❌ Cancel
                    </button>
                    <button type="button" class="btn btn-success" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">
                            <i class="fas fa-check me-1"></i>✅ Complete Transfer
                        </span>
                        <span wire:loading wire:target="save">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Processing Transfer...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Enhanced transfer form initialization with comprehensive logging
        console.log('Transfer create form loaded - Enhanced version');
        
        // Notification system
        function showNotification(type, message, duration = 5000) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-dismiss after duration
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, duration);
        }
        
        // Enhanced Livewire event handling
        window.addEventListener('livewire:init', () => {
            console.log('Livewire initialized for transfer creation');
        });
        
        // Listen for custom notification events
        Livewire.on('showNotification', (event) => {
            showNotification(event.type || 'info', event.message || 'Action completed');
        });
        
        // Enhanced error handling
        window.addEventListener('livewire:error', (event) => {
            console.error('Transfer form Livewire error:', event.detail);
            showNotification('danger', '❌ An error occurred. Please check your input and try again.');
        });
        
        // Enhanced activity logging
        window.addEventListener('livewire:start', (event) => {
            if (event.detail.event === 'save') {
                console.log('Transfer save process initiated');
                showNotification('info', '⏳ Processing transfer...');
            } else if (event.detail.event === 'addItem') {
                console.log('Adding item to transfer');
            } else if (event.detail.event === 'removeItem') {
                console.log('Removing item from transfer');
            }
        });
        
        window.addEventListener('livewire:finish', (event) => {
            if (event.detail.event === 'save') {
                console.log('Transfer save process completed');
            }
        });
        
        // Enhanced modal handling
        Livewire.on('closeModal', () => {
            console.log('Closing transfer confirmation modal');
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmTransferModal'));
            if (modal) {
                modal.hide();
            }
        });
        
        // Form validation helpers
        function validateForm() {
            const sourceId = document.querySelector('[wire\\:model\\.live="form.source_id"]')?.value;
            const destinationId = document.querySelector('[wire\\:model\\.live="form.destination_id"]')?.value;
            
            if (!sourceId) {
                showNotification('warning', '⚠️ Please select a source location');
                return false;
            }
            
            if (!destinationId) {
                showNotification('warning', '⚠️ Please select a destination location');
                return false;
            }
            
            return true;
        }
        
        // Auto-save form state to localStorage for recovery
        function saveFormState() {
            const formData = {
                source_type: document.querySelector('[wire\\:model\\.live="form.source_type"]')?.value,
                source_id: document.querySelector('[wire\\:model\\.live="form.source_id"]')?.value,
                destination_type: document.querySelector('[wire\\:model\\.live="form.destination_type"]')?.value,
                destination_id: document.querySelector('[wire\\:model\\.live="form.destination_id"]')?.value,
                note: document.querySelector('[wire\\:model="form.note"]')?.value,
                timestamp: new Date().toISOString()
            };
            
            localStorage.setItem('transfer_create_form_backup', JSON.stringify(formData));
        }
        
        // Save form state periodically
        setInterval(saveFormState, 30000); // Every 30 seconds
        
        // Cleanup on successful submit
        Livewire.on('transferCreated', () => {
            localStorage.removeItem('transfer_create_form_backup');
            showNotification('success', '🎉 Transfer created successfully!');
        });
        
        console.log('Transfer form enhanced initialization complete');
    });
</script>
@endpush