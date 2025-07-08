@props([
    'rows' => [],
    'loading' => false,
    'filterable' => true,
    'pagination' => true,
    'actions' => true,
    'emptyText' => 'No data available',
    'itemsPerPage' => 10,
    'currentPage' => 1,
    'totalItems' => 0,
])

<div {{ $attributes->merge(['class' => 'card border shadow-sm']) }}>
    @if(isset($header))
        <div class="card-header bg-transparent border-bottom">
            {{ $header }}
        </div>
    @endif
    
    @if($filterable)
        <div class="{{ isset($header) ? 'border-bottom bg-light bg-opacity-50 p-3' : 'card-header bg-transparent py-3' }}">
            {{ $filters ?? '' }}
        </div>
    @endif
    
    <div class="table-responsive">
        <table class="table align-middle mb-0" role="table">
            <thead>
                <tr>
                    {{ $columns ?? '' }}
                </tr>
            </thead>
            <tbody>
                @if($loading)
                    @for($i = 0; $i < 5; $i++)
                        <tr>
                            @for($j = 0; $j < 6; $j++)
                                <td class="px-3 py-3">
                                    <div class="placeholder-glow">
                                        <span class="placeholder col-{{ rand(4, 8) }}"></span>
                                    </div>
                                </td>
                            @endfor
                            
                            @if($actions)
                                <td class="text-end px-3 py-3">
                                    <div class="placeholder-glow">
                                        <span class="placeholder col-4"></span>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endfor
                @elseif($totalItems > 0)
                    {{ $slot }}
                @else
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <div class="py-4">
                                <i class="fas fa-search fa-2x mb-3 text-neutral-300"></i>
                                <p>{{ $emptyText }}</p>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    
    @if($pagination && !$loading && $totalItems > 0)
        <div class="card-footer bg-transparent py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{ ($currentPage - 1) * $itemsPerPage + 1 }} to 
                    {{ min($currentPage * $itemsPerPage, $totalItems) }} of {{ $totalItems }} entries
                </div>
                
                {{ $pagination ?? '' }}
            </div>
        </div>
    @endif
</div> 