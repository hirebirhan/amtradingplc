<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Customer $customer;

    public function mount(Customer $customer)
    {
        // Check authorization
        if (!Auth::user()->can('view', $customer)) {
            session()->flash('error', 'You are not authorized to view this customer.');
            return redirect()->route('admin.customers.index');
        }

        $this->customer = $customer;
    }

    public function delete()
    {
        // Check permissions
        if (!Auth::user()->can('delete', $this->customer)) {
            $this->dispatchBrowserEvent('toast', [
                'type' => 'error',
                'message' => 'You are not authorized to delete this customer.',
            ]);
            return;
        }

        $this->customer->delete();

        session()->flash('message', 'Customer deleted successfully!');
        return redirect()->route('admin.customers.index');
    }

    public function render()
    {
        // Load any recent sales associated with this customer
        $sales = $this->customer->sales()
            ->latest('sale_date')
            ->limit(5)
            ->get();

        // Calculate total spent and other stats
        $stats = [
            'total_spent' => $this->customer->sales()->sum('total_amount'),
            'total_sales' => $this->customer->sales()->count(),
            'average_sale' => $this->customer->sales()->count() > 0
                ? $this->customer->sales()->sum('total_amount') / $this->customer->sales()->count()
                : 0,
        ];

        return view('livewire.customers.show', [
            'sales' => $sales,
            'stats' => $stats,
        ])->title('Customer Details');
    }
}