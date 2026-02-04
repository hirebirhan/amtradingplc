<?php

namespace App\Livewire\Purchases;

use App\Models\Purchase;
use Illuminate\Support\Facades\{Auth, Log};
use Livewire\{Component, Attributes\Layout};
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
class Show extends Component
{
    public Purchase $purchase;
    public $purchaseItems = [];
    public $paymentHistory = [];
    public $isUpdatingStock = false;
    public $confirmingStockUpdate = false;

    public function mount(Purchase $purchase)
    {
        // Load purchase with relationships
        $this->purchase = $purchase->load([
            'supplier', 
            'warehouse', 
            'branch', 
            'user', 
            'items.item', 
            'payments.user', 
            'payments.bankAccount',
            'bankAccount'
        ]);

        // Check if user has permission to view this purchase
        if (!$purchase->user_id || $purchase->user_id !== Auth::id()) {
            $user = Auth::user();
            $adminRoles = $user->roles->pluck('name')->toArray();
            $allowedRoles = ['SuperAdmin', 'GeneralManager', 'BranchManager'];
            
            if (empty(array_intersect($adminRoles, $allowedRoles))) {
                abort(403);
            }
        }

        $this->purchaseItems = $this->purchase->items;
        $this->paymentHistory = $this->purchase->payments()->orderBy('payment_date', 'desc')->get();
    }

    public function confirmStockUpdate()
    {
        // Validate before showing confirmation
        if ($this->purchase->status === 'received') {
            return $this->dispatch('notify', [
                'type' => 'info',
                'message' => 'This purchase has already been received'
            ]);
        }

        // Allow processing if confirmed OR if paid (regardless of status)
        if ($this->purchase->status !== 'confirmed' && $this->purchase->payment_status !== 'paid') {
            return $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Only confirmed or paid purchases can be received'
            ]);
        }

        if ($this->purchase->items->isEmpty()) {
            return $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot receive purchase with no items'
            ]);
        }

        $this->confirmingStockUpdate = true;
    }

    public function cancelStockUpdate()
    {
        $this->confirmingStockUpdate = false;
    }

    public function updateStock()
    {
        if (!$this->canUpdatePurchase()) {
            return $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to update stock'
            ]);
        }

        // Check if already received
        if ($this->purchase->status === 'received') {
            return $this->dispatch('notify', [
                'type' => 'info',
                'message' => 'This purchase has already been received'
            ]);
        }

        // Allow processing if confirmed OR if paid (regardless of status)
        if ($this->purchase->status !== 'confirmed' && $this->purchase->payment_status !== 'paid') {
            return $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Only confirmed or paid purchases can be received. Current status: ' . ucfirst($this->purchase->status)
            ]);
        }

        // Validate purchase has items
        if ($this->purchase->items->isEmpty()) {
            return $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot receive purchase with no items'
            ]);
        }

        $this->isUpdatingStock = true;

        try {
            // Process the purchase directly without changing status
            // The processPurchase method will handle status validation
            $result = $this->purchase->processPurchase();
            
            if ($result) {
                // Refresh the purchase data
                $this->purchase->refresh();
                $this->purchaseItems = $this->purchase->items;
                
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Purchase received successfully! Stock levels have been updated.'
                ]);
            } else {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => 'Purchase was already processed or could not be processed'
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error receiving purchase: ' . $e->getMessage()
            ]);
            
            // Log the error for debugging
            Log::error('Error processing purchase: ' . $e->getMessage(), [
                'purchase_id' => $this->purchase->id,
                'user_id' => Auth::id(),
                'exception' => $e
            ]);
        } finally {
            $this->isUpdatingStock = false;
            $this->confirmingStockUpdate = false;
        }
    }

    public function render()
    {
        return view('livewire.purchases.show', [
            'purchase' => $this->purchase,
            'purchaseItems' => $this->purchaseItems,
        ]);
    }

    private function canUpdatePurchase(): bool
    {
        $user = Auth::user();
        $adminRoles = $user->roles->pluck('name')->toArray();
        $allowedRoles = ['SuperAdmin', 'GeneralManager', 'BranchManager'];
        
        return !empty(array_intersect($adminRoles, $allowedRoles)) || $this->purchase->user_id === Auth::id();
    }
}