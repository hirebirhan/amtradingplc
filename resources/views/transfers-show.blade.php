<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 font-weight-bold">
            {{ __('Transfer Details') }}
        </h2>
    </x-slot>

    <livewire:transfers.show :transfer="$transfer" />
</x-app-layout>