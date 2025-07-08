@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <span class="display-1 text-warning">
                            <i class="fas fa-map-signs"></i>
                        </span>
                    </div>
                    
                    <h1 class="h3 mb-3">Page Not Found</h1>
                    
                    <p class="text-muted mb-4">
                        The page you're looking for doesn't exist or has been moved.
                    </p>
                    
                    <div class="alert alert-info" role="alert">
                        <h6 class="alert-heading mb-1"><i class="fas fa-info-circle me-2"></i>What you can try:</h6>
                        <ul class="mb-0 text-start">
                            <li>Check the URL for typos</li>
                            <li>Return to the previous page</li>
                            <li>Go to the homepage and navigate from there</li>
                        </ul>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="text-muted">Error Code: 404</h6>
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