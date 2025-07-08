<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Sale;
use App\Models\Purchase;
use App\Services\Dashboard\Enums\ChartRange;
use App\Services\Dashboard\Contracts\ChartDataServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ChartDataService implements ChartDataServiceInterface
{
    /**
     * Get chart data for a specific range and user
     */
    public function getChartData(User $user, string $range): array
    {
        $chartRange = ChartRange::from($range);
        
        return match($chartRange) {
            ChartRange::TODAY => $this->getTodayData($user),
            ChartRange::YESTERDAY => $this->getYesterdayData($user),
            ChartRange::WEEK => $this->getWeekData($user),
            ChartRange::MONTH => $this->getMonthData($user),
            ChartRange::THIS_MONTH => $this->getThisMonthData($user),
            ChartRange::YEAR => $this->getYearData($user),
            default => $this->getMonthData($user),
        };
    }

    /**
     * Get empty chart data for error scenarios
     */
    public function getEmptyChartData(): array
    {
        return [
            'labels' => [],
            'sales' => [],
            'purchases' => [],
        ];
    }

    private function getTodayData(User $user): array
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

        $sales = $this->getSalesData($user, $startDate, $endDate)
            ->groupBy(fn($sale) => Carbon::parse($sale->created_at)->format('H'));
        
        $purchases = $this->getPurchasesData($user, $startDate, $endDate)
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

    private function getYesterdayData(User $user): array
    {
        $startDate = Carbon::yesterday();
        $endDate = Carbon::yesterday()->endOfDay();
        
        return $this->getTodayData($user); // Same format, different date
    }

    private function getWeekData(User $user): array
    {
        $startDate = Carbon::now()->subDays(6);
        $endDate = Carbon::now();
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

        $sales = $this->getSalesData($user, $startDate, $endDate)
            ->groupBy(fn($sale) => Carbon::parse($sale->created_at)->format('Y-m-d'));
        
        $purchases = $this->getPurchasesData($user, $startDate, $endDate)
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

    private function getMonthData(User $user): array
    {
        $startDate = Carbon::now()->subDays(29);
        $endDate = Carbon::now();
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

        $sales = $this->getSalesData($user, $startDate, $endDate)
            ->groupBy(fn($sale) => Carbon::parse($sale->created_at)->format('Y-m-d'));
        
        $purchases = $this->getPurchasesData($user, $startDate, $endDate)
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

    private function getThisMonthData(User $user): array
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now();
        
        return $this->getDateRangeData($user, $startDate, $endDate, 'daily');
    }

    private function getYearData(User $user): array
    {
        $startDate = Carbon::now()->startOfYear();
        $endDate = Carbon::now();
        
        return $this->getDateRangeData($user, $startDate, $endDate, 'monthly');
    }

    private function getDateRangeData(User $user, Carbon $startDate, Carbon $endDate, string $groupBy): array
    {
        $labels = [];
        $salesData = [];
        $purchasesData = [];

        if ($groupBy === 'monthly') {
            $period = $startDate->diffInMonths($endDate) + 1;
            for ($i = 0; $i < $period; $i++) {
                $date = $startDate->copy()->addMonths($i);
                $labels[] = $date->format('M');
                $salesData[] = 0;
                $purchasesData[] = 0;
            }
            
            $groupFormat = 'Y-m';
        } else {
            $period = $startDate->diffInDays($endDate) + 1;
            for ($i = 0; $i < $period; $i++) {
                $date = $startDate->copy()->addDays($i);
                $labels[] = $date->format('M d');
                $salesData[] = 0;
                $purchasesData[] = 0;
            }
            
            $groupFormat = 'Y-m-d';
        }

        $sales = $this->getSalesData($user, $startDate, $endDate)
            ->groupBy(fn($sale) => Carbon::parse($sale->created_at)->format($groupFormat));
        
        $purchases = $this->getPurchasesData($user, $startDate, $endDate)
            ->groupBy(fn($purchase) => Carbon::parse($purchase->created_at)->format($groupFormat));

        $index = 0;
        for ($i = 0; $i < $period; $i++) {
            $date = $groupBy === 'monthly' 
                ? $startDate->copy()->addMonths($i)->format($groupFormat)
                : $startDate->copy()->addDays($i)->format($groupFormat);
                
            $salesData[$index] = $sales->get($date, collect())->sum('total_amount');
            $purchasesData[$index] = $purchases->get($date, collect())->sum('total_amount');
            $index++;
        }

        return [
            'labels' => $labels,
            'sales' => $salesData,
            'purchases' => $purchasesData,
        ];
    }

    private function getSalesData(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $query = Sale::whereBetween('created_at', [$startDate, $endDate]);
        
        return $this->applyUserFiltering($query, $user)->get();
    }

    private function getPurchasesData(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $query = Purchase::whereBetween('created_at', [$startDate, $endDate]);
        
        return $this->applyUserFiltering($query, $user, 'purchases')->get();
    }

    private function applyUserFiltering($query, User $user, string $type = 'sales')
    {
        if ($user->hasRole(['SystemAdmin', 'SuperAdmin', 'Manager'])) {
            return $query; // No filtering for admins
        }

        if ($user->hasRole('BranchManager') && $user->branch_id) {
            return $query->whereHas('warehouse.branches', function($q) use ($user) {
                $q->where('branches.id', $user->branch_id);
            });
        }

        if ($user->hasRole('WarehouseUser') && $user->warehouse_id) {
            return $query->where('warehouse_id', $user->warehouse_id);
        }

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