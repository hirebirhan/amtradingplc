<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Sale;
use App\Models\Purchase;
use App\Services\Dashboard\Enums\ChartRange;
use App\Services\Dashboard\Contracts\ChartDataServiceInterface;
use App\Helpers\UserHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ChartDataService implements ChartDataServiceInterface
{
    /**
     * Get chart data for a specific range and user
     */
    /**
     * Get chart data with optional branch and warehouse filters
     * 
     * @param User $user The authenticated user
     * @param string $range The time range for the chart
     * @param int|null $branchId Filter by branch ID
     * @param int|null $warehouseId Filter by warehouse ID
     * @return array Chart data
     */
    public function getChartData(User $user, string $range, ?int $branchId = null, ?int $warehouseId = null): array
    {
        try {
            $chartRange = ChartRange::from($range);
            
            // Get chart data based on range with filters
            $chartData = match($chartRange) {
                ChartRange::TODAY => $this->getTodayData($user, $branchId, $warehouseId),
                ChartRange::YESTERDAY => $this->getYesterdayData($user, $branchId, $warehouseId),
                ChartRange::WEEK => $this->getWeekData($user, $branchId, $warehouseId),
                ChartRange::MONTH => $this->getMonthData($user, $branchId, $warehouseId),
                ChartRange::THIS_MONTH => $this->getThisMonthData($user, $branchId, $warehouseId),
                ChartRange::YEAR => $this->getYearData($user, $branchId, $warehouseId),
                default => $this->getMonthData($user, $branchId, $warehouseId),
            };
            
            // Apply role-based filtering
            return $this->filterChartDataByRole($chartData, $user);
            
        } catch (\Exception $e) {
            \Log::error('Error generating chart data', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'range' => $range,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getEmptyChartData();
        }
    }

    /**
     * Get empty chart data for error scenarios
     */
    /**
     * Filter chart data based on user role
     */
    private function filterChartDataByRole(array $chartData, User $user): array
    {
        // If user can't view purchases, remove purchases data
        if (!UserHelper::isAdminOrManager() && 
            !UserHelper::hasRole(\App\Enums\UserRole::PURCHASE_OFFICER)) {
            $chartData['purchases'] = array_fill(0, count($chartData['purchases']), 0);
        }
        
        // If user is sales and not admin/manager, ensure we only show their sales
        if (UserHelper::isSales() && !UserHelper::isAdminOrManager()) {
            // Sales data is already filtered in getSalesData
            // Just ensure purchases are hidden
            $chartData['purchases'] = array_fill(0, count($chartData['purchases']), 0);
        }
        
        return $chartData;
    }
    
    public function getEmptyChartData(): array
    {
        return [
            'labels' => [],
            'sales' => [],
            'purchases' => [],
        ];
    }

/**
     * Get chart data for today
     */
    private function getTodayData(
        User $user, 
        ?int $branchId = null, 
        ?int $warehouseId = null
    ): array
    {
        $startDate = Carbon::today();
        $endDate = Carbon::now();
        $labels = [];
        $salesData = [];
        $purchasesData = [];

        // Generate hourly labels
        for ($i = 0; $i < 24; $i++) {
            $labels[] = sprintf('%02d:00', $i);
            $salesData[] = 0;
            $purchasesData[] = 0;
        }

        $sales = $this->getSalesData($user, $startDate, $endDate, $branchId, $warehouseId)
            ->groupBy(fn($sale) => Carbon::parse($sale->created_at)->format('H'));
        
        $purchases = $this->getPurchasesData($user, $startDate, $endDate, $branchId, $warehouseId)
            ->groupBy(fn($purchase) => Carbon::parse($purchase->created_at)->format('H'));

        foreach ($sales as $hour => $saleGroup) {
            $salesData[intval($hour)] = $saleGroup->sum('total_amount');
        }

        foreach ($purchases as $hour => $purchaseGroup) {
            $purchasesData[intval($hour)] = $purchaseGroup->sum('total_amount');
        }

        return [
            'labels' => $labels,
            'sales' => $salesData,
            'purchases' => $purchasesData,
        ];
    }

    /**
     * Get chart data for yesterday
     */
    private function getYesterdayData(
        User $user, 
        ?int $branchId = null, 
        ?int $warehouseId = null
    ): array {
        $startDate = Carbon::yesterday();
        $endDate = Carbon::yesterday()->endOfDay();
        
        // Get data for yesterday with filters
        $sales = $this->getSalesData($user, $startDate, $endDate, $branchId, $warehouseId)
            ->groupBy(fn($sale) => Carbon::parse($sale->created_at)->format('H'));
        
        $purchases = $this->getPurchasesData($user, $startDate, $endDate, $branchId, $warehouseId)
            ->groupBy(fn($purchase) => Carbon::parse($purchase->created_at)->format('H'));
        
        // Initialize hourly data
        $labels = [];
        $salesData = [];
        $purchasesData = [];
        
        // Generate hourly labels and initialize data arrays
        for ($i = 0; $i < 24; $i++) {
            $labels[] = sprintf('%02d:00', $i);
            $salesData[] = 0;
            $purchasesData[] = 0;
        }
        
        // Fill in sales data
        foreach ($sales as $hour => $saleGroup) {
            $salesData[intval($hour)] = $saleGroup->sum('total_amount');
        }
        
        // Fill in purchases data
        foreach ($purchases as $hour => $purchaseGroup) {
            $purchasesData[intval($hour)] = $purchaseGroup->sum('total_amount');
        }
        
        return [
            'labels' => $labels,
            'sales' => $salesData,
            'purchases' => $purchasesData,
        ];
    }

    /**
     * Get chart data for the last 7 days
     */
    private function getWeekData(
        User $user, 
        ?int $branchId = null, 
        ?int $warehouseId = null
    ): array {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $labels = [];
        $salesData = [];
        $purchasesData = [];

        // Generate daily labels for last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('D');
            $salesData[$date->format('Y-m-d')] = 0;
            $purchasesData[$date->format('Y-m-d')] = 0;
        }

        $sales = $this->getSalesData($user, $startDate, $endDate, $branchId, $warehouseId)
            ->groupBy(fn($sale) => Carbon::parse($sale->created_at)->format('Y-m-d'));
        
        $purchases = $this->getPurchasesData($user, $startDate, $endDate, $branchId, $warehouseId)
            ->groupBy(fn($purchase) => Carbon::parse($purchase->created_at)->format('Y-m-d'));

        foreach ($sales as $date => $saleGroup) {
            $salesData[$date] = $saleGroup->sum('total_amount');
        }

        foreach ($purchases as $date => $purchaseGroup) {
            $purchasesData[$date] = $purchaseGroup->sum('total_amount');
        }

        return [
            'labels' => $labels,
            'sales' => array_values($salesData),
            'purchases' => array_values($purchasesData),
        ];
    }

    /**
     * Get chart data for the last 30 days
     */
    private function getMonthData(
        User $user, 
        ?int $branchId = null, 
        ?int $warehouseId = null
    ): array {
        $startDate = Carbon::now()->subDays(29)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $labels = [];
        $salesData = [];
        $purchasesData = [];

        // Generate daily labels for last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M d');
            $salesData[$date->format('Y-m-d')] = 0;
            $purchasesData[$date->format('Y-m-d')] = 0;
        }

        $sales = $this->getSalesData($user, $startDate, $endDate, $branchId, $warehouseId)
            ->groupBy(fn($sale) => Carbon::parse($sale->created_at)->format('Y-m-d'));
        
        $purchases = $this->getPurchasesData($user, $startDate, $endDate, $branchId, $warehouseId)
            ->groupBy(fn($purchase) => Carbon::parse($purchase->created_at)->format('Y-m-d'));

        foreach ($sales as $date => $saleGroup) {
            $salesData[$date] = $saleGroup->sum('total_amount');
        }

        foreach ($purchases as $date => $purchaseGroup) {
            $purchasesData[$date] = $purchaseGroup->sum('total_amount');
        }

        return [
            'labels' => $labels,
            'sales' => array_values($salesData),
            'purchases' => array_values($purchasesData),
        ];
    }

    /**
     * Get chart data for the current month
     */
    private function getThisMonthData(
        User $user, 
        ?int $branchId = null, 
        ?int $warehouseId = null
    ): array {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfDay();
        
        $daysInMonth = $startDate->daysInMonth;
        $labels = [];
        $salesData = [];
        $purchasesData = [];

        // Generate daily labels for current month
        for ($i = 0; $i < $daysInMonth; $i++) {
            $date = $startDate->copy()->addDays($i);
            $labels[] = $date->format('M d');
            $salesData[$date->format('Y-m-d')] = 0;
            $purchasesData[$date->format('Y-m-d')] = 0;
        }
        
        // Get sales and purchases data with filters
        $sales = $this->getSalesData($user, $startDate, $endDate, $branchId, $warehouseId)
            ->groupBy(fn($sale) => Carbon::parse($sale->created_at)->format('Y-m-d'));
            
        $purchases = $this->getPurchasesData($user, $startDate, $endDate, $branchId, $warehouseId)
            ->groupBy(fn($purchase) => Carbon::parse($purchase->created_at)->format('Y-m-d'));
            
        // Fill in sales data
        foreach ($sales as $date => $saleGroup) {
            if (isset($salesData[$date])) {
                $salesData[$date] = $saleGroup->sum('total_amount');
            }
        }
        
        // Fill in purchases data
        foreach ($purchases as $date => $purchaseGroup) {
            if (isset($purchasesData[$date])) {
                $purchasesData[$date] = $purchaseGroup->sum('total_amount');
            }
        }
        
        return [
            'labels' => $labels,
            'sales' => array_values($salesData),
            'purchases' => array_values($purchasesData),
        ];
    }

    /**
     * Get chart data for the last 12 months
     */
    private function getYearData(
        User $user, 
        ?int $branchId = null, 
        ?int $warehouseId = null
    ): array {
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $labels = [];
        $salesData = [];
        $purchasesData = [];
        
        // Generate monthly labels for last 12 months
        for ($i = 0; $i < 12; $i++) {
            $date = $startDate->copy()->addMonths($i);
            $labels[] = $date->format('M Y');
            $salesData[$date->format('Y-m')] = 0;
            $purchasesData[$date->format('Y-m')] = 0;
        }
        
        // Get sales and purchases data with filters
        $sales = $this->getSalesData($user, $startDate, $endDate, $branchId, $warehouseId)
            ->groupBy(fn($sale) => Carbon::parse($sale->created_at)->format('Y-m'));
            
        $purchases = $this->getPurchasesData($user, $startDate, $endDate, $branchId, $warehouseId)
            ->groupBy(fn($purchase) => Carbon::parse($purchase->created_at)->format('Y-m'));
        
        // Fill in sales data
        foreach ($sales as $month => $saleGroup) {
            if (isset($salesData[$month])) {
                $salesData[$month] = $saleGroup->sum('total_amount');
            }
        }
        
        // Fill in purchases data
        foreach ($purchases as $month => $purchaseGroup) {
            if (isset($purchasesData[$month])) {
                $purchasesData[$month] = $purchaseGroup->sum('total_amount');
            }
        }
        
        return [
            'labels' => $labels,
            'sales' => array_values($salesData),
            'purchases' => array_values($purchasesData),
        ];
    }

    private function getSalesData(
        User $user, 
        Carbon $startDate, 
        Carbon $endDate,
        ?int $branchId = null,
        ?int $warehouseId = null
    ): Collection {
        $query = Sale::whereBetween('created_at', [$startDate, $endDate]);
        
        // Apply role-based filtering
        if (UserHelper::isSales() && !UserHelper::isAdminOrManager()) {
            $query->where('user_id', $user->id);
        }
        
        // Apply branch filter if provided
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        // Apply warehouse filter if provided
        if ($warehouseId) {
            $query->whereHas('saleItems', function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }
        
        return $query->select('created_at', 'total_amount', 'branch_id')
            ->with(['branch:id,name', 'saleItems.warehouse:id,name'])
            ->get();
    }

    private function getPurchasesData(
        User $user, 
        Carbon $startDate, 
        Carbon $endDate,
        ?int $branchId = null,
        ?int $warehouseId = null
    ): Collection {
        // Only show purchases if user has permission
        if (!UserHelper::isAdminOrManager() && 
            !UserHelper::hasRole(\App\Enums\UserRole::PURCHASE_OFFICER)) {
            return collect([]);
        }
        
        $query = Purchase::whereBetween('created_at', [$startDate, $endDate]);
        
        // Apply branch filter if provided
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        // Apply warehouse filter if provided
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        
        return $query->select('created_at', 'total_amount', 'branch_id', 'warehouse_id')
            ->with(['branch:id,name', 'warehouse:id,name'])
            ->get();
    }

    private function applyUserFiltering($query, User $user, string $type = 'sales')
    {
        if ($type === 'sales' && !UserHelper::isAdminOrManager()) {
            $query->where('user_id', $user->id);
        }
        
        if ($type === 'purchases' && !UserHelper::isAdminOrManager() && 
            !UserHelper::hasRole(\App\Enums\UserRole::PURCHASE_OFFICER)) {
            $query->where('id', 0); // Return empty result
        }
        
        return $query;

        if ($user->hasRole('Sales')) {
            if ($type === 'sales') {
                return $query->where('user_id', $user->id);
            } elseif ($type === 'purchases' && $user->branch_id) {
                // Sales users can see purchases from their branch
                return $query->where(function($q) use ($user) {
                    $q->where('branch_id', $user->branch_id)
                      ->orWhereHas('warehouse.branches', function($branchQuery) use ($user) {
                          $branchQuery->where('branches.id', $user->branch_id);
                      });
                });
            }
        }

        return $query->whereRaw('1 = 0'); // No access
    }
} 