{{-- Error and Success Alerts --}}
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
