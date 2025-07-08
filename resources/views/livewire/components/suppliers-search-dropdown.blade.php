<div class="suppliers-search-dropdown">
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

        @if($showDropdown && count($filteredSuppliers) > 0)
            <div class="position-absolute w-100 mt-1 bg-white rounded shadow-sm border" style="max-height: 300px; overflow-y: auto; z-index: 1050;">
                <ul class="list-unstyled mb-0">
                    @foreach($filteredSuppliers as $supplier)
                        <li class="py-2 px-3" style="cursor: pointer;" 
                            wire:click="selectSupplier({{ $supplier['id'] }})" 
                            onmouseover="this.classList.add('bg-light')" 
                            onmouseout="this.classList.remove('bg-light')">
                            <div class="fw-medium">{{ $supplier['name'] }}</div>
                            <div class="small text-muted">
                                @if($supplier['email'])
                                    <span>{{ $supplier['email'] }}</span>
                                @endif
                                @if($supplier['phone'])
                                    <span>@if($supplier['email']) • @endif{{ $supplier['phone'] }}</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @elseif($showDropdown && $search && strlen($search) >= $minimumCharacters)
            <div class="position-absolute w-100 mt-1 bg-white rounded shadow-sm border" style="z-index: 1050;">
                <div class="py-3 px-3 small text-muted">
                    No suppliers found matching "{{ $search }}"
                </div>
            </div>
        @endif

        @error('selected')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>