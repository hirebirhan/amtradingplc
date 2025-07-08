@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <span class="display-1 text-danger">
                            <i class="fas fa-exclamation-circle"></i>
                        </span>
                    </div>
                    
                    <h1 class="h3 mb-3">{{ $message ?? 'An error occurred' }}</h1>
                    
                    <p class="text-muted mb-4">
                        @if(config('app.debug') && isset($error))
                            {{ $error }}
                        @else
                            We're experiencing some technical difficulties at the moment. 
                            Please try again later or contact support if the problem persists.
                        @endif
                    </p>
                    
                    <div class="mb-4">
                        <h6 class="text-muted">Error Code: {{ $code ?? 500 }}</h6>
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