<x-app-layout>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 mt-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ $feature }} - Coming Soon</h3>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <i class="fas fa-tools fa-4x text-muted mb-3"></i>
                            <h4>This feature is currently under development</h4>
                            <p class="text-muted">The {{ $feature }} module will be available in a future update.</p>
                        </div>
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i> Return to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>