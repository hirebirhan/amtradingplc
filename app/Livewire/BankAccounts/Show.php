<?php

namespace App\Livewire\BankAccounts;

use App\Models\BankAccount;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.app')]
class Show extends Component
{
    public BankAccount $bankAccount;

    public function mount(BankAccount $bankAccount)
    {
        $this->bankAccount = $bankAccount;
    }

    public function delete()
    {
        $currentUser = Auth::user();
        
        // Check if user has permission to delete
        if (!$currentUser->isSuperAdmin() && $currentUser->branch_id !== $this->bankAccount->branch_id) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'You do not have permission to delete this bank account.']);
            return;
        }

        $this->bankAccount->delete();

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Bank account deleted successfully.']);

        return redirect()->route('admin.bank-accounts.index');
    }

    public function render()
    {
        return view('livewire.bank-accounts.show');
    }
}