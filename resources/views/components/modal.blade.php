@props(['id', 'maxWidth', 'title' => null, 'footer' => null])

@php
$id = $id ?? md5($attributes->wire('model'));
$maxWidth = $maxWidth ?? '2xl';

$maxWidthClass = match ($maxWidth) {
    'sm' => 'modal-sm',
    'md' => '',
    'lg' => 'modal-lg',
    'xl' => 'modal-xl',
    '2xl' => 'modal-xl',
    'fullscreen' => 'modal-fullscreen',
    default => '',
};
@endphp

<div
    x-data="{ show: @entangle($attributes->wire('model')).defer }"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    id="{{ $id }}"
    class="modal fade"
    tabindex="-1"
    aria-hidden="true"
    x-on:show.bs.modal.window="if ($event.target.id === '{{ $id }}') show = true"
    x-on:hidden.bs.modal.window="if ($event.target.id === '{{ $id }}') show = false"
    x-init="$watch('show', value => {
        if (value) {
            $nextTick(() => {
                const modal = new bootstrap.Modal(document.getElementById('{{ $id }}'));
                modal.show();
            });
        } else {
            bootstrap.Modal.getInstance(document.getElementById('{{ $id }}')).hide();
        }
    })"
>
    <div class="modal-dialog modal-dialog-centered {{ $maxWidthClass }}">
        <div class="modal-content">
            @if ($title)
                <div class="modal-header">
                    <h5 class="modal-title">{{ $title }}</h5>
                    <button type="button" class="btn-close" x-on:click="show = false" aria-label="Close"></button>
                </div>
            @endif

            <div class="modal-body">
                {{ $content ?? $slot }}
            </div>

            @if ($footer)
                <div class="modal-footer">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div> 