@props([
    'type' => 'text',
    'name' => null,
    'id' => null,
    'label' => null,
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
    'rows' => null, // for textarea
    'options' => [], // for select
    'multiple' => false, // for select
    'min' => null,
    'max' => null,
    'step' => null,
])

@php
    $id = $id ?? $name;
    $errorClass = $error ? 'is-invalid' : '';
    $fieldClass = $type === 'select' ? 'form-select' : 'form-control';
    $fieldClass .= $size ? ($type === 'select' ? ' form-select-' . $size : ' form-control-' . $size) : '';
    $fieldClass .= ' ' . $errorClass;
    
    $wrapperClass = 'mb-3';
    if ($icon) {
        $wrapperClass .= ' has-icon has-icon-' . $iconPosition;
    }
@endphp

<div class="{{ $wrapperClass }}">
    @if($label)
        <label for="{{ $id }}" class="form-label fw-medium">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    <div class="{{ $icon ? 'input-group' : '' }}">
        @if($icon && $iconPosition === 'left')
            <span class="input-group-text">
                <i class="bi {{ $icon }}"></i>
            </span>
        @endif
        
        @if($type === 'textarea')
            <textarea
                name="{{ $name }}"
                id="{{ $id }}"
                {{ $attributes->merge(['class' => $fieldClass]) }}
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                @if($required) required @endif
                @if($disabled) disabled @endif
                @if($readonly) readonly @endif
                @if($autofocus) autofocus @endif
                @if($rows) rows="{{ $rows }}" @endif
            >{{ $value }}</textarea>
        @elseif($type === 'select')
            <select
                name="{{ $name }}"
                id="{{ $id }}"
                {{ $attributes->merge(['class' => $fieldClass]) }}
                @if($required) required @endif
                @if($disabled) disabled @endif
                @if($multiple) multiple @endif
            >
                @if($placeholder && !$multiple)
                    <option value="">{{ $placeholder }}</option>
                @endif
                
                @foreach($options as $optionValue => $optionLabel)
                    @if(is_array($optionLabel))
                        <optgroup label="{{ $optionValue }}">
                            @foreach($optionLabel as $groupValue => $groupLabel)
                                <option value="{{ $groupValue }}" {{ $value == $groupValue ? 'selected' : '' }}>
                                    {{ $groupLabel }}
                                </option>
                            @endforeach
                        </optgroup>
                    @else
                        <option value="{{ $optionValue }}" {{ $value == $optionValue ? 'selected' : '' }}>
                            {{ $optionLabel }}
                        </option>
                    @endif
                @endforeach
            </select>
        @else
            <input
                type="{{ $type }}"
                name="{{ $name }}"
                id="{{ $id }}"
                value="{{ $value }}"
                {{ $attributes->merge(['class' => $fieldClass]) }}
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                @if($required) required @endif
                @if($disabled) disabled @endif
                @if($readonly) readonly @endif
                @if($autofocus) autofocus @endif
                @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                @if($min) min="{{ $min }}" @endif
                @if($max) max="{{ $max }}" @endif
                @if($step) step="{{ $step }}" @endif
            >
        @endif
        
        @if($icon && $iconPosition === 'right')
            <span class="input-group-text">
                <i class="bi {{ $icon }}"></i>
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