@props([
    'type' => 'text',
    'label' => null,
    'name' => null,
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'help' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'autofocus' => false,
    'autocomplete' => null,
    'size' => null, // sm, lg
    'icon' => null,
    'iconPosition' => 'left', // left, right
])

@php
    $id = $id ?? $name;
    $errorClass = $error ? 'is-invalid' : '';
    $inputClass = 'form-control';
    $inputClass .= $size ? ' form-control-' . $size : '';
    $inputClass .= $errorClass;
    
    $wrapperClass = 'mb-3';
    if ($icon) {
        $wrapperClass .= ' has-icon has-icon-' . $iconPosition;
    }
@endphp

<div class="{{ $wrapperClass }}">
    @if($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    <div class="input-group">
        @if($icon && $iconPosition === 'left')
            <span class="input-group-text">
                <i class="fas {{ $icon }}"></i>
            </span>
        @endif
        
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ $value }}"
            {{ $attributes->merge(['class' => $inputClass]) }}
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            @if($autofocus) autofocus @endif
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if($type === 'tel') oninput="this.value = this.value.replace(/[^0-9+]/g, '')" @endif
        >
        
        @if($icon && $iconPosition === 'right')
            <span class="input-group-text">
                <i class="fas {{ $icon }}"></i>
            </span>
        @endif
        
        @if($error)
            <div class="invalid-feedback">
                {{ $error }}
            </div>
        @endif
    </div>
    
    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div> 