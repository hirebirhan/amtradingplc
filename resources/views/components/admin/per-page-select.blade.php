@props([
    'model' => 'perPage',
    'options' => [10, 25, 50, 100],
    'size' => 'sm',
    'labelFormat' => '%d',
    'classes' => '',
])

<select class="form-select form-select-{{ $size }} {{ $classes }}" wire:model.live="{{ $model }}">
    @foreach($options as $option)
        <option value="{{ $option }}">{{ sprintf($labelFormat, $option) }}</option>
    @endforeach
</select> 