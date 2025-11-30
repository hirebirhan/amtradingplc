<?php

namespace App\Livewire\CreditPayment;

use App\Models\Credit;
use App\Models\BankAccount;
use App\Services\BankService;
use App\Services\CreditPaymentService;
use App\Traits\HasNotifications;
use App\Traits\HasFlashMessages;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads, HasNotifications, HasFlashMessages;
    
    public Credit $credit;
    
    public $amount;
    public $payment_method = 'cash';
    public $reference_no;
    public $payment_date;
    public $notes;
    
    // Bank transfer fields
    public $bank_account_id;
    public $account_holder_name;
    
    // New bank details fields
    public $receiver_bank_name;
    public $receiver_account_holder;
    public $receiver_account_number;
    public $reference;
    
    // Telebirr fields
    public $transaction_number;
    
    // File upload
    public $attachment;
    
    // Available bank accounts for selection
    public $bankAccounts = [];
    
    // Closing Offer Logic (50%+ payment)
    public $showClosingOffer = false;
    public $closingOffer = [];
    public $negotiatedPrices = [];
    public $showNegotiationForm = false;
    public $savingsCalculation = null;
    

    
    // Confirmation properties
    public $showConfirmation = false;
    public $confirmationData = [];
    
    // Closing prices modal
    public $showClosingPricesModal = false;
    public $closingPrices = [];
    
    // Payment type selection
    public $paymentType = 'down_payment'; // 'down_payment' or 'closing_payment'
    
    public function mount(Credit $credit)
    {
        // Check if credit is already fully paid
        if ($credit->status === 'paid' || $credit->balance <= 0) {
            $this->notifyError('This credit is already fully paid. No additional payments can be made.');
            return redirect()->route('admin.credits.show', $credit->id);
        }
        
        // Eager load basic relationships
        $credit->load(['customer', 'supplier']);
        
        // Conditionally load purchase or sale based on reference type
        if ($credit->reference_type === 'purchase' && $credit->reference_id) {
            $credit->load('purchase.items.item');
        } elseif ($credit->reference_type === 'sale' && $credit->reference_id) {
            $credit->load('sale');
        }
        
        $this->credit = $credit;
        $this->amount = $credit->balance; // Simple: remaining = current balance
        $this->payment_date = date('Y-m-d');
        
        // Load bank accounts from centralized service
        $this->loadBankAccounts();
        
        // Check for closing offer eligibility (only for unpaid credits)
        $this->checkClosingOfferEligibility();
    }
    
    public function loadBankAccounts()
    {
        $bankService = new BankService();
        $this->bankAccounts = $bankService->getActiveBankAccounts();
    }
    
    public function updatedPaymentMethod($value)
    {
        // Reset all method-specific fields when payment method changes
        $this->reset(['transaction_number', 'bank_account_id', 'account_holder_name', 'reference_no']);
        
        // Reset bank details fields for non-bank/telebirr payments
        if (!in_array($value, ['bank_transfer', 'telebirr'])) {
            $this->reset(['receiver_bank_name', 'receiver_account_holder', 'receiver_account_number']);
        }
        
        // Load bank accounts if needed
        if ($value === 'bank_transfer') {
            $this->loadBankAccounts();
        }
    }
    
    public function updatedPaymentType($value)
    {
        // Reset amount to correct value based on payment type
        if ($value === 'down_payment') {
            // For regular payments: remaining = current balance
            $this->amount = $this->credit->balance;
        } elseif ($value === 'closing_payment' && $this->credit->credit_type === 'payable') {
            // For closing payments: will be calculated when prices are entered
            $this->amount = $this->credit->balance;
        }
    }
    
    /**
     * Check if credit is eligible for closing offer
     */
    public function checkClosingOfferEligibility()
    {
        $creditPaymentService = new CreditPaymentService();
        
        // Only show closing offer for payable credits
        if ($this->credit->credit_type !== 'payable') {
            return;
        }
        
        // Check if eligible for early closure (50%+)
        if ($creditPaymentService->isEligibleForClosingOffer($this->credit)) {
            $this->closingOffer = $creditPaymentService->calculateClosingOffer($this->credit);
            $this->showClosingOffer = true;
            
            // Initialize negotiated prices for each item
            $this->initializeNegotiatedPrices();
        }
    }

    /**
     * Initialize negotiated prices with original prices
     */
    public function initializeNegotiatedPrices()
    {
        $this->negotiatedPrices = [];
        
        if ($this->credit->reference_type === 'purchase' && $this->credit->reference_id) {
            $purchase = $this->credit->purchase;
            if ($purchase) {
                foreach ($purchase->items as $item) {
                    $this->negotiatedPrices[$item->item_id] = $item->unit_cost;
                }
            }
        }
    }
    
    /**
     * Show accept form with purchased items
     */
    public function acceptOffer()
    {
        $this->showNegotiationForm = true;
        
        // Ensure purchase relationship is loaded
        if ($this->credit->reference_type === 'purchase' && $this->credit->reference_id) {
            $this->credit->load('purchase.items.item');
        }
    }
    
    /**
     * Calculate profit/loss based on negotiated prices
     */
    public function calculateSavings()
    {
        $creditPaymentService = new CreditPaymentService();
        $this->savingsCalculation = $creditPaymentService->calculateProfitLossFromNegotiatedPrices($this->credit, $this->negotiatedPrices);
    }

    /**
     * Calculate savings when negotiated prices change
     */
    public function updatedNegotiatedPrices()
    {
        // Calculate savings whenever negotiated prices are updated
        $this->calculateSavings();
    }
    
    /**
     * Accept the closing offer with negotiated prices
     */
    public function acceptClosingOffer()
    {
        // Validate that we have negotiated prices
        if (empty($this->negotiatedPrices)) {
            $this->notifyError('Please enter negotiated prices first.');
            return;
        }
        
        $creditPaymentService = new CreditPaymentService();
        $result = $creditPaymentService->processEarlyClosureWithNegotiatedPrices($this->credit, $this->negotiatedPrices);
        
        if ($result['success']) {
            $this->notifySuccess($result['message']);
            return redirect()->route('admin.credits.show', $this->credit->id);
        } else {
            $this->notifyError($result['message']);
        }
    }
    
    /**
     * Decline the closing offer
     */
    public function declineClosingOffer()
    {
        $this->showClosingOffer = false;
        $this->showNegotiationForm = false;
        $this->closingOffer = [];
        $this->negotiatedPrices = [];
        $this->savingsCalculation = null;
    }
    
    public function confirmPayment()
    {
        // Check if credit is already fully paid
        if ($this->credit->status === 'paid' || $this->credit->balance <= 0) {
            $this->notifyError('This credit is already fully paid. No additional payments can be made.');
            return redirect()->route('admin.credits.show', $this->credit->id);
        }
        
        // Validate the form first
        $rules = [
            'amount' => 'required|numeric|min:0.01|max:' . $this->credit->balance,
            'payment_method' => 'required|string|in:cash,bank_transfer,telebirr,check',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
        ];
        
        // Add conditional validation rules based on payment method
        if ($this->payment_method === 'bank_transfer') {
            $rules['bank_account_id'] = 'required|exists:bank_accounts,id';
            $rules['reference_no'] = 'nullable|string|max:255';
            $rules['receiver_bank_name'] = 'required|string|max:255';
            $rules['receiver_account_holder'] = 'required|string|max:255';
            $rules['receiver_account_number'] = 'required|string|max:255';
        } elseif ($this->payment_method === 'telebirr') {
            $rules['transaction_number'] = 'required|string|max:255';
            $rules['receiver_bank_name'] = 'required|string|max:255';
            $rules['receiver_account_holder'] = 'required|string|max:255';
            $rules['receiver_account_number'] = 'required|string|max:255';
        } elseif ($this->payment_method === 'check') {
            $rules['reference_no'] = 'required|string|max:255';
        }
        
        // File upload validation
        if ($this->attachment) {
            $rules['attachment'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }
        
        $this->validate($rules, $this->getValidationMessages());
        
        // Prevent full payment with down payment type
        if ($this->paymentType === 'down_payment' && $this->amount >= $this->credit->balance) {
            $this->addError('amount', 'Cannot pay full amount with Down Payment. Use Closing Payment instead.');
            return;
        }
        
        if ($this->paymentType === 'closing_payment') {
            // Show closing prices modal for closing payments
            $this->showClosingPricesModal = true;
            return;
        }
        
        // Show confirmation modal
        $this->showConfirmation = true;
    }
    
    public function cancelConfirmation()
    {
        $this->showConfirmation = false;
        $this->confirmationData = [];
    }
    
    public function showClosingPricesForm()
    {
        $this->showClosingPricesModal = true;
        $this->initializeClosingPrices();
    }
    
    public function cancelClosingPrices()
    {
        $this->showClosingPricesModal = false;
        $this->closingPrices = [];
    }
    
    public function initializeClosingPrices()
    {
        $this->closingPrices = [];
        
        if ($this->credit->reference_type === 'purchase' && $this->credit->reference_id) {
            $purchase = $this->credit->purchase;
            if ($purchase) {
                foreach ($purchase->items as $item) {
                    // Initialize with original unit cost
                    $this->closingPrices[$item->item_id] = $item->unit_cost;
                }
                
                // Calculate initial payment amount
                $this->calculateClosingPaymentAmount();
            }
        } elseif ($this->credit->reference_type === 'sale' && $this->credit->reference_id) {
            $sale = $this->credit->sale;
            if ($sale) {
                foreach ($sale->items as $item) {
                    // Initialize with original unit price
                    $this->closingPrices[$item->item_id] = $item->unit_price;
                }
                
                // Calculate initial payment amount
                $this->calculateClosingPaymentAmount();
            }
        }
    }
    
    public function updatedClosingPrices()
    {
        // Auto-update payment amount based on closing prices
        $this->calculateClosingPaymentAmount();
    }
    
    public function calculateClosingPaymentAmount()
    {
        // Only calculate for closing payments with negotiated prices
        if ($this->paymentType !== 'closing_payment' || !$this->credit->reference_id) {
            return;
        }
        
        $totalClosingCost = 0;
        
        if ($this->credit->reference_type === 'purchase') {
            $purchase = $this->credit->purchase;
            if (!$purchase) {
                return;
            }
            
            foreach ($purchase->items as $item) {
                if (isset($this->closingPrices[$item->item_id]) && is_numeric($this->closingPrices[$item->item_id])) {
                    $closingPricePerUnit = (float) $this->closingPrices[$item->item_id];
                    $totalClosingCost += $closingPricePerUnit * $item->quantity;
                }
            }
        } elseif ($this->credit->reference_type === 'sale') {
            $sale = $this->credit->sale;
            if (!$sale) {
                return;
            }
            
            foreach ($sale->items as $item) {
                if (isset($this->closingPrices[$item->item_id]) && is_numeric($this->closingPrices[$item->item_id])) {
                    $closingPricePerUnit = (float) $this->closingPrices[$item->item_id];
                    $totalClosingCost += $closingPricePerUnit * $item->quantity;
                }
            }
        }
        
        // Business Rule: Closing cost cannot exceed current balance
        $maxAllowedCost = $this->credit->balance;
        $totalClosingCost = min($totalClosingCost, $maxAllowedCost);
        
        // Payment amount = closing cost (cannot exceed what you owe)
        $this->amount = $totalClosingCost;
    }
    
    public function processClosingPayment()
    {
        $this->validate([
            'closingPrices' => 'required|array',
            'closingPrices.*' => 'required|numeric|min:0',
        ]);
        
        // Validate total closing cost doesn't exceed credit amount
        $totalClosingCost = 0;
        
        if ($this->credit->reference_type === 'purchase' && $this->credit->purchase) {
            foreach ($this->credit->purchase->items as $item) {
                if (isset($this->closingPrices[$item->item_id])) {
                    $totalClosingCost += (float) $this->closingPrices[$item->item_id] * $item->quantity;
                }
            }
        } elseif ($this->credit->reference_type === 'sale' && $this->credit->sale) {
            foreach ($this->credit->sale->items as $item) {
                if (isset($this->closingPrices[$item->item_id])) {
                    $totalClosingCost += (float) $this->closingPrices[$item->item_id] * $item->quantity;
                }
            }
        }
        
        if ($totalClosingCost > $this->credit->balance) {
            $this->addError('closingPrices', 'Total closing cost (' . number_format($totalClosingCost, 2) . ' ETB) cannot exceed current balance (' . number_format($this->credit->balance, 2) . ' ETB)');
            return;
        }
        
        $this->calculateClosingPaymentAmount();
        $this->showClosingPricesModal = false;
        
        // Process as regular payment with closing price tracking
        try {
            DB::beginTransaction();
            
            $referenceNumber = $this->reference_no;
            if ($this->payment_method === 'telebirr') {
                $referenceNumber = $this->transaction_number;
            }
            
            // Update items with closing prices for tracking
            if ($this->credit->reference_type === 'purchase' && $this->credit->purchase) {
                foreach ($this->credit->purchase->items as $item) {
                    if (isset($this->closingPrices[$item->item_id])) {
                        $closingPrice = (float) $this->closingPrices[$item->item_id];
                        $item->update([
                            'closing_unit_price' => $closingPrice,
                            'total_closing_cost' => $closingPrice * $item->quantity,
                            'profit_loss_per_item' => ($item->unit_cost - $closingPrice) * $item->quantity
                        ]);
                    }
                }
            } elseif ($this->credit->reference_type === 'sale' && $this->credit->sale) {
                foreach ($this->credit->sale->items as $item) {
                    if (isset($this->closingPrices[$item->item_id])) {
                        $closingPrice = (float) $this->closingPrices[$item->item_id];
                        $item->update([
                            'closing_unit_price' => $closingPrice,
                            'total_closing_cost' => $closingPrice * $item->quantity,
                            'profit_loss_per_item' => ($item->unit_price - $closingPrice) * $item->quantity
                        ]);
                    }
                }
            }
            
            // Make regular payment - DO NOT change credit amount
            $payment = $this->credit->addPayment(
                $this->amount,
                $this->payment_method,
                $referenceNumber,
                $this->reference . ' (Closing payment with negotiated prices)',
                $this->payment_date,
                'closing',
                $this->reference,
                $this->receiver_bank_name,
                $this->receiver_account_holder,
                $this->receiver_account_number
            );
            
            DB::commit();
            
            // Refresh credit to get updated status
            $this->credit->refresh();
            
            // Log closing payment completion
            \Log::info('Closing payment completed', [
                'credit_id' => $this->credit->id,
                'payment_amount' => $this->amount,
                'final_status' => $this->credit->status,
                'final_balance' => $this->credit->balance
            ]);
            
            if ($this->credit->status === 'paid' || $this->credit->balance <= 0) {
                session()->flash('success', 'Credit fully paid with closing prices.');
                return redirect()->route('admin.credits.index');
            } else {
                session()->flash('success', 'Partial payment made with closing prices. Remaining balance: ' . number_format($this->credit->balance, 2) . ' ETB');
                return redirect()->route('admin.credits.show', $this->credit->id);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notifyError('Failed to record payment: ' . $e->getMessage());
        }
    }
    
    public function store()
    {
        // Prevent double submission
        if ($this->showConfirmation === false) {
            return;
        }
        
        try {
            DB::beginTransaction();
            
            // Log payment attempt
            \Log::info('Credit payment attempt', [
                'credit_id' => $this->credit->id,
                'amount' => $this->amount,
                'payment_method' => $this->payment_method,
                'current_balance' => $this->credit->balance
            ]);
            
            $referenceNumber = $this->reference_no;
            if ($this->payment_method === 'telebirr') {
                $referenceNumber = $this->transaction_number;
            }
            
            // Regular payment - always use addPayment method
            $payment = $this->credit->addPayment(
                $this->amount,
                $this->payment_method,
                $referenceNumber,
                $this->reference,
                $this->payment_date,
                'regular',
                $this->reference,
                $this->receiver_bank_name,
                $this->receiver_account_holder,
                $this->receiver_account_number
            );
            
            DB::commit();
            
            // Close confirmation modal
            $this->showConfirmation = false;
            
            // Refresh credit to get updated status
            $this->credit->refresh();
            
            // Log final status
            \Log::info('Credit payment completed', [
                'credit_id' => $this->credit->id,
                'final_status' => $this->credit->status,
                'final_balance' => $this->credit->balance,
                'payment_id' => $payment->id
            ]);
            
            if ($this->credit->status === 'paid' || $this->credit->balance <= 0) {
                session()->flash('success', 'Credit fully paid.');
                return redirect()->route('admin.credits.index');
            } else {
                session()->flash('success', 'Credit partially paid.');
                return redirect()->route('admin.credits.show', $this->credit->id);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->showConfirmation = false;
            
            // Log the error
            \Log::error('Credit payment failed', [
                'credit_id' => $this->credit->id,
                'amount' => $this->amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->notifyError('Failed to record payment: ' . $e->getMessage());
        }
    }
    
    protected function getValidationMessages(): array
    {
        return [
            'amount.required' => 'Payment amount is required.',
            'amount.numeric' => 'Payment amount must be a valid number.',
            'amount.min' => 'Payment amount must be at least 0.01.',
            'amount.max' => 'Payment amount cannot exceed the credit balance.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Please select a valid payment method.',
            'payment_date.required' => 'Payment date is required.',
            'payment_date.date' => 'Please enter a valid payment date.',
            'reference.max' => 'Reference cannot exceed 255 characters.',
            'bank_account_id.required' => 'Bank account is required for bank transfers.',
            'bank_account_id.exists' => 'Selected bank account does not exist.',
            'account_holder_name.required' => 'Account holder name is required.',
            'account_holder_name.max' => 'Account holder name cannot exceed 255 characters.',
            'reference_no.required' => 'Reference number is required.',
            'reference_no.max' => 'Reference number cannot exceed 255 characters.',
            'transaction_number.required' => 'Transaction number is required for Telebirr payments.',
            'transaction_number.max' => 'Transaction number cannot exceed 255 characters.',
            'receiver_bank_name.required' => 'Receiver bank name is required.',
            'receiver_bank_name.max' => 'Receiver bank name cannot exceed 255 characters.',
            'receiver_account_holder.required' => 'Receiver account holder name is required.',
            'receiver_account_holder.max' => 'Receiver account holder name cannot exceed 255 characters.',
            'receiver_account_number.required' => 'Receiver account number is required.',
            'receiver_account_number.max' => 'Receiver account number cannot exceed 255 characters.',
            'attachment.file' => 'Please upload a valid file.',
            'attachment.mimes' => 'File must be a PDF, JPG, JPEG, or PNG.',
            'attachment.max' => 'File size cannot exceed 2MB.',
        ];
    }

    public function getBanksProperty()
    {
        $bankService = new BankService();
        return $bankService->getEthiopianBanks();
    }

    public function render()
    {
        return view('livewire.credit-payment.create')
            ->title('Make Payment - Credit #' . $this->credit->reference_no);
    }
}
