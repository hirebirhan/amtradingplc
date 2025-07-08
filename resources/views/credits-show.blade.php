<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 font-weight-bold">
            {{ __('Credit Details') }}
        </h2>
    </x-slot>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Credit #{{ $credit->reference_no }}</h5>
        </div>
        <div class="card-body">
            <div class="coming-soon">
                <div class="mb-4">
                    <i class="fas fa-credit-card fa-3x text-primary"></i>
                </div>
                <h3>Credit Details Coming Soon</h3>
                <p>This functionality is coming soon. Check back later!</p>
                <a href="{{ route('admin.credits.index') }}" class="btn btn-primary mt-3">
                    <i class="fas fa-arrow-left me-2"></i> Back to Credits
                </a>
            </div>
        </div>
    </div>
</x-app-layout>