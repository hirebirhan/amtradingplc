<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Purchase') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="alert alert-info">
                        <h4>Edit Functionality Coming Soon</h4>
                        <p>Purchase editing is not yet implemented. You can view the purchase details below.</p>
                        <a href="{{ route('admin.purchases.show', $purchase) }}" class="btn btn-primary">
                            View Purchase Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>