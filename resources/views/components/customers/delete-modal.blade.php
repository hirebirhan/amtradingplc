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
            console.log('Stored customer ID from window event:', customerIdToDelete);
            
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
        
        // Also listen for Livewire dispatch events
        Livewire.on('showDeleteWarnings', (event) => {
            console.log('Livewire showDeleteWarnings event received:', event);
            
            // Handle both array and object formats
            const data = Array.isArray(event) ? event[0] : event;
            const { customerName, customerId, hasSales, hasBalance } = data;
            
            // Store customer ID for deletion
            customerIdToDelete = customerId;
            console.log('Stored customer ID:', customerIdToDelete);
            
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
        
        // Also listen for Livewire dispatch events
        Livewire.on('customerDeleted', () => {
            console.log('Livewire customerDeleted event received');
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
                    // Call the delete method on the Livewire component
                    try {
                        // Find the Livewire component and call delete method
                        const wireElement = document.querySelector('[wire\\:id]');
                        if (wireElement) {
                            const componentId = wireElement.getAttribute('wire:id');
                            const component = Livewire.find(componentId);
                            if (component) {
                                component.call('delete', customerIdToDelete);
                            } else {
                                // Fallback to dispatch event
                                Livewire.dispatch('deleteConfirmed', { customerId: customerIdToDelete });
                            }
                        } else {
                            // Final fallback
                            Livewire.dispatch('deleteConfirmed', { customerId: customerIdToDelete });
                        }
                    } catch (error) {
                        console.error('Error calling delete method:', error);
                        // Emergency fallback
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
    
    // Additional error handling for modal initialization
    if (!deleteModal) {
        console.error('Failed to initialize delete modal');
    }
</script>
@endpush 