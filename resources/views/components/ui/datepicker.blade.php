@props([
    'name' => '',
    'id' => null,
    'value' => '',
    'label' => null,
    'placeholder' => 'Select date',
    'required' => false,
    'disabled' => false,
    'helper' => null,
    'error' => null,
    'wired' => true,
    'format' => 'YYYY-MM-DD',
    'minDate' => null,
    'maxDate' => null,
    'disabledDates' => [],
    'enabledDates' => [],
    'icons' => true,
])

@php
    $id = $id ?? $name;
    $uniqueId = 'date_' . $id . '_' . uniqid();
    $hasError = $error || $errors->has($name);
    $errorClass = $hasError ? 'is-invalid' : '';
    $requiredClass = $required ? 'required' : '';
    $inputClasses = "form-control datepicker-input $errorClass";
    
    // Handle wire model binding
    $wireModel = $wired && $name ? "wire:model.defer=\"$name\"" : '';
@endphp

<div {{ $attributes->merge(['class' => 'mb-3']) }} 
     x-data="{
        value: '{{ $value }}',
        init() {
            const tempusDominus = new tempusDominus.TempusDominus(document.getElementById('{{ $uniqueId }}'), {
                localization: {
                    locale: document.documentElement.lang || 'en',
                    format: '{{ $format }}'
                },
                display: {
                    icons: {
                        type: 'icons',
                        time: 'fa-solid fa-clock',
                        date: 'fa-solid fa-calendar',
                        up: 'fa-solid fa-arrow-up',
                        down: 'fa-solid fa-arrow-down',
                        previous: 'fa-solid fa-chevron-left',
                        next: 'fa-solid fa-chevron-right',
                        today: 'fa-solid fa-calendar-check',
                        clear: 'fa-solid fa-trash',
                        close: 'fa-solid fa-xmark'
                    },
                    buttons: {
                        today: true,
                        clear: true,
                        close: true
                    }
                },
                restrictions: {
                    @if($minDate)
                    minDate: '{{ $minDate }}',
                    @endif
                    @if($maxDate)
                    maxDate: '{{ $maxDate }}',
                    @endif
                    @if(count($disabledDates) > 0)
                    disabledDates: {!! json_encode($disabledDates) !!},
                    @endif
                    @if(count($enabledDates) > 0)
                    enabledDates: {!! json_encode($enabledDates) !!},
                    @endif
                }
            });
            
            // Handle model updates
            @if($wired)
            this.$watch('value', function(value) {
                @this.set('{{ $name }}', value);
            });
            @endif
            
            // Cleanup
            this.$cleanup = () => {
                tempusDominus.dispose();
            }
        }
     }"
>
    @if($label)
        <label for="{{ $uniqueId }}-input" class="form-label {{ $requiredClass }}">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    <div class="input-group" id="{{ $uniqueId }}">
        <input 
            type="text" 
            id="{{ $uniqueId }}-input" 
            name="{{ $name }}" 
            {!! $wireModel !!}
            x-model="value"
            placeholder="{{ $placeholder }}" 
            class="{{ $inputClasses }}" 
            @if($required) required @endif
            @if($disabled) disabled @endif
            autocomplete="off"
        >
        
        @if($icons)
            <span class="input-group-text" data-td-toggle>
                <i class="fas fa-calendar"></i>
            </span>
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
    <link href="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.7.13/dist/css/tempus-dominus.min.css" rel="stylesheet">
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.7.13/dist/js/tempus-dominus.min.js"></script>
    @endpush
@endonce 