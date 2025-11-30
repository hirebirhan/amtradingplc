<?php

namespace App\Livewire\Transfers;

use App\Models\Transfer;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $transferDirection = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'transferDirection' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTransferDirection()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    /**
     * Get the total count of transfers this month
     */
    public function getThisMonthCountProperty()
    {
        return Transfer::forUser(auth()->user())
            ->whereYear('date_initiated', now()->year)
            ->whereMonth('date_initiated', now()->month)
            ->count();
    }

    /**
     * Get the count of outgoing transfers
     */
    public function getOutgoingTransfersCountProperty()
    {
        $user = auth()->user();
        return Transfer::forUser($user)
            ->where(function($query) use ($user) {
                $query->where(function($q) use ($user) {
                    $q->where('source_type', 'warehouse')
                      ->where('source_id', $user->warehouse_id);
                })->orWhere(function($q) use ($user) {
                    $q->where('source_type', 'branch')
                      ->where('source_id', $user->branch_id);
                });
            })
            ->count();
    }

    /**
     * Get the count of incoming transfers
     */
    public function getIncomingTransfersCountProperty()
    {
        $user = auth()->user();
        return Transfer::forUser($user)
            ->where(function($query) use ($user) {
                $query->where(function($q) use ($user) {
                    $q->where('destination_type', 'warehouse')
                      ->where('destination_id', $user->warehouse_id);
                })->orWhere(function($q) use ($user) {
                    $q->where('destination_type', 'branch')
                      ->where('destination_id', $user->branch_id);
                });
            })
            ->count();
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

    /**
     * Get filtered transfers query based on search and filters
     */
    protected function getTransfersQuery()
    {
        return Transfer::query()
            ->whereIn('status', ['completed', 'rejected'])
            ->forUser(auth()->user())
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
            ->when($this->transferDirection, function($query) {
                $user = auth()->user();
                if ($this->transferDirection === 'outgoing') {
                    $query->where(function($q) use ($user) {
                        $q->where(function($subQ) use ($user) {
                            $subQ->where('source_type', 'warehouse')
                                 ->where('source_id', $user->warehouse_id);
                        })->orWhere(function($subQ) use ($user) {
                            $subQ->where('source_type', 'branch')
                                 ->where('source_id', $user->branch_id);
                        });
                    });
                } elseif ($this->transferDirection === 'incoming') {
                    $query->where(function($q) use ($user) {
                        $q->where(function($subQ) use ($user) {
                            $subQ->where('destination_type', 'warehouse')
                                 ->where('destination_id', $user->warehouse_id);
                        })->orWhere(function($subQ) use ($user) {
                            $subQ->where('destination_type', 'branch')
                                 ->where('destination_id', $user->branch_id);
                        });
                    });
                }
            })
            ->when($this->dateFrom, function($query) {
                $query->whereDate('date_initiated', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function($query) {
                $query->whereDate('date_initiated', '<=', $this->dateTo);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->with(['sourceWarehouse', 'destinationWarehouse', 'sourceBranch', 'destinationBranch', 'user', 'items']);
    }



    /**
     * Reset all filters
     */
    public function resetFilters()
    {
        $this->search = '';
        $this->transferDirection = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
        session()->flash('success', 'Filters reset successfully.');
    }


    


    public function render()
    {
        $transfers = $this->getTransfersQuery()->paginate($this->perPage);

        return view('livewire.transfers.index', [
            'transfers' => $transfers,
            'thisMonthCount' => $this->thisMonthCount,
            'outgoingTransfersCount' => $this->outgoingTransfersCount,
            'incomingTransfersCount' => $this->incomingTransfersCount,
        ])->title('Stock Transfers');
    }
}