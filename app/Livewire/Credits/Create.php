<?php

namespace App\Livewire\Credits;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Supplier;
use App\Traits\HasFlashMessages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

#[Layout('layouts.app')]
class Create extends Component
{
    use HasFlashMessages;
    public $form = [
        'credit_type' => '',
        'reference_no' => '',
        'party_id' => '',
        'amount' => '',
        'credit_date' => '',
        'due_date' => '',
        'reference_type' => '',
        'reference_id' => '',
        'description' => '',
    ];

    public function mount()
    {
        $this->form['credit_date'] = now()->format('Y-m-d');
        $this->form['due_date'] = now()->addDays(30)->format('Y-m-d');
        $this->form['reference_no'] = $this->generateUniqueReferenceNumber();
    }

    /**
     * Generate a unique reference number for credits
     */
    private function generateUniqueReferenceNumber()
    {
        do {
            $referenceNo = 'CR-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
            $exists = Credit::where('reference_no', $referenceNo)->exists();
        } while ($exists);
        
        return $referenceNo;
    }

    public function getCustomersProperty()
    {
        return Customer::orderBy('name')->get();
    }

    public function getSuppliersProperty()
    {
        return Supplier::orderBy('name')->get();
    }

    public function save()
    {
        $this->validate([
            'form.credit_type' => 'required|in:receivable,payable',
            'form.reference_no' => 'required|string',
            'form.party_id' => 'required|integer',
            'form.amount' => 'required|numeric|min:0',
            'form.credit_date' => 'required|date',
            'form.due_date' => 'required|date|after_or_equal:form.credit_date',
            'form.reference_type' => 'nullable|in:sale,purchase,manual',
            'form.reference_id' => 'nullable|integer',
            'form.description' => 'nullable|string',
        ]);

        $credit = new Credit();
        $credit->fill($this->form);

        // Set the appropriate relationship based on credit type
        if ($this->form['credit_type'] === 'receivable') {
            $credit->customer_id = $this->form['party_id'];
        } else {
            $credit->supplier_id = $this->form['party_id'];
        }

        // Set default values
        $credit->status = 'active';
        $credit->paid_amount = 0;
        $credit->balance = $this->form['amount'];
        $credit->user_id = Auth::id();
        $credit->branch_id = Auth::user()->branch_id;

        $credit->save();

        $this->flashCrudSuccess('credit', 'created');
        return redirect()->route('admin.credits.show', $credit);
    }

    public function cancel()
    {
        return redirect()->route('admin.credits.index');
    }

    public function render()
    {
        return view('livewire.credits.create');
    }
}
