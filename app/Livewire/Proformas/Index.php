<?php

namespace App\Livewire\Proformas;

use App\Models\Proforma;
use App\Models\Customer;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $dateFilter = '';
    public $showDeleteModal = false;
    public $proformaToDelete = null;

    protected $queryString = ['search', 'statusFilter', 'dateFilter'];

    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'dateFilter']);
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        
        $proformas = Proforma::query()
            ->when($user->branch_id, fn($query) => $query->where('branch_id', $user->branch_id))
            ->when($this->search, function($query) {
                $query->where('reference_no', 'like', '%' . $this->search . '%')
                      ->orWhereHas('customer', function($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
            })
            ->when($this->statusFilter, fn($query) => $query->where('status', $this->statusFilter))
            ->when($this->dateFilter, fn($query) => $query->whereDate('created_at', $this->dateFilter))
            ->with(['customer', 'user', 'branch'])
            ->latest()
            ->paginate(15);

        return view('livewire.proformas.index', compact('proformas'));
    }

    public function confirmDelete($proformaId)
    {
        $this->proformaToDelete = $proformaId;
        $this->showDeleteModal = true;
    }

    public function deleteProforma()
    {
        if ($this->proformaToDelete) {
            Proforma::find($this->proformaToDelete)->delete();
            $this->showDeleteModal = false;
            $this->proformaToDelete = null;
            session()->flash('success', 'Proforma deleted successfully.');
        }
    }
}