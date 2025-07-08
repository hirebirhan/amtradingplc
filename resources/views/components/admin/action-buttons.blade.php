@props([
    'id' => null,
    'name' => null,
    'showRoute' => null,
    'editRoute' => null,
    'deleteAction' => null,
    'deleteConfirmMessage' => 'Are you sure you want to delete this item?',
    'extraActions' => null,
    'asRow' => false,
])

<div class="btn-group {{ $asRow ? 'd-block text-end' : '' }}">
    @if($showRoute)
        <a href="{{ $showRoute }}" class="btn btn-sm btn-outline-info border-0" title="View">
            <i class="fas fa-eye"></i>
        </a>
    @endif
    
    @if($editRoute)
        <a href="{{ $editRoute }}" class="btn btn-sm btn-outline-primary border-0" title="Edit">
            <i class="fas fa-edit"></i>
        </a>
    @endif
    
    @if($deleteAction)
        <button 
            type="button" 
            class="btn btn-sm btn-outline-danger border-0" 
            title="Delete"
            {{ $name ? "onclick=\"confirmDelete('$id', '$name')\"" : "wire:click=\"{$deleteAction}({$id})\"" }}
        >
            <i class="fas fa-trash"></i>
        </button>
    @endif
    
    {{ $slot }}
</div>

@if($name && !$deleteAction)
<script>
function confirmDelete(id, name) {
    if (confirm('Delete ' + name + '? ' + '{{ $deleteConfirmMessage }}')) {
        @this.call('delete', id);
    }
}
</script>
@endif 