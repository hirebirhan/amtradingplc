@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <span class="display-1 text-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                    </div>
                    
                    <h1 class="h3 mb-3">Server Error</h1>
                    
                    <p class="text-muted mb-4">
                        @if(config('app.debug') && isset($exception))
                            {{ $exception->getMessage() }}
                        @else
                            We're sorry, but something went wrong on our server. Our technical team has been notified and is working to fix the issue.
                        @endif
                    </p>
                    
                    <div class="alert alert-info" role="alert">
                        <h6 class="alert-heading mb-1"><i class="fas fa-info-circle me-2"></i>What you can try:</h6>
                        <ul class="mb-0 text-start">
                            <li>Refresh the page</li>
                            <li>Try again in a few minutes</li>
                            <li>Clear your browser cache</li>
                            <li>Check your internet connection</li>
                        </ul>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="text-muted">Error Code: 500</h6>
                        @if(config('app.debug') && isset($exception))
                            <small class="text-muted d-block mt-2">{{ get_class($exception) }}</small>
                        @endif
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Go Back
                        </a>
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