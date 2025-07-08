<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Supplier $supplier;

    public function mount(Supplier $supplier)
    {
        // Check authorization
        if (!Auth::user()->can('view', $supplier)) {
            session()->flash('error', 'You are not authorized to view this supplier.');
            return redirect()->route('admin.suppliers.index');
        }

        $this->supplier = $supplier;
    }

    public function delete()
    {
        // Check permissions
        if (!Auth::user()->can('delete', $this->supplier)) {
            $this->dispatchBrowserEvent('toast', [
                'type' => 'error',
                'message' => 'You are not authorized to delete this supplier.',
            ]);
            return;
        }

        $this->supplier->delete();

        session()->flash('message', 'Supplier deleted successfully!');
        return redirect()->route('admin.suppliers.index');
    }

    public function render()
    {
        // Fetch compact financial stats
        $totalPurchases = $this->supplier->purchases()->count();
        $totalSpent     = $this->supplier->purchases()->sum('total_amount');
        $avgPurchase    = $this->supplier->purchases()->avg('total_amount');

        // Get the latest 3 purchases for quick overview
        $recentPurchases = $this->supplier->purchases()->latest()->take(3)->get();

        return view('livewire.suppliers.show', [
            'totalPurchases'  => $totalPurchases,
            'totalSpent'      => $totalSpent,
            'avgPurchase'     => $avgPurchase,
            'recentPurchases' => $recentPurchases,
        ])->title('Supplier Details');
    }
}