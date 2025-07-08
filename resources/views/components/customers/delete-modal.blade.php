<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the customer <strong id="customerName"></strong>?</p>
                
                <div id="customerWarnings">
                    <!-- Populated dynamically -->
                </div>
                
                <p class="text-danger mt-3">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let customerIdToDelete = null;
        let deleteModal = null;
        
        // Initialize modal
        const modalElement = document.getElementById('deleteModal');
        if (!modalElement) {
            console.error('Delete modal element not found');
            return;
        }
        
        deleteModal = new bootstrap.Modal(modalElement);
        
        // Handle Livewire events
        window.addEventListener('showDeleteWarnings', function(event) {
            console.log('Delete warnings event received:', event.detail);
            
            const data = event.detail[0] || event.detail;
            const { customerName, customerId, hasSales, hasBalance } = data;
            
            // Store customer ID for deletion
            customerIdToDelete = customerId;
            
            // Set customer name
            const customerNameElement = document.getElementById('customerName');
            if (customerNameElement) {
                customerNameElement.textContent = customerName || 'Unknown Customer';
            }
            
            // Clear and populate warnings
            const warningsContainer = document.getElementById('customerWarnings');
            if (warningsContainer) {
                warningsContainer.innerHTML = '';
                
                if (hasSales || hasBalance) {
                    const warningDiv = document.createElement('div');
                    warningDiv.className = 'alert alert-warning';
                    
                    let warningText = '<strong>Warning:</strong> This customer has ';
                    const warnings = [];
                    
                    if (hasSales) warnings.push(`<strong>${hasSales}</strong> related sales`);
                    if (hasBalance) warnings.push(`an outstanding balance of <strong>ETB ${hasBalance}</strong>`);
                    
                    warningText += warnings.join(' and ') + '.';
                    warningDiv.innerHTML = warningText;
                    
                    warningsContainer.appendChild(warningDiv);
                }
            }
            
            // Show the modal
            deleteModal.show();
        });
        
        // Handle customer deletion completion
        window.addEventListener('customerDeleted', function() {
            console.log('Customer deleted event received');
            deleteModal.hide();
            customerIdToDelete = null;
        });
        
        // Handle confirm delete button click
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Delete confirmed for customer ID:', customerIdToDelete);
                
                if (customerIdToDelete) {
                    // Try multiple approaches to ensure deletion works
                    try {
                        // Approach 1: Call Livewire method directly
                        if (window.Livewire) {
                            const component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                            if (component) {
                                component.call('delete', customerIdToDelete);
                            } else {
                                // Fallback: Use global Livewire dispatch
                                Livewire.dispatch('deleteConfirmed', { customerId: customerIdToDelete });
                            }
                        } else {
                            console.error('Livewire not available');
                        }
                    } catch (error) {
                        console.error('Error calling delete method:', error);
                        // Final fallback
                        Livewire.dispatch('deleteConfirmed', { customerId: customerIdToDelete });
                    }
                } else {
                    console.error('No customer ID to delete');
                }
            });
        }
        
        // Clean up when modal is hidden
        modalElement.addEventListener('hidden.bs.modal', function() {
            customerIdToDelete = null;
        });
    });
</script>
@endpush 