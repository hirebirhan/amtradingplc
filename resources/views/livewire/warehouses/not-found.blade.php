<div>
    <div class="container-fluid">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="card-body p-5 text-center">
                        <div class="mb-4">
                            <div class="rounded-circle bg-danger bg-opacity-10 p-4 d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 100px; height: 100px">
                                <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                            </div>
                            <h3 class="mb-3">Warehouse Not Found</h3>
                            <p class="text-muted mb-4">The warehouse you are trying to access does not exist or has been deleted.</p>
                            <a href="{{ route('admin.warehouses.index') }}" class="btn btn-primary px-4 py-2">
                                <i class="fas fa-arrow-left me-2"></i> Return to Warehouses
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 