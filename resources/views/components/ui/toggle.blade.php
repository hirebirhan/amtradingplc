@props([
    'name' => '',
    'id' => null,
    'value' => false,
    'label' => null,
    'required' => false,
    'disabled' => false,
    'helper' => null,
    'error' => null,
    'wired' => true,
    'color' => 'primary', // primary, success, info, warning, danger
    'size' => 'md', // sm, md, lg
    'labelPosition' => 'right', // left, right
])

@php
    $id = $id ?? $name;
    $hasError = $error || $errors->has($name);
    $errorClass = $hasError ? 'is-invalid' : '';
    $requiredClass = $required ? 'required' : '';
    
    // Colors
    $colors = [
        'primary' => 'form-check-primary',
        'success' => 'form-check-success',
        'info' => 'form-check-info',
        'warning' => 'form-check-warning',
        'danger' => 'form-check-danger',
    ];
    
    // Sizes
    $sizes = [
        'sm' => 'form-switch-sm',
        'md' => '',
        'lg' => 'form-switch-lg',
    ];
    
    $colorClass = $colors[$color] ?? $colors['primary'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    
    // Handle wire model binding
    $wireModel = $wired && $name ? "wire:model.defer=\"$name\"" : '';
@endphp

<div {{ $attributes->merge(['class' => 'mb-3']) }}>
    <div class="form-check form-switch {{ $colorClass }} {{ $sizeClass }} d-flex align-items-center gap-2 {{ $labelPosition === 'left' ? 'flex-row-reverse justify-content-end' : '' }}">
        <input 
            type="checkbox" 
            id="{{ $id }}" 
            name="{{ $name }}" 
            {!! $wireModel !!}
            @if(!$wired) value="1" @if($value) checked @endif @endif
            class="form-check-input {{ $errorClass }}" 
            role="switch"
            @if($required) required @endif
            @if($disabled) disabled @endif
            aria-label="{{ $label ?: $name }}"
        >
        
        @if($label)
            <label for="{{ $id }}" class="form-check-label {{ $requiredClass }} user-select-none">
                {{ $label }}
                @if($required)
                    <span class="text-danger">*</span>
                @endif
            </label>
        @endif
    </div>
    
    @if($helper && !$hasError)
        <div class="form-text text-muted">{{ $helper }}</div>
    @endif
    
    @if($hasError)
        <div class="invalid-feedback d-block">
            {{ $error ?? $errors->first($name) }}
        </div>
    @endif
</div>

@once
    @push('styles')
    <style>
        /* Form switch sizes only, remove color overrides */
        .form-switch.form-switch-sm {
            min-height: 1.2rem;
        }
        .form-switch.form-switch-sm .form-check-input {
            height: 1.2rem;
            width: 2.1rem;
            margin-top: 0.15rem;
        }
        .form-switch.form-switch-lg {
            min-height: 1.8rem;
        }
        .form-switch.form-switch-lg .form-check-input {
            height: 1.8rem;
            width: 3.2rem;
            margin-top: 0.15rem;
        }
        /* Remove all .form-switch.form-check-* color rules */
    </style>
    @endpush
@endonce 