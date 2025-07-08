@props([
    'withCard' => true,
    'filterClass' => 'mb-4',
])

<div class="{{ $withCard ? 'card-body p-4' : '' }}">
    <div class="filter-section p-3 border rounded-3 bg-light bg-opacity-25 {{ $filterClass }}">
        <div class="row g-3 align-items-center">
            {{ $slot }}
        </div>
    </div>
</div> 