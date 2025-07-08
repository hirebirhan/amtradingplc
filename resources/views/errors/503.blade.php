@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <span class="display-1 text-warning">
                            <i class="fas fa-database"></i>
                        </span>
                    </div>
                    
                    <h1 class="h3 mb-3">Service Temporarily Unavailable</h1>
                    
                    <p class="text-muted mb-4">
                        We're experiencing technical difficulties with our database connection. Our team has been notified and is working to restore service as quickly as possible.
                    </p>
                    
                    <div class="alert alert-info" role="alert">
                        <h6 class="alert-heading mb-1"><i class="fas fa-info-circle me-2"></i>What you can try:</h6>
                        <ul class="mb-0 text-start">
                            <li>Wait a few minutes and try refreshing the page</li>
                            <li>Try again later</li>
                            <li>If the problem persists, please contact support</li>
                        </ul>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="text-muted">Error Code: 503</h6>
                        <small class="text-muted">Service Unavailable</small>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <button onclick="window.location.reload()" class="btn btn-outline-secondary">
                            <i class="fas fa-sync-alt me-2"></i> Refresh Page
                        </button>
                        <a href="{{ url('/') }}" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i> Go to Homepage
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 