<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'expense_date';
    public $sortDirection = 'desc';
    public $categoryFilter = '';
    public $paymentMethodFilter = '';
    public $dateFrom = '';
    public $dateTo = '';

    protected $listeners = [
        'delete' => 'delete',
        'getExpenseDetails' => 'getExpenseDetails'
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'paymentMethodFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'expense_date'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatingPaymentMethodFilter()
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

    public function clearSearch()
    {
        $this->search = '';
    }

    public function clearFilters()
    {
        $this->reset(['search', 'categoryFilter', 'paymentMethodFilter', 'dateFrom', 'dateTo']);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Get expense details for confirmation before deletion
     */
    public function getExpenseDetails($id)
    {
        $expense = Expense::findOrFail($id);
        
        $this->dispatch('showDeleteConfirmation', [
            'reference' => $expense->reference_no,
            'date' => $expense->expense_date->format('M d, Y'),
            'category' => $expense->category,
            'amount' => number_format($expense->amount, 2)
        ]);
    }

    /**
     * Delete an expense
     */
    public function delete($id)
    {
        $expense = Expense::findOrFail($id);
        
        try {
            $expense->delete();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Expense '{$expense->reference_no}' deleted successfully."
            ]);
            
            $this->dispatch('expenseDeleted');
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Error deleting expense: {$e->getMessage()}"
            ]);
        }
    }

    /**
     * Export expenses to CSV/Excel
     */
    public function exportExpenses()
    {
        return response()->streamDownload(function() {
            echo $this->generateExpensesExport();
        }, 'expenses-export-' . now()->format('Y-m-d') . '.csv');
    }

    /**
     * Generate CSV data for export
     */
    private function generateExpensesExport()
    {
        $expenses = $this->getFilteredExpenses(false)->get();
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'ID', 'Reference', 'Category', 'Amount', 'Payment Method', 
            'Date', 'Note', 'Is Recurring', 'Created By', 'Created At'
        ]);
        
        foreach ($expenses as $expense) {
            fputcsv($output, [
                $expense->id,
                $expense->reference_no,
                $expense->category,
                number_format($expense->amount, 2),
                ucwords(str_replace('_', ' ', $expense->payment_method)),
                $expense->expense_date->format('Y-m-d'),
                $expense->note,
                $expense->is_recurring ? 'Yes' : 'No',
                $expense->user->name ?? 'N/A',
                $expense->created_at->format('Y-m-d H:i:s')
            ]);
        }
        
        fclose($output);
    }

    /**
     * Get unique expense categories
     */
    public function getCategoriesProperty()
    {
        $categories = Expense::distinct('category')
            ->orderBy('category')
            ->pluck('category')
            ->toArray();
            
        $result = [];
        foreach ($categories as $category) {
            $result[$category] = $category;
        }
        
        return $result;
    }

    /**
     * Get this month's total expenses
     */
    public function getCurrentMonthTotalProperty()
    {
        return Expense::whereYear('expense_date', now()->year)
            ->whereMonth('expense_date', now()->month)
            ->sum('amount');
    }

    /**
     * Get the average expense amount
     */
    public function getAverageAmountProperty()
    {
        $count = Expense::count();
        if ($count === 0) {
            return 0;
        }
        
        return Expense::sum('amount') / $count;
    }

    /**
     * Get filtered expenses query
     */
    private function getFilteredExpenses($paginate = true)
    {
        $query = Expense::query()
            ->with(['user'])
            ->when($this->search, function (Builder $query) {
                $query->where(function($q) {
                    $q->where('reference_no', 'like', '%' . $this->search . '%')
                      ->orWhere('category', 'like', '%' . $this->search . '%')
                      ->orWhere('note', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->categoryFilter, function (Builder $query) {
                $query->where('category', $this->categoryFilter);
            })
            ->when($this->paymentMethodFilter, function (Builder $query) {
                $query->where('payment_method', $this->paymentMethodFilter);
            })
            ->when($this->dateFrom, function (Builder $query) {
                $query->whereDate('expense_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function (Builder $query) {
                $query->whereDate('expense_date', '<=', $this->dateTo);
            })
            ->orderBy($this->sortField, $this->sortDirection);
            
        return $paginate 
            ? $query->paginate($this->perPage) 
            : $query;
    }

    public function render()
    {
        return view('livewire.expenses.index', [
            'expenses' => $this->getFilteredExpenses(),
            'currentMonthTotal' => $this->getCurrentMonthTotalProperty(),
            'averageAmount' => $this->getAverageAmountProperty()
        ])->layout('layouts.app');
    }
}