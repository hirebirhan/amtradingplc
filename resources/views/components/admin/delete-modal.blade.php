@props([
    'title' => 'Confirm Delete',
    'message' => 'Are you sure you want to delete this item? This action cannot be undone.',
    'id' => 'deleteModal'
])

<div>
    <div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger py-3">
                    <h5 class="modal-title text-white fw-semibold" id="{{ $id }}Label">{{ $title }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle text-danger fa-2x"></i>
                        </div>
                        <div>
                            <p class="mb-3">{{ $message }}</p>
                            
                            @if(isset($slot) && trim($slot))
                                <div class="alert alert-secondary border-0 bg-light small">
                                    {{ $slot }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDelete" wire:click="delete">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let deleteModal = new bootstrap.Modal(document.getElementById('{{ $id }}'));
            
            Livewire.on('showDeleteConfirmation', (data) => {
                deleteModal.show();
            });
            
            Livewire.on('expenseDeleted', () => {
                deleteModal.hide();
            });
        });
    </script>
    @endpush
</div> 