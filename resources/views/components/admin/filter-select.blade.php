@props([
    'label' => null,
    'options' => [],
    'model' => '',
    'placeholder' => 'Select option',
    'allowEmpty' => true,
    'emptyLabel' => 'All',
    'size' => 'md',
    'classes' => '',
])

<select 
    class="form-select form-select-{{ $size }} {{ $classes }}" 
    wire:model.live="{{ $model }}"
    {{ $attributes }}
>
    @if($allowEmpty)
        <option value="">{{ $emptyLabel }}</option>
    @endif
    
    @foreach($options as $value => $label)
        @if(is_array($label))
            <option value="{{ $label['id'] ?? $value }}">{{ $label['name'] ?? $label }}</option>
        @else
            <option value="{{ $value }}">{{ $label }}</option>
        @endif
    @endforeach
</select> 