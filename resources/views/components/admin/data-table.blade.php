@props([
    'responsive' => true,
    'hover' => true,
    'striped' => false,
    'withPagination' => true,
    'emptyText' => 'No records found',
    'emptyIcon' => 'bi-folder-x',
    'emptyActionText' => null,
    'emptyActionRoute' => null,
    'emptyActionIcon' => 'bi-plus-lg',
    'paginationItemName' => 'records',
    'paginationData' => null,
    'actions' => null,
    'tableClasses' => 'align-middle mb-0',
    'cardWrapper' => true,
])

@if($cardWrapper)
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
@endif

        <!-- Table Container -->
        @if($responsive)
            <div class="table-responsive">
        @endif
        
        <table class="table {{ $hover ? 'table-hover' : '' }} {{ $striped ? 'table-striped' : '' }} {{ $tableClasses }}">
            {{ $header }}
            
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
        
        @if($responsive)
            </div>
        @endif
        
        <!-- Empty State -->
        @if(isset($empty))
            {{ $empty }}
        @endif
        
        <!-- Pagination -->
        @if($withPagination && $paginationData && $paginationData->hasPages())
            <div class="border-top px-4 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <div class="small text-secondary">
                        Showing {{ $paginationData->firstItem() ?? 0 }} to {{ $paginationData->lastItem() ?? 0 }} 
                        of {{ $paginationData->total() }} {{ $paginationItemName }}
                    </div>
                    <div>
                        {{ $paginationData->links() }}
                    </div>
                </div>
            </div>
        @endif

@if($cardWrapper)
    </div>
</div>
@endif 