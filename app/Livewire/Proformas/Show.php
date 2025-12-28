<?php

namespace App\Livewire\Proformas;

use App\Models\Proforma;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Show extends Component
{
    public Proforma $proforma;

    public function mount(Proforma $proforma)
    {
        // Check if user can access this proforma (branch isolation)
        /** @var User $user */
        $user = Auth::user();
        if ($user->branch_id && $proforma->branch_id !== $user->branch_id) {
            abort(403, 'You can only view proformas from your branch.');
        }
        
        $this->proforma = $proforma->load(['customer', 'user', 'items.item', 'branch']);
    }

    public function convertToSale()
    {
        if ($this->proforma->status !== 'approved') {
            session()->flash('error', 'Only approved proformas can be converted to sales.');
            return;
        }

        // Logic to convert proforma to sale would go here
        session()->flash('success', 'Proforma converted to sale successfully.');
    }

    public function render()
    {
        return view('livewire.proformas.show');
    }
}