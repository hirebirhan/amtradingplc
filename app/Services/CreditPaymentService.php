<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Credit;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Collection;

class CreditPaymentService
{
    /**
     * Calculate payment percentage
     */
    public function calculatePaymentPercentage(Credit $credit): float
    {
        if ($credit->amount <= 0) {
            return 0;
        }
        
        return ($credit->paid_amount / $credit->amount) * 100;
    }

    /**
     * Check if credit is eligible for closing offer (more than 50% paid)
     * ONLY for payable credits (we owe the supplier)
     */
    public function isEligibleForClosingOffer(Credit $credit): bool
    {
        // Only apply to payable credits (we owe the supplier)
        if ($credit->credit_type !== 'payable') {
            return false;
        }
        
        $paymentPercentage = $this->calculatePaymentPercentage($credit);
        return $paymentPercentage >= 50 && $credit->balance > 0;
    }

    /**
     * Check if credit is fully paid (100%)
     */
    public function isFullyPaid(Credit $credit): bool
    {
        $paymentPercentage = $this->calculatePaymentPercentage($credit);
        return $paymentPercentage >= 100;
    }

    /**
     * Check if credit requires closing prices before full payment
     * ONLY for payable credits
     */
    public function requiresClosingPricesForFullPayment(Credit $credit): bool
    {
        // Only apply to payable credits
        if ($credit->credit_type !== 'payable') {
            return false;
        }
        
        // Check if this is a purchase credit
        if ($credit->reference_type !== 'purchase') {
            return false;
        }
        
        // If user is trying to pay the full balance, require closing prices
        return true;
    }

    /**
     * Calculate closing offer for a credit
     */
    public function calculateClosingOffer(Credit $credit): array
    {
        // Only for payable credits
        if ($credit->credit_type !== 'payable') {
            return [
                'eligible' => false,
                'message' => 'Closing offers are only available for payable credits (supplier debts)'
            ];
        }
        
        if (!$this->isEligibleForClosingOffer($credit)) {
            return [
                'eligible' => false,
                'message' => 'Credit is not eligible for closing offer (requires 50% payment for payable credits)'
            ];
        }

        // Get the original transaction (purchase or sale)
        $originalTransaction = $this->getOriginalTransaction($credit);
        
        if (!$originalTransaction) {
            return [
                'eligible' => false,
                'message' => 'Original transaction not found'
            ];
        }

        $result = [
            'eligible' => true,
            'payment_percentage' => $this->calculatePaymentPercentage($credit),
            'remaining_balance' => $credit->balance,
            'original_transaction_type' => $credit->reference_type,
            'original_transaction_id' => $credit->reference_id,
            'message' => 'You can close this payable credit early by negotiating unit prices with the supplier.',
        ];

        return $result;
    }

    /**
     * Get the original transaction (purchase or sale)
     */
    private function getOriginalTransaction(Credit $credit): ?object
    {
        if (!$credit->reference_type || !$credit->reference_id) {
            return null;
        }

        return match($credit->reference_type) {
            'purchase' => Purchase::with(['items.item'])->find($credit->reference_id),
            'sale' => Sale::with(['items.item'])->find($credit->reference_id),
            default => null,
        };
    }

