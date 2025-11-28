<?php

namespace App\Livewire\Transfers;

use App\Models\Transfer;
use App\Enums\TransferStatus;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Pending extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    public function render()
    {
        $transfers = Transfer::query()
            ->forUser(auth()->user())
            ->where('status', TransferStatus::PENDING)
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('reference_code', 'like', '%' . $this->search . '%')
                      ->orWhereHas('sourceWarehouse', function($subQ) {
                          $subQ->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('destinationWarehouse', function($subQ) {
                          $subQ->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('sourceBranch', function($subQ) {
                          $subQ->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('destinationBranch', function($subQ) {
                          $subQ->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->with(['sourceWarehouse', 'destinationWarehouse', 'sourceBranch', 'destinationBranch', 'user', 'items'])
            ->paginate($this->perPage);

        return view('livewire.transfers.pending', [
            'transfers' => $transfers,
        ])->title('Pending Approvals');
    }
}