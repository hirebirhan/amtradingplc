<?php

namespace App\Livewire\CreditPayment;

use App\Models\Credit;
use App\Models\BankAccount;
use App\Services\BankService;
use App\Services\CreditPaymentService;
use App\Traits\HasNotifications;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads, HasNotifications;
    
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
        $this->amount = $credit->balance;
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
        if (
            $this->payment_method === 'bank_transfer'
        ) {
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
        
        // Check if full payment requires closing prices (for payable credits)
        if ($this->credit->credit_type === 'payable' && $this->amount >= $this->credit->balance && $this->paymentType === 'down_payment') {
            // If user selected down payment but is paying full amount, warn them
            $this->addError('paymentType', 'You are paying the full amount but selected "Down Payment". Please select "Closing Payment" to enter closing prices, or reduce the payment amount.');
            return;
        }
        
        if ($this->credit->credit_type === 'payable' && $this->paymentType === 'closing_payment') {
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
                    $this->closingPrices[$item->item_id] = $item->unit_cost;
                }
            }
        }
    }
    
    public function processClosingPayment()
    {
        // Validate closing prices
        $this->validate([
            'closingPrices' => 'required|array',
            'closingPrices.*' => 'required|numeric|min:0',
        ], [
            'closingPrices.required' => 'Please enter closing prices for all items.',
            'closingPrices.*.required' => 'Please enter a closing price for this item.',
            'closingPrices.*.numeric' => 'Closing price must be a valid number.',
            'closingPrices.*.min' => 'Closing price must be greater than or equal to 0.',
        ]);
        
        try {
            $creditPaymentService = new CreditPaymentService();
            $result = $creditPaymentService->processEarlyClosureWithNegotiatedPrices($this->credit, $this->closingPrices, true);
            
            if ($result['success']) {
                $this->notifySuccess($result['message']);
                return redirect()->route('admin.credits.show', $this->credit->id);
            } else {
                $this->notifyError($result['message']);
            }
        } catch (\Exception $e) {
            $this->notifyError('Failed to calculate savings. Please check closing prices.');
        }
    }
    
    public function store()
    {
        \Log::info('CreditPayment Create@store called', [
            'user_id' => auth()->id() ?? null,
            'credit_id' => $this->credit->id ?? null,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
        ]);
        
        // Add a small delay to ensure previous renders are complete
        usleep(500000); // 500ms delay
        
        // Show loading state to prevent double submissions
        $this->dispatch('payment-processing');
        
        // Detailed logging for debugging performance issues
        \Log::info('Starting credit payment store process', [
            'credit_id' => $this->credit->id,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'timestamp' => now()->format('Y-m-d H:i:s.v')
        ]);
        
        $rules = [
            'amount' => 'required|numeric|min:0.01|max:' . $this->credit->balance,
            'payment_method' => 'required|string|in:cash,bank_transfer,telebirr,check',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
        ];
        
        // Add conditional validation rules based on payment method
        if (
            $this->payment_method === 'bank_transfer'
        ) {
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
        
        try {
            // Set DB transaction isolation level to ensure data consistency
            DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE');
            DB::beginTransaction();
            
            // Prepare reference number based on payment method
            $referenceNumber = $this->reference_no;
            if ($this->payment_method === 'telebirr') {
                $referenceNumber = $this->transaction_number;
            }
            
            // Prepare notes with additional payment details
            $paymentNotes = $this->reference;
            if ($this->payment_method === 'bank_transfer' && $this->bank_account_id) {
                $bankAccount = $this->bankAccounts->firstWhere('id', $this->bank_account_id);
                if ($bankAccount) {
                    $paymentNotes = trim("Bank: {$bankAccount->bank_name}, Account: {$bankAccount->account_number}" . 
                        ($this->reference ? "\n{$this->reference}" : ''));
                }
            }
            
            // Add payment to credit with new fields
            $payment = $this->credit->addPayment(
                $this->amount,
                $this->payment_method,
                $referenceNumber,
                $paymentNotes,
                $this->payment_date,
                $this->reference,
                $this->receiver_bank_name,
                $this->receiver_account_holder,
                $this->receiver_account_number
            );
            
            // If this credit is linked to a purchase or sale, update that too
            if ($this->credit->reference_type === 'purchase' && $this->credit->reference_id) {
                $purchase = $this->credit->purchase;
                if ($purchase) {
                    $purchase->addPayment(
                        $this->amount,
                        $this->payment_method,
                        $referenceNumber,
                        $paymentNotes,
                        $this->payment_date,
                        $this->reference,
                        $this->receiver_bank_name,
                        $this->receiver_account_holder,
                        $this->receiver_account_number
                    );
                    
                    // Update payment status if fully paid
                    if ($purchase->due_amount <= 0) {
                        $purchase->payment_status = 'paid';
                        $purchase->save();
                    }
                }
            } elseif ($this->credit->reference_type === 'sale' && $this->credit->reference_id) {
                $sale = $this->credit->sale;
                if ($sale) {
                    $sale->addPayment(
                        $this->amount,
                        $this->payment_method,
                        $referenceNumber,
                        $paymentNotes,
                        $this->payment_date,
                        $this->reference,
                        $this->receiver_bank_name,
                        $this->receiver_account_holder,
                        $this->receiver_account_number
                    );
                    
                    // Update payment status if fully paid
                    if ($sale->due_amount <= 0) {
                        $sale->payment_status = 'paid';
                        $sale->save();
                    }
                }
            }
            
            // Ensure all changes are saved before committing
            $this->credit->refresh();
            
            DB::commit();
            
            // If credit is now fully paid, redirect to the credits index page
            if ($this->credit->status === 'paid' || $this->credit->balance <= 0) {
                $this->notifySuccess('Payment of ETB ' . number_format($this->amount, 2) . ' recorded. Credit fully paid!');
                return redirect()->route('admin.credits.index');
            }
            
            // Otherwise, show the credit details
            $this->notifySuccess('Payment of ETB ' . number_format($this->amount, 2) . ' recorded successfully.');
            return redirect()->route('admin.credits.show', $this->credit->id);
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Credit payment failed', [
                'credit_id' => $this->credit->id,
                'amount' => $this->amount,
                'error' => $e->getMessage()
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