    /**
     * Calculate profit/loss based on negotiated unit prices
     * Updated to work per base unit (per kg, liter, etc.) not per piece
     */
    public function calculateProfitLossFromNegotiatedPrices(Credit $credit, array $negotiatedPrices): array
    {
        $originalTransaction = $this->getOriginalTransaction($credit);
        
        if (!$originalTransaction) {
            return [
                'success' => false,
                'message' => 'Original transaction not found'
            ];
        }

        $totalOriginalCost = 0;
        $totalClosingCost = 0;
        $totalSavings = 0;
        $items = [];

        foreach ($originalTransaction->items as $item) {
            if (!$item->item) continue;

            $itemId = $item->item_id;
            $quantity = (float) $item->quantity; // Total quantity purchased (pieces)
            
            // Get original unit cost (per piece)
            $originalUnitCost = (float) ($originalTransaction instanceof \App\Models\Purchase 
                ? $item->unit_cost 
                : $item->item->cost_price);
            
            // Get unit quantity (how many liters per piece)
            $unitQuantity = $item->item->unit_quantity ?: 1;
            
            // Calculate original cost per individual item (per liter)
            $originalCostPerItem = $originalUnitCost / $unitQuantity;
            
            // Get closing price per individual item (per liter) - direct input from user
            $closingPricePerItem = isset($negotiatedPrices[$itemId]) 
                ? (float) $negotiatedPrices[$itemId] 
                : $originalCostPerItem;
            
            // Calculate total costs
            $originalItemCost = $originalUnitCost * $quantity; // Total original cost
            $closingItemCost = $closingPricePerItem * $unitQuantity * $quantity; // Total closing cost
            $itemSavings = $originalItemCost - $closingItemCost; // Savings
            
            $totalOriginalCost += $originalItemCost;
            $totalClosingCost += $closingItemCost;
            $totalSavings += $itemSavings;

            $items[] = [
                'item_id' => $itemId,
                'item_name' => $item->item->name,
                'quantity' => $quantity,
                'unit_quantity' => $unitQuantity,
                'original_unit_cost' => $originalUnitCost,
                'original_cost_per_item' => $originalCostPerItem,
                'closing_price_per_item' => $closingPricePerItem,
                'original_total_cost' => $originalItemCost,
                'closing_total_cost' => $closingItemCost,
                'profit_loss' => $itemSavings,
                'profit_loss_percentage' => $originalItemCost > 0 ? ($itemSavings / $originalItemCost) * 100 : 0,
            ];
        }

        // Calculate final closing amount: Total closing cost - Already paid amount
        $finalClosingAmount = $totalClosingCost - $credit->paid_amount;
        $overallSavingsPercentage = $totalOriginalCost > 0 ? ($totalSavings / $totalOriginalCost) * 100 : 0;

        // Check if credit can be closed with current savings
        $canClose = ($credit->paid_amount + $totalSavings) >= $credit->amount;
        $shortfall = $canClose ? 0 : ($credit->amount - ($credit->paid_amount + $totalSavings));

        return [
            'success' => true,
            'items' => $items,
            'total_original_cost' => $totalOriginalCost,
            'total_closing_cost' => $totalClosingCost,
            'total_savings' => $totalSavings,
            'total_profit_loss' => $totalSavings,
            'overall_savings_percentage' => $overallSavingsPercentage,
            'overall_profit_loss_percentage' => $overallSavingsPercentage,
            'final_closing_amount' => $finalClosingAmount,
            'current_balance' => $credit->balance,
            'is_profit' => $totalSavings > 0,
            'is_loss' => $totalSavings < 0,
            'is_break_even' => $totalSavings == 0,
            'can_close' => $canClose,
            'shortfall' => $shortfall,
        ];
    }

