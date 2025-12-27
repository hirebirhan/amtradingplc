<div class="modal fade" id="clearCartModal" tabindex="-1" aria-labelledby="clearCartModalLabel" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="clearCartModalLabel">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Clear Cart
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to clear all items from the cart? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" wire:click="clearCart" data-bs-dismiss="modal">
                    <i class="bi bi-trash-fill me-1"></i>Clear All
                </button>
            </div>
        </div>
    </div>
</div>
