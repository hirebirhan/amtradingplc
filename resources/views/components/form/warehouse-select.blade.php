@props([
    'name' => 'warehouse_id',
    'id' => null,
    'label' => 'Warehouse',
    'placeholder' => 'Select Warehouse',
    'selected' => null,
    'required' => true,
    'disabled' => false,
    'livewire' => false,
    'error' => null,
    'size' => null,
    'help' => null,
    'warehouses' => null,
    'showLabel' => true,
    'model' => null,
])

@php
    $id = $id ?? $name;
    $errorClass = $error ? 'is-invalid' : '';
    $selectClass = 'form-select';
    $selectClass .= $size ? ' form-select-' . $size : '';
    $selectClass .= $errorClass;
    
    // Fetch warehouses if not provided
    if (!$warehouses) {
        $warehouses = \App\Models\Warehouse::orderBy('name', 'asc')->get();
    }
@endphp

<div class="mb-3">
    @if($showLabel)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if($required) <span class="text-danger">*</span> @endif
        </label>
    @endif

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
        @foreach($warehouses as $warehouse)
            <option value="{{ $warehouse->id }}" {{ $selected == $warehouse->id ? 'selected' : '' }}>
                {{ $warehouse->name }}
            </option>
        @endforeach
    </select>
    
    @if($error)
        <div class="invalid-feedback">{{ $error }}</div>
    @endif
    
    @if($help)
        <div id="{{ $id }}-help" class="form-text">{{ $help }}</div>
    @endif
</div> 