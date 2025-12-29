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

    public function markAsPaid()
    {
        if (!$this->canUpdatePurchase()) {
            return $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to update this purchase'
            ]);
        }

        $this->purchase->payment_status = 'paid';
        $this->purchase->save();

        $this->dispatchBrowserEvent('notify', [
            'type' => 'success',
            'message' => 'Purchase marked as paid successfully!'
        ]);
    }

    public function markAsPartial()
    {
        if (!$this->canUpdatePurchase()) {
            return $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to update this purchase'
            ]);
        }

        $this->purchase->payment_status = 'partial';
        $this->purchase->save();

        $this->dispatchBrowserEvent('notify', [
            'type' => 'success',
            'message' => 'Purchase marked as partially paid successfully!'
        ]);
    }

    public function markAsPending()
    {
        if (!$this->canUpdatePurchase()) {
            return $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to update this purchase'
            ]);
        }

        $this->purchase->payment_status = 'pending';
        $this->purchase->save();

        $this->dispatchBrowserEvent('notify', [
            'type' => 'success',
            'message' => 'Purchase marked as pending successfully!'
        ]);
    }

    public function confirmStockUpdate()
    {
        $this->confirmingStockUpdate = true;
    }

    public function cancelStockUpdate()
    {
        $this->confirmingStockUpdate = false;
    }

    public function updateStock()
    {
        if (!$this->canUpdatePurchase()) {
            return $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to update stock'
            ]);
        }

        if ($this->purchase->status === 'received') {
            return $this->dispatchBrowserEvent('notify', [
                'type' => 'info',
                'message' => 'This purchase has already been received'
            ]);
        }

        $this->isUpdatingStock = true;

        try {
            // Process the purchase to update stock levels
            $result = $this->purchase->processPurchase();
            
            if ($result) {
                // Refresh the purchase data
                $this->purchase->refresh();
                $this->purchaseItems = $this->purchase->items;
                
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'success',
                    'message' => 'Stock has been updated successfully!'
                ]);
            } else {
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'warning',
                    'message' => 'Purchase was already processed or could not be processed'
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'Error updating stock: ' . $e->getMessage()
            ]);
            
            // Log the error for debugging
            Log::error('Error processing purchase: ' . $e->getMessage(), [
                'purchase_id' => $this->purchase->id,
                'exception' => $e
            ]);
        } finally {
            $this->isUpdatingStock = false;
            $this->confirmingStockUpdate = false;
        }
    }

    public function downloadPdf()
    {
        return redirect()->route('admin.purchases.pdf', $this->purchase->id);
    }

    public function printInvoice()
    {
        return redirect()->route('admin.purchases.print', $this->purchase);
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