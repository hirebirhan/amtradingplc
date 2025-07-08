<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 font-weight-bold">
            {{ __('Purchase Details') }}
        </h2>
    </x-slot>

    <livewire:purchases.show :purchase="$purchase" />
</x-app-layout>