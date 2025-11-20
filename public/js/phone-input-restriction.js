/**
 * Phone Input Restriction
 * Restricts phone number inputs to only allow numbers (0-9) and plus (+) symbol
 */

(function() {
    'use strict';
    
    // Track processed inputs to avoid duplicate event listeners
    const processedInputs = new WeakSet();
    
    // Function to check if an input is a phone input
    function isPhoneInput(input) {
        return input.type === 'tel' || 
               input.getAttribute('inputmode') === 'tel' || 
               (input.pattern && input.pattern.includes('[0-9]')) ||
               (input.placeholder && input.placeholder.includes('+')) ||
               (input.id && input.id.toLowerCase().includes('phone')) ||
               (input.name && input.name.toLowerCase().includes('phone'));
    }
    
    // Function to restrict phone input
    function restrictPhoneInput(input) {
        // Skip if already processed
        if (processedInputs.has(input)) {
            return;
        }
        
        // Mark as processed
        processedInputs.add(input);
        
        // Handle keydown event to prevent invalid characters
        input.addEventListener('keydown', function(e) {
            // Allow: backspace, delete, tab, escape, enter
            if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+Z
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true) ||
                (e.keyCode === 90 && e.ctrlKey === true) ||
                // Allow: home, end, left, right, down, up
                (e.keyCode >= 35 && e.keyCode <= 40)) {
                return;
            }
            
            // Allow: + symbol (only at the beginning or if no + exists)
            if (e.key === '+') {
                if (input.selectionStart === 0 || !input.value.includes('+')) {
                    return;
                }
            }
            
            // Allow: numbers 0-9
            if (e.key >= '0' && e.key <= '9') {
                return;
            }
            
            // Prevent all other keys
            e.preventDefault();
        });

        // Handle input event to clean up content
        input.addEventListener('input', function(e) {
            let value = e.target.value;
            let originalValue = value;
            
            // Remove all characters except numbers and +
            let cleanValue = value.replace(/[^0-9+]/g, '');
            
            // Ensure + is only at the beginning
            if (cleanValue.includes('+')) {
                let parts = cleanValue.split('+');
                if (parts[0] === '') {
                    // + is at the beginning, keep only the first +
                    cleanValue = '+' + parts.slice(1).join('');
                } else {
                    // + is not at the beginning, remove all +
                    cleanValue = cleanValue.replace(/\+/g, '');
                }
            }
            
            // Update the input value if it was changed
            if (originalValue !== cleanValue) {
                e.target.value = cleanValue;
                
                // Trigger Livewire update if it's a Livewire component
                if (e.target.hasAttribute('wire:model') || 
                    e.target.hasAttribute('wire:model.defer') || 
                    e.target.hasAttribute('wire:model.live')) {
                    // Create and dispatch a new input event
                    const inputEvent = new Event('input', { 
                        bubbles: true, 
                        cancelable: true 
                    });
                    e.target.dispatchEvent(inputEvent);
                }
            }
        });

        // Handle paste event
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            
            // Get pasted data
            let paste = (e.clipboardData || window.clipboardData).getData('text');
            
            // Clean the pasted data
            let cleanPaste = paste.replace(/[^0-9+]/g, '');
            
            // Ensure + is only at the beginning
            if (cleanPaste.includes('+')) {
                let parts = cleanPaste.split('+');
                if (parts[0] === '') {
                    cleanPaste = '+' + parts.slice(1).join('');
                } else {
                    cleanPaste = cleanPaste.replace(/\+/g, '');
                }
            }
            
            // Insert the cleaned data at cursor position
            let start = input.selectionStart;
            let end = input.selectionEnd;
            let currentValue = input.value;
            
            input.value = currentValue.substring(0, start) + cleanPaste + currentValue.substring(end);
            
            // Set cursor position after the pasted content
            input.setSelectionRange(start + cleanPaste.length, start + cleanPaste.length);
            
            // Trigger input event for Livewire
            const inputEvent = new Event('input', { 
                bubbles: true, 
                cancelable: true 
            });
            input.dispatchEvent(inputEvent);
        });
    }

    // Apply restriction to existing phone inputs
    function applyToExistingInputs() {
        const phoneInputs = document.querySelectorAll('input');
        phoneInputs.forEach(input => {
            if (isPhoneInput(input)) {
                restrictPhoneInput(input);
            }
        });
    }

    // Initialize when DOM is ready
    function initialize() {
        applyToExistingInputs();
        
        // Observer for dynamically added inputs (for Livewire components)
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Check if the added node is a phone input
                        if (node.tagName === 'INPUT' && isPhoneInput(node)) {
                            restrictPhoneInput(node);
                        }
                        
                        // Check for phone inputs within the added node
                        if (node.querySelectorAll) {
                            const phoneInputs = node.querySelectorAll('input');
                            phoneInputs.forEach(input => {
                                if (isPhoneInput(input)) {
                                    restrictPhoneInput(input);
                                }
                            });
                        }
                    }
                });
            });
        });

        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Initialize based on document state
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
    
    // Re-apply when Livewire updates the DOM
    document.addEventListener('livewire:navigated', applyToExistingInputs);
    document.addEventListener('livewire:load', applyToExistingInputs);
    
    // For Livewire v3
    if (typeof window !== 'undefined' && window.Livewire) {
        if (window.Livewire.hook) {
            window.Livewire.hook('morph.updated', () => {
                setTimeout(applyToExistingInputs, 50);
            });
        }
    }
    
    // Also listen for Livewire component updates
    document.addEventListener('livewire:update', function() {
        setTimeout(applyToExistingInputs, 50);
    });
    
})();