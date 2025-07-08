<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Show extends Component
{
    public Sale $sale;

    public function mount(Sale $sale)
    {
        $this->sale = $sale;
        $this->sale->load(['items.item', 'customer', 'warehouse', 'user']);
    }

    public function render()
    {
        return view('livewire.sales.show')
            ->title('Sale #' . $this->sale->reference_no);
    }
}