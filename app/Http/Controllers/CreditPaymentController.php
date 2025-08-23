<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use UserHelper;

class CreditPaymentController extends Controller
{
    /**
     * Show form to make a payment against a credit
     */
    public function create(Credit $credit)
    {
        // Check if user has access to this credit's warehouse
        if (!UserHelper::hasAccessToWarehouse($credit->warehouse_id)) {
            abort(403, 'You do not have permission to access this credit.');
        }
        
        return view('credit-payments.create', compact('credit'));
    }

    /**
     * Store a new payment against a credit
     */
    public function store(Request $request, Credit $credit)
    {
        // Check if user has access to this credit's warehouse
        if (!UserHelper::hasAccessToWarehouse($credit->warehouse_id)) {
            abort(403, 'You do not have permission to access this credit.');
        }
        
        try {
            // Validate the payment data
            $validatedData = $request->validate([
                'amount' => 'required|numeric|min:0.01|max:' . $credit->remaining_amount,
                'payment_method' => 'required|in:cash,bank_transfer,check,mobile_money',
                'payment_date' => 'required|date',
                'notes' => 'nullable|string|max:500',
                'reference_no' => 'nullable|string|max:100',
            ]);

            DB::beginTransaction();
            
            // Record the payment
            $payment = $credit->addPayment(
                $validatedData['amount'],
                $validatedData['payment_method'],
                $validatedData['reference_no'],
                $validatedData['notes'],
                $validatedData['payment_date']
            );
            
            // If this credit is linked to a purchase or sale, update that too
            if ($credit->reference_type === 'purchase' && $credit->reference_id) {
                $purchase = $credit->purchase;
                if ($purchase) {
                    $purchase->addPayment(
                        $validatedData['amount'],
                        $validatedData['payment_method'],
                        $validatedData['reference_no'],
                        $validatedData['notes'],
                        $validatedData['payment_date']
                    );
                    
                    // Ensure purchase payment status is set to 'paid' if fully paid
                    if ($purchase->due_amount <= 0) {
                        $purchase->payment_status = 'paid';
                        $purchase->save();
                    }
                } else {
                    \Log::warning('Purchase reference not found in controller', [
                        'credit_id' => $credit->id, 
                        'reference_id' => $credit->reference_id
                    ]);
                }
            } elseif ($credit->reference_type === 'sale' && $credit->reference_id) {
                $sale = $credit->sale;
                if ($sale) {
                    $sale->addPayment(
                        $validatedData['amount'],
                        $validatedData['payment_method'],
                        $validatedData['reference_no'],
                        $validatedData['notes'],
                        $validatedData['payment_date']
                    );
                    
                    // Ensure sale payment status is set to 'paid' if fully paid
                    if ($sale->due_amount <= 0) {
                        $sale->payment_status = 'paid';
                        $sale->save();
                    }
                } else {
                    // Sale reference not found
                }
            }
            
            DB::commit();
            
            // If credit is now fully paid, redirect to the credits index page
            if ($credit->status === 'paid' || $credit->balance <= 0) {
                return redirect()
                    ->route('admin.credits.index')
                    ->with('success', 'Payment of ETB ' . number_format($validatedData['amount'], 2) . ' recorded. Credit fully paid!');
            }
            
            // Otherwise, show the credit details
            return redirect()
                ->route('admin.credits.show', $credit->id)
                ->with('success', 'Payment of ETB ' . number_format($validatedData['amount'], 2) . ' recorded successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Failed to record credit payment', [
                'credit_id' => $credit->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()
                ->back()
                ->with('error', 'Failed to record payment. Please try again.');
        }
    }

    /**
     * List payments for a credit
     */
    public function index(Credit $credit)
    {
        $payments = $credit->payments()->orderBy('payment_date', 'desc')->paginate(15);
        
        // Force refresh credit data to ensure it's up to date
        $credit->refresh();
        
        return view('credit-payments.index', [
            'credit' => $credit,
            'payments' => $payments,
            'slot' => '' // Add empty slot to fix undefined variable issue
        ]);
    }
}
