@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => 'Select an option',
    'help' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'multiple' => false,
    'size' => null, // sm, lg
    'icon' => null,
    'iconPosition' => 'left', // left, right
])

@php
    $id = $id ?? $name;
    $errorClass = $error ? 'is-invalid' : '';
    $selectClass = 'form-select';
    $selectClass .= $size ? ' form-select-' . $size : '';
    $selectClass .= $errorClass;
    
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
                <i class="bi {{ $icon }}"></i>
            </span>
        @endif
        
        <select
            name="{{ $name }}"
            id="{{ $id }}"
            {{ $attributes->merge(['class' => $selectClass]) }}
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($multiple) multiple @endif
        >
            @if($placeholder)
                <option value="" disabled {{ $selected === null ? 'selected' : '' }}>
                    {{ $placeholder }}
                </option>
            @endif
            
            @foreach($options as $value => $label)
                @if(is_array($label))
                    <optgroup label="{{ $value }}">
                        @foreach($label as $groupValue => $groupLabel)
                            <option value="{{ $groupValue }}" {{ $selected == $groupValue ? 'selected' : '' }}>
                                {{ $groupLabel }}
                            </option>
                        @endforeach
                    </optgroup>
                @else
                    <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endif
            @endforeach
        </select>
        
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