<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 font-weight-bold">
            {{ __('Sale Details') }}
        </h2>
    </x-slot>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Sale #{{ $sale->reference_no ?? 'N/A' }}</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                This view file is not used since the route points directly to the Livewire component.
                <br>
                <small>Route: <code>Route::get('/{sale}', App\Livewire\Sales\Show::class)</code></small>
            </div>
            <a href="{{ route('admin.sales.index') }}" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to Sales
            </a>
        </div>
    </div>
</x-app-layout>