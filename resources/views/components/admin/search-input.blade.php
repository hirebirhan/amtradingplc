@props([
    'placeholder' => 'Search...',
    'model' => 'search',
    'debounce' => '300ms',
    'clearMethod' => 'clearSearch',
    'classes' => '',
])

<div class="input-group {{ $classes }}">
    <span class="input-group-text bg-transparent border-end-0">
        <i class="fas fa-search text-muted"></i>
    </span>
    <input 
        type="text" 
        class="form-control border-start-0 ps-0" 
        placeholder="{{ $placeholder }}" 
        wire:model.live{{ $debounce ? '.debounce.'.$debounce : '' }}="{{ $model }}"
    >
    @if($clearMethod)
        @if(property_exists($this, $model) && data_get($this, $model))
            <button class="btn btn-outline-secondary border-start-0" type="button" wire:click="{{ $clearMethod }}">
                <i class="fas fa-times"></i>
            </button>
        @endif
    @endif
</div> 