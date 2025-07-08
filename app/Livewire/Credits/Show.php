<?php

namespace App\Livewire\Credits;

use App\Models\Credit;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Show extends Component
{
    public Credit $credit;
    
    public function mount(Credit $credit)
    {
        // Eager load the credit with basic relationships
        $credit->load(['payments', 'customer', 'supplier']);
        
        // Conditionally load purchase or sale based on reference type
        if ($credit->reference_type === 'purchase' && $credit->reference_id) {
            $credit->load('purchase.items.item');
        } elseif ($credit->reference_type === 'sale' && $credit->reference_id) {
            $credit->load('sale');
        }
        
        $this->credit = $credit;
    }
    
    public function refreshData()
    {
        // Clear cache before refresh
        \Log::info('Manually refreshing credit data', ['credit_id' => $this->credit->id]);
        
        // Start with basic relationships
        $query = Credit::with(['payments', 'customer', 'supplier']);
        
        // Conditionally load purchase or sale based on reference type
        if ($this->credit->reference_type === 'purchase' && $this->credit->reference_id) {
            $query->with('purchase.items.item');
        } elseif ($this->credit->reference_type === 'sale' && $this->credit->reference_id) {
            $query->with('sale');
        }
        
        // Get the fresh credit
        $freshCredit = $query->find($this->credit->id);
        
        if ($freshCredit) {
            $this->credit = $freshCredit;
            session()->flash('refresh', 'Data refreshed at ' . now()->format('H:i:s'));
        } else {
            session()->flash('error', 'Could not refresh credit data');
        }
    }
    
    public function getSavingsInformation()
    {
        // Check if this credit has closing payments with savings
        $closingPayment = $this->credit->payments()
            ->where('payment_method', 'other')
            ->where('reference_no', 'LIKE', 'EARLY-CLOSURE-%')
            ->first();
            
        if (!$closingPayment) {
            return null;
        }
        
        $savings = 0;
        $hasClosingPrices = false;
        
        // Get savings from purchase if available
        if ($this->credit->reference_type === 'purchase' && $this->credit->purchase) {
            $savings = $this->credit->purchase->discount ?? 0;
            
            // Check if purchase items have closing prices
            $hasClosingPrices = $this->credit->purchase->items()
                ->whereNotNull('closing_unit_price')
                ->exists();
        }
        
        return [
            'total_savings' => $savings,
            'has_closing_prices' => $hasClosingPrices,
            'closing_payment' => $closingPayment,
            'effective_balance' => max(0, $this->credit->balance - $savings)
        ];
    }
    
    public function render()
    {
        // Add cache busting parameter to ensure fresh data
        $timestamp = now()->timestamp;
        
        // Ensure fresh payments data is loaded
        $recentPayments = $this->credit->payments()
            ->latest('payment_date')
            ->take(5)
            ->get();
            
        // Get savings information
        $savingsInfo = $this->getSavingsInformation();
            
        return view('livewire.credits.show', [
            'credit' => $this->credit,
            'recentPayments' => $recentPayments,
            'savingsInfo' => $savingsInfo,
            'refreshTimestamp' => $timestamp
        ])->title('Credit Details - #' . $this->credit->reference_no);
    }
}
