@props([
    'responsive' => true,
    'hover' => true,
    'striped' => true,
    'withPagination' => true,
    'emptyText' => 'No records found',
    'emptyIcon' => 'fa-folder-open',
    'emptyActionText' => null,
    'emptyActionRoute' => null,
    'emptyActionIcon' => 'fa-plus',
    'paginationItemName' => 'records',
    'paginationData' => null,
    'actions' => null,
    'tableClasses' => '',
])

<div>
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
        <div class="d-flex justify-content-between align-items-center p-3 border-top">
            <div class="text-muted small">
                Showing {{ $paginationData->firstItem() ?? 0 }} to {{ $paginationData->lastItem() ?? 0 }} 
                of {{ $paginationData->total() }} {{ $paginationItemName }}
            </div>
            <div>
                {{ $paginationData->links() }}
            </div>
        </div>
    @endif
</div> 