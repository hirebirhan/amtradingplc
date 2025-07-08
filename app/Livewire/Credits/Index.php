<?php

namespace App\Livewire\Credits;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use App\Exports\CreditsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'bootstrap';

    public $filters = [
        'type' => '',
        'status' => '',
        'date_from' => '',
        'date_to' => '',
        'customer' => '',
        'supplier' => '',
        'amount_min' => '',
        'amount_max' => '',
    ];
    
    public $search = '';
    public $showPaidCredits = false;
    public $groupByCustomerSupplier = false;
    public $perPage = 10;
    
    // Properties for searchable dropdowns
    public $customerSearch = '';
    public $supplierSearch = '';
    public $customers = [];
    public $suppliers = [];

    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public function mount()
    {
        $this->filters = [
            'type' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'customer' => '',
            'supplier' => '',
            'amount_min' => '',
            'amount_max' => '',
        ];

        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        
        // Load initial options for dropdowns
        $this->loadCustomers();
        $this->loadSuppliers();
        
        // Check if there are any credits in the database
        $totalCredits = Credit::count();
        
        if ($totalCredits > 0) {
            // Get a sample of credits to check their status
            $sampleCredits = Credit::select('id', 'status', 'balance', 'credit_type')
                ->limit(5)
                ->get();
        }
    }
    
    // Load customers for the dropdown with optional search filter
    public function loadCustomers()
    {
        $query = Customer::query()
            ->select('id', 'name', 'phone');
            
        if (!empty($this->customerSearch)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->customerSearch . '%')
                  ->orWhere('phone', 'like', '%' . $this->customerSearch . '%');
            });
        }
        
        // Get active customers first, limited to 10 for performance
        $this->customers = $query->orderBy('name')
            ->limit(10)
            ->get()
            ->toArray();
    }
    
    // Load suppliers for the dropdown with optional search filter
    public function loadSuppliers()
    {
        $query = Supplier::query()
            ->select('id', 'name', 'phone');
            
        if (!empty($this->supplierSearch)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->supplierSearch . '%')
                  ->orWhere('phone', 'like', '%' . $this->supplierSearch . '%');
            });
        }
        
        // Get active suppliers first, limited to 10 for performance
        $this->suppliers = $query->orderBy('name')
            ->limit(10)
            ->get()
            ->toArray();
    }
    
    // Update customer search and reload options
    public function updatedCustomerSearch()
    {
        $this->loadCustomers();
    }
    
    // Update supplier search and reload options
    public function updatedSupplierSearch()
    {
        $this->loadSuppliers();
    }
    
    // Select a customer from dropdown
    public function selectCustomer($id)
    {
        $this->filters['customer'] = $id;
        $this->resetPage();
    }
    
    // Select a supplier from dropdown
    public function selectSupplier($id)
    {
        $this->filters['supplier'] = $id;
        $this->resetPage();
    }
    
    // Clear customer selection
    public function clearCustomer()
    {
        $this->filters['customer'] = '';
        $this->customerSearch = '';
        $this->resetPage();
    }
    
    // Clear supplier selection
    public function clearSupplier()
    {
        $this->filters['supplier'] = '';
        $this->supplierSearch = '';
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->filters = [
            'type' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'customer' => '',
            'supplier' => '',
            'amount_min' => '',
            'amount_max' => '',
        ];
        $this->search = '';
        $this->customerSearch = '';
        $this->supplierSearch = '';
        $this->resetPage();
    }
    
    public function togglePaidCredits()
    {
        $this->showPaidCredits = !$this->showPaidCredits;
        $this->resetPage();
    }

    public function toggleGrouping()
    {
        $this->groupByCustomerSupplier = !$this->groupByCustomerSupplier;
        $this->resetPage();
    }

    public function getStatusColor($status)
    {
        return match($status) {
            'active' => 'primary',
            'partially_paid' => 'warning',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'secondary',
            default => 'info',
        };
    }

    /**
     * Calculate effective balance and status considering savings from closing prices
     */
    public function getEffectiveCreditInfo($credit)
    {
        // If it's a payable credit with a purchase reference, check for closing prices
        if ($credit->credit_type === 'payable' && $credit->reference_type === 'purchase' && $credit->purchase) {
            $savingsInfo = $this->calculateSavingsForCredit($credit);
            
            if ($savingsInfo && $savingsInfo['total_savings'] > 0) {
                $effectiveBalance = $savingsInfo['effective_balance'];
                $effectiveStatus = $effectiveBalance <= 0 ? 'paid' : $credit->status;
                
                return [
                    'effective_balance' => $effectiveBalance,
                    'effective_status' => $effectiveStatus,
                    'has_savings' => true,
                    'total_savings' => $savingsInfo['total_savings']
                ];
            }
        }
        
        // No savings, return original values
        return [
            'effective_balance' => $credit->balance,
            'effective_status' => $credit->status,
            'has_savings' => false,
            'total_savings' => 0
        ];
    }

    /**
     * Calculate savings for a specific credit
     */
    private function calculateSavingsForCredit($credit)
    {
        if (!$credit->purchase) {
            return null;
        }

        $totalOriginalCost = 0;
        $totalClosingCost = 0;
        $hasClosingPrices = false;

        foreach ($credit->purchase->items as $item) {
            $unitQuantity = $item->item->unit_quantity ?: 1;
            $originalCostPerItem = $item->unit_cost / $unitQuantity;
            $originalTotalCost = $item->unit_cost * $item->quantity;
            $totalOriginalCost += $originalTotalCost;

            // Check if there's a closing price for this item
            $closingPrice = $item->closing_price ?? null;
            if ($closingPrice && $closingPrice > 0) {
                $hasClosingPrices = true;
                $closingTotalCost = $closingPrice * $item->quantity;
                $totalClosingCost += $closingTotalCost;
            } else {
                $totalClosingCost += $originalTotalCost;
            }
        }

        if (!$hasClosingPrices) {
            return null;
        }

        $totalSavings = $totalOriginalCost - $totalClosingCost;
        $effectiveBalance = max(0, $totalClosingCost - $credit->paid_amount);

        return [
            'total_original_cost' => $totalOriginalCost,
            'total_closing_cost' => $totalClosingCost,
            'total_savings' => $totalSavings,
            'effective_balance' => $effectiveBalance,
            'has_closing_prices' => true
        ];
    }

    public function delete($id)
    {
        $credit = Credit::findOrFail($id);

        // Check if credit can be deleted (e.g., not paid)
        if ($credit->status === 'paid') {
            session()->flash('error', 'Cannot delete a paid credit.');
            return;
        }

        $credit->delete();
        session()->flash('success', 'Credit deleted successfully.');
    }

    public function render()
    {
        // Start with a clean query builder
        $query = Credit::query()
            ->with(['customer', 'supplier', 'purchase.items']);

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->whereHas('customer', function($customerQuery) {
                    $customerQuery->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('supplier', function($supplierQuery) {
                    $supplierQuery->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('reference_number', 'like', '%' . $this->search . '%')
                ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filtering
        if (!empty($this->filters['status'])) {
            // If a specific status is selected, use it
            $query->where('status', $this->filters['status']);
        } elseif (!$this->showPaidCredits) {
            // Otherwise, if not showing paid, only show active credits
            // Note: We'll filter by effective status after loading the data
            $query->where(function($q) {
                $q->whereIn('status', ['active', 'partially_paid', 'overdue'])
                  ->where('balance', '>', 0);
            });
        }
        // If no status is selected AND showPaidCredits is true, no status filter is applied (shows all)
        
        // Apply remaining filters
        if ($this->filters['type']) {
            $query->where('credit_type', $this->filters['type']);
        }
        
        if ($this->filters['date_from']) {
            $query->whereDate('credit_date', '>=', $this->filters['date_from']);
        }
        
        if ($this->filters['date_to']) {
            $query->whereDate('credit_date', '<=', $this->filters['date_to']);
        }

        // Customer filter (for receivables)
        if ($this->filters['customer']) {
            $query->where(function($q) {
                $q->where('credit_type', 'receivable')
                  ->where('customer_id', $this->filters['customer']);
            });
        }
        
        // Supplier filter (for payables)
        if ($this->filters['supplier']) {
            $query->where(function($q) {
                $q->where('credit_type', 'payable')
                  ->where('supplier_id', $this->filters['supplier']);
            });
        }
        
        // Apply branch filter for non-admin users
        if (!Auth::user()->hasRole('admin')) {
            $userBranchId = Auth::user()->branch_id;
            if ($userBranchId !== null) {
                $query->where('branch_id', $userBranchId);
            }
        }

        // Get counts for filters using separate queries
        $activeCount = Credit::whereIn('status', ['active', 'partially_paid', 'overdue'])
            ->where(function($q) {
                $q->where('balance', '>', 0)
                  ->orWhereNull('balance');
            })
            ->count();
            
        $paidCount = Credit::where(function($q) {
            $q->where('status', 'paid')
              ->orWhere('balance', '<=', 0);
        })->count();

        // Get customer and supplier info if selected
        $selectedCustomer = null;
        $selectedSupplier = null;
        
        if ($this->filters['customer']) {
            $selectedCustomer = Customer::find($this->filters['customer']);
        }
        
        if ($this->filters['supplier']) {
            $selectedSupplier = Supplier::find($this->filters['supplier']);
        }

        // For grouped view, modify the query to show summary data
        if ($this->groupByCustomerSupplier) {
            $credits = $this->getGroupedCredits($query);
            return view('livewire.credits.index-grouped', [
                'groupedCredits' => $credits,
                'paidCount' => $paidCount,
                'activeCount' => $activeCount,
                'selectedCustomer' => $selectedCustomer,
                'selectedSupplier' => $selectedSupplier
            ]);
        } else {
            // Order by created_at for non-grouped view
            $query->orderBy('created_at', 'desc');
            $credits = $query->paginate($this->perPage);
            
            // Filter by effective status if needed
            if (!empty($this->filters['status'])) {
                $credits->getCollection()->transform(function ($credit) {
                    $effectiveInfo = $this->getEffectiveCreditInfo($credit);
                    $credit->effective_status = $effectiveInfo['effective_status'];
                    $credit->effective_balance = $effectiveInfo['effective_balance'];
                    return $credit;
                });
            }
            
            return view('livewire.credits.index', [
                'credits' => $credits,
                'paidCount' => $paidCount,
                'activeCount' => $activeCount,
                'selectedCustomer' => $selectedCustomer,
                'selectedSupplier' => $selectedSupplier
            ]);
        }
    }

    /**
     * Get grouped credits by customer/supplier
     */
    private function getGroupedCredits($query)
    {
        // Clone query to avoid modifying the original
        $clonedQuery = clone $query;
        
        // Apply search filter for grouped view
        if (!empty($this->search)) {
            $clonedQuery->where(function($q) {
                $q->whereHas('customer', function($customerQuery) {
                    $customerQuery->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('supplier', function($supplierQuery) {
                    $supplierQuery->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('reference_number', 'like', '%' . $this->search . '%')
                ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }
        
        // For receivables, group by customer
        $receivables = (clone $clonedQuery)
            ->where('credit_type', 'receivable')
            ->select(
                'customer_id',
                DB::raw('COUNT(*) as credit_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(paid_amount) as total_paid'),
                DB::raw('SUM(balance) as total_balance'),
                DB::raw('MAX(credit_date) as latest_credit_date'),
                DB::raw('MIN(due_date) as earliest_due_date')
            )
            ->with('customer')
            ->groupBy('customer_id')
            ->orderBy('total_balance', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->customer_id,
                    'name' => $item->customer->name ?? 'Unknown Customer',
                    'type' => 'receivable',
                    'count' => $item->credit_count,
                    'total_amount' => $item->total_amount,
                    'total_paid' => $item->total_paid,
                    'total_balance' => $item->total_balance,
                    'latest_credit_date' => $item->latest_credit_date,
                    'earliest_due_date' => $item->earliest_due_date,
                    'entity_type' => 'customer',
                    'entity_id' => $item->customer_id
                ];
            });
            
        // For payables, group by supplier
        $payables = (clone $clonedQuery)
            ->where('credit_type', 'payable')
            ->select(
                'supplier_id',
                DB::raw('COUNT(*) as credit_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(paid_amount) as total_paid'),
                DB::raw('SUM(balance) as total_balance'),
                DB::raw('MAX(credit_date) as latest_credit_date'),
                DB::raw('MIN(due_date) as earliest_due_date')
            )
            ->with('supplier')
            ->groupBy('supplier_id')
            ->orderBy('total_balance', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->supplier_id,
                    'name' => $item->supplier->name ?? 'Unknown Supplier',
                    'type' => 'payable',
                    'count' => $item->credit_count,
                    'total_amount' => $item->total_amount,
                    'total_paid' => $item->total_paid,
                    'total_balance' => $item->total_balance,
                    'latest_credit_date' => $item->latest_credit_date,
                    'earliest_due_date' => $item->earliest_due_date,
                    'entity_type' => 'supplier',
                    'entity_id' => $item->supplier_id
                ];
            });

        // Combine both collections and sort
        $combined = $receivables->concat($payables)->sortByDesc('total_balance');
        
        // Manual pagination
        $page = request()->get('page', 1);
        
        $items = $combined->forPage($page, $this->perPage);
        
        return new LengthAwarePaginator(
            $items,
            $combined->count(),
            $this->perPage,
            $page,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Get filtered credits query for export
     */
    private function getCreditsQuery()
    {
        $query = Credit::query()->with(['customer', 'supplier', 'user', 'branch']);

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->whereHas('customer', function($customerQuery) {
                    $customerQuery->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('supplier', function($supplierQuery) {
                    $supplierQuery->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('reference_number', 'like', '%' . $this->search . '%')
                ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filtering
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        } elseif (!$this->showPaidCredits) {
            $query->where(function($q) {
                $q->whereIn('status', ['active', 'partially_paid', 'overdue'])
                  ->where('balance', '>', 0);
            });
        }
        
        // Apply remaining filters
        if ($this->filters['type']) {
            $query->where('credit_type', $this->filters['type']);
        }
        
        if ($this->filters['date_from']) {
            $query->whereDate('credit_date', '>=', $this->filters['date_from']);
        }
        
        if ($this->filters['date_to']) {
            $query->whereDate('credit_date', '<=', $this->filters['date_to']);
        }

        if ($this->filters['customer']) {
            $query->where(function($q) {
                $q->where('credit_type', 'receivable')
                  ->where('customer_id', $this->filters['customer']);
            });
        }
        
        if ($this->filters['supplier']) {
            $query->where(function($q) {
                $q->where('credit_type', 'payable')
                  ->where('supplier_id', $this->filters['supplier']);
            });
        }
        
        // Apply branch filter for non-admin users
        if (!Auth::user()->hasRole('admin')) {
            $userBranchId = Auth::user()->branch_id;
            if ($userBranchId !== null) {
                $query->where('branch_id', $userBranchId);
            }
        }

        return $query->orderBy('created_at', 'desc');
    }



    /**
     * Export credits to Excel using Laravel Excel library
     */
    public function exportExcel()
    {
        try {
            $filename = 'credits_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download(
                new CreditsExport($this->getCreditsQuery()),
                $filename
            );
            
        } catch (\Exception $e) {
            session()->flash('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Export credits to CSV using same method as categories
     */
    public function exportCsv()
    {
        try {
            $filename = 'credits_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

            return response()->streamDownload(
                fn() => $this->generateCreditsExport(),
                $filename
            );
            
        } catch (\Exception $e) {
            session()->flash('error', 'CSV export failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate CSV data for export (optimized to avoid empty rows)
     */
    private function generateCreditsExport()
    {
        $credits = $this->getCreditsQuery()->get();
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'Reference No', 'Type', 'Customer/Supplier', 'Amount', 'Paid Amount', 'Balance',
            'Status', 'Credit Date', 'Due Date', 'Created By', 'Branch', 'Description',
            'Reference Type', 'Created At'
        ]);
        
        foreach ($credits as $credit) {
            $partyName = $credit->credit_type === 'receivable' 
                ? ($credit->customer?->name ?? '')
                : ($credit->supplier?->name ?? '');
                
            fputcsv($output, [
                $credit->reference_no ?? '',
                ucfirst($credit->credit_type ?? ''),
                $partyName,
                $credit->amount ?? 0,
                $credit->paid_amount ?? 0,
                $credit->balance ?? 0,
                ucfirst($credit->status ?? ''),
                $credit->credit_date?->format('Y-m-d') ?? '',
                $credit->due_date?->format('Y-m-d') ?? '',
                $credit->user?->name ?? '',
                $credit->branch?->name ?? '',
                $credit->description ?? '',
                $credit->reference_type ?? '',
                $credit->created_at?->format('Y-m-d H:i:s') ?? ''
            ]);
        }
        
        fclose($output);
    }

    /**
     * Export credits to PDF (print-ready HTML)
     */
    public function exportPdf()
    {
        try {
            $credits = $this->getCreditsQuery()->get();
            
            $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Credits Export</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .export-info { text-align: center; margin-bottom: 20px; color: #666; }
        .amount { text-align: right; }
        .summary { margin-top: 30px; border-top: 2px solid #333; padding-top: 20px; }
        @media print {
            body { margin: 0; }
            table { font-size: 10px; }
        }
    </style>
</head>
<body>
    <h1>Credits Export Report</h1>
    <div class="export-info">
        <p>Generated on: ' . now()->format('F j, Y g:i A') . '</p>
        <p>Total Records: ' . $credits->count() . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Reference</th>
                <th>Type</th>
                <th>Party</th>
                <th>Amount</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Status</th>
                <th>Credit Date</th>
                <th>Due Date</th>
                <th>Branch</th>
            </tr>
        </thead>
        <tbody>';
        
            foreach ($credits as $credit) {
                $partyName = $credit->credit_type === 'receivable' 
                    ? ($credit->customer ? $credit->customer->name : 'N/A')
                    : ($credit->supplier ? $credit->supplier->name : 'N/A');
                    
                $html .= '<tr>
                    <td>' . htmlspecialchars($credit->reference_no) . '</td>
                    <td>' . ucfirst($credit->credit_type) . '</td>
                    <td>' . htmlspecialchars($partyName) . '</td>
                    <td class="amount">ETB ' . number_format($credit->amount, 2) . '</td>
                    <td class="amount">ETB ' . number_format($credit->paid_amount, 2) . '</td>
                    <td class="amount">ETB ' . number_format($credit->balance, 2) . '</td>
                    <td>' . ucfirst($credit->status) . '</td>
                    <td>' . $credit->credit_date->format('M j, Y') . '</td>
                    <td>' . $credit->due_date->format('M j, Y') . '</td>
                    <td>' . htmlspecialchars($credit->branch ? $credit->branch->name : 'N/A') . '</td>
                </tr>';
            }
            
            // Summary section
            $totalAmount = $credits->sum('amount');
            $totalPaid = $credits->sum('paid_amount');
            $totalBalance = $credits->sum('balance');
            $activeCount = $credits->whereIn('status', ['active', 'partially_paid', 'overdue'])->count();
            
            $html .= '</tbody>
    </table>
    
    <div class="summary">
        <h3>Summary</h3>
        <table style="width: 50%; margin-top: 10px;">
            <tr><td><strong>Total Credits:</strong></td><td>' . $credits->count() . '</td></tr>
            <tr><td><strong>Active Credits:</strong></td><td>' . $activeCount . '</td></tr>
            <tr><td><strong>Total Amount:</strong></td><td>ETB ' . number_format($totalAmount, 2) . '</td></tr>
            <tr><td><strong>Total Paid:</strong></td><td>ETB ' . number_format($totalPaid, 2) . '</td></tr>
            <tr><td><strong>Outstanding Balance:</strong></td><td>ETB ' . number_format($totalBalance, 2) . '</td></tr>
        </table>
    </div>
    
    <script>
        // Auto-trigger print dialog
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>';

            return response($html)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Content-Disposition', 'inline');
                
        } catch (\Exception $e) {
            session()->flash('error', 'PDF export failed: ' . $e->getMessage());
        }
    }


}
