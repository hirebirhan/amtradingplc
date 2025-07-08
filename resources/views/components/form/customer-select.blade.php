@props([
    'name' => 'customer_id',
    'id' => null,
    'label' => 'Customer',
    'placeholder' => 'Select Customer',
    'selected' => null,
    'required' => true,
    'disabled' => false,
    'livewire' => false,
    'error' => null,
    'size' => null,
    'help' => null,
    'customers' => null,
    'showLabel' => true,
    'model' => null,
    'searchable' => false,
    'searchTerm' => '',
    'customerSelected' => null,
])

@php
    $id = $id ?? $name;
    $errorClass = $error ? 'is-invalid' : '';
    $selectClass = 'form-select';
    $selectClass .= $size ? ' form-select-' . $size : '';
    $selectClass .= $errorClass;
    
    // Fetch customers if not provided and not searchable
    if (!$customers && !$searchable) {
        $customers = \App\Models\Customer::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();
    }
@endphp

<div class="mb-3">
    @if($showLabel)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if($required) <span class="text-danger">*</span> @endif
        </label>
    @endif

    @if($searchable)
        <div class="position-relative">
            <div class="input-group">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="searchTerm" 
                    class="form-control" 
                    placeholder="Search {{ $label }}..."
                    @if($disabled) disabled @endif
                >
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-filter"></i>
                </button>
                <div class="input-group-text">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            
            @if($customers && $customers->count() > 0 && !empty($searchTerm))
                <div class="position-absolute top-100 start-0 mt-1 w-100 z-1">
                    <div class="list-group shadow-sm rounded" style="max-height: 300px; overflow-y: auto;">
                        @foreach($customers as $customer)
                            <button 
                                type="button" 
                                class="list-group-item list-group-item-action py-2 px-3" 
                                wire:click="selectCustomer({{ $customer->id }})"
                            >
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>{{ $customer->name }}</span>
                                    <small class="text-muted">{{ $customer->phone }}</small>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
            
            @if($customerSelected)
                <div class="mt-2">
                    <div class="p-2 border rounded d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">{{ $customerSelected->name }}</div>
                            <div class="small text-muted">{{ $customerSelected->phone }}</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="clearSelectedCustomer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <input type="hidden" name="{{ $name }}" value="{{ $customerSelected->id }}">
                </div>
            @endif
        </div>
    @else
        <select 
            id="{{ $id }}"
            name="{{ $name }}"
            @if($livewire && $model) wire:model="{{ $model }}" @endif
            class="{{ $selectClass }}"
            @if($disabled) disabled @endif
            @if($required) required @endif
            aria-describedby="{{ $id }}-help"
        >
            <option value="">{{ $placeholder }}</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" {{ $selected == $customer->id ? 'selected' : '' }}>
                    {{ $customer->name }} - {{ $customer->phone }}
                </option>
            @endforeach
        </select>
    @endif
    
    @if($error)
        <div class="invalid-feedback">{{ $error }}</div>
    @endif
    
    @if($help)
        <div id="{{ $id }}-help" class="form-text">{{ $help }}</div>
    @endif
</div> 