    /**
     * Process early closure of credit with negotiated prices
     */
    public function processEarlyClosureWithNegotiatedPrices(Credit $credit, array $negotiatedPrices, bool $forceClose = false): array
    {
        // Only for payable credits
        if ($credit->credit_type !== 'payable') {
            return [
                'success' => false,
                'message' => 'Early closure is only available for payable credits'
            ];
        }
        
        // Check if credit is already fully paid or closed
        if ($credit->status === 'paid' || $credit->balance <= 0) {
            return [
                'success' => false,
                'message' => 'Credit is already fully paid or closed'
            ];
        }
        
        // Check if closing payment already exists
        $existingClosingPayment = $credit->payments()
            ->where('payment_method', 'other')
            ->where('reference_no', 'LIKE', 'EARLY-CLOSURE-%')
            ->first();
            
        if ($existingClosingPayment) {
            return [
                'success' => false,
                'message' => 'Closing payment has already been processed for this credit'
            ];
        }
        
        if (!$forceClose && !$this->isEligibleForClosingOffer($credit)) {
            return [
                'success' => false,
                'message' => 'Credit is not eligible for early closure'
            ];
        }

        // Calculate profit/loss
        $savingsCalculation = $this->calculateProfitLossFromNegotiatedPrices($credit, $negotiatedPrices);
        
        if (!$savingsCalculation['success']) {
            return $savingsCalculation;
        }

        $totalSavings = $savingsCalculation['total_savings'];
        
        // Calculate the actual payment amount needed to close the credit
        // If savings >= balance, then credit is fully paid with minimal payment
        // If savings < balance, then payment = balance - savings
        $actualPaymentAmount = max(0.01, $credit->balance - $totalSavings); // Minimum 0.01 ETB payment
        
        // If savings are greater than or equal to balance, credit is fully paid
        $willBeFullyPaid = ($totalSavings >= $credit->balance);

        try {
            // Create the closing payment
            $payment = $credit->addPayment(
                $actualPaymentAmount,
                'other',
                'EARLY-CLOSURE-' . $credit->reference_no,
                'Early closure payment with negotiated prices - Savings: ' . number_format($totalSavings, 2) . ' ETB',
                now()->format('Y-m-d')
            );

            // For closing payments, we need to adjust the credit amount to reflect negotiated prices
            // This ensures the credit is properly marked as paid
            $totalClosingCost = $savingsCalculation['total_closing_cost'];
            $effectiveAmount = $totalClosingCost; // The actual amount we need to pay
            
            // Update credit to reflect the negotiated total
            $credit->amount = $effectiveAmount;
            $credit->paid_amount = $credit->payments()->sum('amount');
            $credit->balance = max(0, $effectiveAmount - $credit->paid_amount);
            
            // Update status based on new balance
            if ($credit->balance <= 0) {
                $credit->status = 'paid';
            } else {
                $credit->status = 'partially_paid';
            }
            
            $credit->save();

            // Update purchase items with closing prices and profit/loss calculations
            if ($credit->reference_type === 'purchase' && $credit->reference_id) {
                $purchase = $credit->purchase;
                if ($purchase) {
                    // Store total savings in the purchase record
                    $purchase->discount = $totalSavings;
                    $purchase->save();
                    
                    // Update each purchase item with closing prices and profit/loss
                    foreach ($savingsCalculation['items'] as $itemData) {
                        $purchaseItem = $purchase->items()->where('item_id', $itemData['item_id'])->first();
                        if ($purchaseItem) {
                            // Store the negotiated unit price (per base unit)
                            $purchaseItem->closing_unit_price = $itemData['closing_price_per_item'];
                            // Store the total closing cost for this item
                            $purchaseItem->total_closing_cost = $itemData['closing_total_cost'];
                            // Store the profit/loss for this item
                            $purchaseItem->profit_loss_per_item = $itemData['profit_loss'];
                            $purchaseItem->save();
                        }
                    }
                }
            }

            return [
                'success' => true,
                'message' => 'Credit closed successfully with negotiated prices. Savings: ' . number_format($totalSavings, 2) . ' ETB',
                'payment' => $payment,
                'final_balance' => (float) $credit->balance,
                'savings' => $totalSavings,
                'actual_payment_amount' => $actualPaymentAmount,
                'will_be_fully_paid' => $willBeFullyPaid,
                'savings_calculation' => $savingsCalculation
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to process early closure: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate if full payment can be made without closing prices
     * For payable credits, require closing prices before full payment
     */
    public function validateFullPaymentWithoutClosingPrices(Credit $credit, float $paymentAmount): array
    {
        // Only check payable credits
        if ($credit->credit_type !== 'payable') {
            return [
                'valid' => true,
                'message' => ''
            ];
        }
        
        // Only check if this is a full payment
        if ($paymentAmount < $credit->balance) {
            return [
                'valid' => true,
                'message' => ''
            ];
        }
        
        // For payable credits, require closing prices for full payment
        return [
            'valid' => false,
            'message' => 'For payable credits, closing prices must be entered before making full payment. Please use the early closure option above to negotiate prices with the supplier.'
        ];
    }

    /**
     * Fix existing credits that have closing payments but incorrect status
     * This method corrects the credit amount and status for credits with closing payments
     */
    public function fixClosingPaymentCredits(): array
    {
        $fixedCredits = [];
        $errors = [];

        // Find all credits with closing payments
        $creditsWithClosingPayments = \App\Models\Credit::whereHas('payments', function($query) {
            $query->where('payment_method', 'other')
                  ->where('reference_no', 'LIKE', 'EARLY-CLOSURE-%');
        })->with(['purchase.items', 'payments'])->get();

        foreach ($creditsWithClosingPayments as $credit) {
            try {
                // Only process payable credits with purchase references
                if ($credit->credit_type !== 'payable' || $credit->reference_type !== 'purchase' || !$credit->purchase) {
                    continue;
                }

                // Calculate total closing cost from purchase items
                $totalClosingCost = 0;
                $hasClosingPrices = false;

                foreach ($credit->purchase->items as $item) {
                    if ($item->closing_unit_price && $item->closing_unit_price > 0) {
                        $hasClosingPrices = true;
                        // Calculate closing cost: closing_price_per_base_unit * unit_quantity * quantity
                        $unitQuantity = $item->item->unit_quantity ?: 1;
                        $closingCost = $item->closing_unit_price * $unitQuantity * $item->quantity;
                        $totalClosingCost += $closingCost;
                    } else {
                        $totalClosingCost += $item->unit_cost * $item->quantity;
                    }
                }

                if (!$hasClosingPrices) {
                    continue;
                }

                // Calculate total payments
                $totalPaid = $credit->payments()->sum('amount');
                
                // Update credit to reflect negotiated total
                $credit->amount = $totalClosingCost;
                $credit->paid_amount = $totalPaid;
                $credit->balance = max(0, $totalClosingCost - $totalPaid);
                
                // Update status
                if ($credit->balance <= 0) {
                    $credit->status = 'paid';
                } else {
                    $credit->status = 'partially_paid';
                }
                
                $credit->save();
                
                $fixedCredits[] = [
                    'id' => $credit->id,
                    'reference_no' => $credit->reference_no,
                    'old_amount' => $credit->getOriginal('amount'),
                    'new_amount' => $totalClosingCost,
                    'old_status' => $credit->getOriginal('status'),
                    'new_status' => $credit->status,
                    'total_paid' => $totalPaid,
                    'balance' => $credit->balance
                ];

            } catch (\Exception $e) {
                $errors[] = [
                    'credit_id' => $credit->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'fixed_credits' => $fixedCredits,
            'total_fixed' => count($fixedCredits),
            'errors' => $errors
        ];
    }
} 