{{-- Modern SaaS Sales Creation Form --}}
<div>
    {{-- Notification container --}}
    <div id="notification-container" class="position-fixed top-0 end-0 z-3 mt-3 me-3"></div>

    {{-- Main Card Container --}}
    <div class="card border-0 shadow-sm">
        {{-- Header --}}
        <div class="card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Create New Sale</h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary mb-0 small">Add items and complete sale details</span>
                        @php
                            $itemCount = is_countable($items) ? count($items) : 0;
                        @endphp
                        @if($itemCount > 0)
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $itemCount }} item{{ $itemCount > 1 ? 's' : '' }}</span>
                        @endif
                        @if($form['payment_method'] === 'credit_advance')
                            <span class="badge bg-warning">Partial Credit</span>
                        @elseif($form['payment_method'] === 'full_credit')
                            <span class="badge bg-danger">Credit Sale</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Back to Sales</span>
                </a>
            </div>
        </div>

        {{-- Card Body --}}
        <div class="card-body p-0">
            {{-- Error Alerts --}}
            <div class="p-4 pb-0">
                @include('livewire.sales.partials._error-alerts')
            </div>

            {{-- Form Content --}}
            <form id="salesForm" class="p-4 pt-0">
                {{-- Basic Sale Information --}}
                @include('livewire.sales.partials._basic-info-form')

                {{-- Items Section --}}
                @include('livewire.sales.partials._item-selection')

                {{-- Items List --}}
                @include('livewire.sales.partials._items-table')
            </form>
        </div>

        {{-- Card Footer --}}
        <div class="card-footer border-top-0 px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <button type="button" wire:click="cancel" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <div class="d-flex gap-2">
                    @php
                        $itemCount = is_countable($items) ? count($items) : 0;
                    @endphp
                    @if($itemCount > 0)
                        <button type="button" class="btn btn-primary" wire:click="validateAndShowModal" wire:loading.attr="disabled">
                            <i class="bi bi-check-lg me-1"></i>
                            <span wire:loading.remove>Complete Sale</span>
                            <span wire:loading>Processing...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Confirmation Modal --}}
    @include('livewire.sales.partials._confirmation-modal')

    {{-- Stock Warning Modal --}}
    @include('livewire.sales.partials._stock-warning-modal')

    {{-- Clear Cart Modal --}}
    @include('livewire.partials._clear-cart-modal')

    {{-- Initialize Livewire Events --}}
    <script>
        let priceCheckTimeout;
        
        document.addEventListener('livewire:init', () => {
            const confirmationModal = document.getElementById('confirmationModal');
            if (confirmationModal) {
                const modal = new bootstrap.Modal(confirmationModal);
                
                Livewire.on('showConfirmationModal', () => {
                    modal.show();
                });
                
                Livewire.on('closeSaleModal', () => {
                    modal.hide();
                });
            }
            
            // Debounced price checking
            Livewire.on('checkBelowCostPrice', (data) => {
                clearTimeout(priceCheckTimeout);
                priceCheckTimeout = setTimeout(() => {
                    Livewire.dispatch('checkBelowCostPrice', data);
                }, 800); // 800ms delay
            });
        });
    </script>
</div>