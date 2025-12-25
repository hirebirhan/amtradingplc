{{-- Error and Success Alerts --}}
{{-- Validation Error Alert --}}
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <div class="flex-grow-1">
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-1 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- General Error Alert --}}
@if($errors->has('general'))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <div class="flex-grow-1">
                <strong>Error:</strong> {{ $errors->first('general') }}
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Success Alert --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle me-2"></i>
            <div>{{ session('success') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
