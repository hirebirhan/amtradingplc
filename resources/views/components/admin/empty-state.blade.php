@props([
    'icon' => 'fa-folder-open',
    'title' => 'No Records Found',
    'message' => 'No records match your search criteria.',
    'actionRoute' => null,
    'actionText' => 'Create New',
    'actionIcon' => 'fa-plus',
    'showClearFilters' => false,
    'clearAction' => null,
])

<tr>
    <td colspan="10" class="text-center py-5">
        <div class="d-flex flex-column align-items-center justify-content-center">
            <i class="fas {{ $icon }} fa-3x text-muted mb-3"></i>
            <h5>{{ $title }}</h5>
            <p class="text-muted mb-3">{{ $message }}</p>
            
            <div class="d-flex gap-2">
                @if($actionRoute)
                    <a href="{{ $actionRoute }}" class="btn btn-primary">
                        <i class="fas {{ $actionIcon }} me-1"></i> {{ $actionText }}
                    </a>
                @endif
                
                @if($showClearFilters)
                    <button class="btn btn-outline-secondary" wire:click="{{ $clearAction ?? '$set(\'search\', \'\')' }}">
                        <i class="fas fa-times me-1"></i> Clear Filters
                    </button>
                @endif
                
                {{ $slot }}
            </div>
        </div>
    </td>
</tr> 