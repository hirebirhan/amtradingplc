<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 font-weight-bold">
            {{ __('Create Expense') }}
        </h2>
    </x-slot>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">New Expense</h5>
        </div>
        <div class="card-body">
            <div class="coming-soon">
                <div class="mb-4">
                    <i class="fas fa-money-bill fa-3x text-primary"></i>
                </div>
                <h3>Expense Form Coming Soon</h3>
                <p>This functionality is coming soon. Check back later!</p>
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-primary mt-3">
                    <i class="fas fa-arrow-left me-2"></i> Back to Expenses
                </a>
            </div>
        </div>
    </div>
</x-app-layout>