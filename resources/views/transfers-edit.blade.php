<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 font-weight-bold">
            {{ __('Edit Transfer') }}
        </h2>
    </x-slot>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Edit Transfer #{{ $transfer->reference_no }}</h5>
        </div>
        <div class="card-body">
            <div class="coming-soon">
                <div class="mb-4">
                    <i class="fas fa-exchange-alt fa-3x text-primary"></i>
                </div>
                <h3>Transfer Edit Form Coming Soon</h3>
                <p>This functionality is coming soon. Check back later!</p>
                <a href="{{ route('transfers.index') }}" class="btn btn-primary mt-3">
                    <i class="fas fa-arrow-left me-2"></i> Back to Transfers
                </a>
            </div>
        </div>
    </div>
</x-app-layout>