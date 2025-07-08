<?php

namespace App\Livewire\CreditPayment;

use App\Models\Credit;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;
    
    public Credit $credit;
    
    public function mount(Credit $credit)
    {
        $this->credit = $credit;
    }
    
    public function refreshData()
    {
        // Force reload credit data from database
        $this->credit->refresh();
    }
    
    public function render()
    {
        // Ensure we get fresh data
        $payments = $this->credit->payments()
            ->orderBy('payment_date', 'desc')
            ->paginate(15);
            
        return view('livewire.credit-payment.index', [
            'credit' => $this->credit,
            'payments' => $payments
        ])->title('Payment History - Credit #' . $this->credit->reference_no);
    }
}
