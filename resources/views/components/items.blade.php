@props(['title' => 'Items'])

<x-partials.main :title="$title">
    {{ $slot }}
</x-partials.main>