<div>
    <div class="position-relative">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            wire:keydown.escape="$set('showDropdown', false)"
            wire:keydown.arrow-down="$set('showDropdown', true)"
            wire:keydown.arrow-up="$set('showDropdown', true)"
            wire:click="$set('showDropdown', true)"
            wire:focus="$set('showDropdown', true)"
            placeholder="{{ $placeholder }}"
            class="form-control @error('selected') is-invalid @enderror"
            autocomplete="off"
        />
        <input type="hidden" wire:model="selected" />

        @if($showDropdown && count($filteredCustomers) > 0)
            <ul class="list-unstyled mb-0 bg-body border rounded shadow-sm position-absolute w-100 mt-1" style="z-index: 1050;">
                @foreach($filteredCustomers as $customer)
                    <li class="py-2 px-3 cursor-pointer hover-bg-light border-bottom" wire:click="selectCustomer({{ $customer['id'] }})">
                        <div class="fw-medium text-body">{{ $customer['name'] }}</div>
                        <div class="small text-muted">
                            @if($customer['email'])
                                <span>{{ $customer['email'] }}</span>
                            @endif
                            @if($customer['phone'])
                                <span>• {{ $customer['phone'] }}</span>
                            @endif
                            @if($customer['balance'] > 0)
                                <span>• Balance: ETB {{ number_format($customer['balance'], 2) }}</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @elseif($showDropdown && $search && strlen($search) >= $minimumCharacters)
            <div class="position-absolute w-100 mt-1 bg-white rounded shadow-sm border" style="z-index: 1050;">
                <div class="py-3 px-3 small text-muted">
                    No customers found matching "{{ $search }}"
                </div>
            </div>
        @endif

        @error('selected')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>