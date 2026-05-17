<?php

namespace App\Services\Dashboard;

use App\Enums\UserRole;
use App\Helpers\UserHelper;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use App\Services\Dashboard\Contracts\ChartDataServiceInterface;
use App\Services\Dashboard\Enums\ChartRange;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ChartDataService implements ChartDataServiceInterface
{
    public function getChartData(User $user, string $range, ?int $branchId = null, ?int $warehouseId = null): array
    {
        try {
            $chartRange = ChartRange::from($range);

            $data = match ($chartRange) {
                ChartRange::TODAY      => $this->todayChart($user, $branchId, $warehouseId),
                ChartRange::YESTERDAY  => $this->yesterdayChart($user, $branchId, $warehouseId),
                ChartRange::WEEK       => $this->weekChart($user, $branchId, $warehouseId),
                ChartRange::MONTH      => $this->monthChart($user, $branchId, $warehouseId),
                ChartRange::THIS_MONTH => $this->thisMonthChart($user, $branchId, $warehouseId),
                ChartRange::YEAR       => $this->yearChart($user, $branchId, $warehouseId),
                default                => $this->monthChart($user, $branchId, $warehouseId),
            };

            if (! UserHelper::isAdminOrManager() && ! UserHelper::hasRole(UserRole::PURCHASE_OFFICER)) {
                $data['purchases'] = array_fill(0, count($data['purchases']), 0);
            }

            return $data;
        } catch (\Exception $e) {
            \Log::error('Error generating chart data', [
                'error'   => $e->getMessage(),
                'user_id' => $user->id,
                'range'   => $range,
            ]);

            return $this->getEmptyChartData();
        }
    }

    public function getEmptyChartData(): array
    {
        return ['labels' => [], 'sales' => [], 'purchases' => []];
    }

    private function todayChart(User $user, ?int $branchId, ?int $warehouseId): array
    {
        [$labels, $keys] = $this->hourlySlots();

        return $this->buildChart($user, Carbon::today(), Carbon::now(), $labels, $keys, 'H', $branchId, $warehouseId);
    }

    private function yesterdayChart(User $user, ?int $branchId, ?int $warehouseId): array
    {
        [$labels, $keys] = $this->hourlySlots();

        return $this->buildChart($user, Carbon::yesterday(), Carbon::yesterday()->endOfDay(), $labels, $keys, 'H', $branchId, $warehouseId);
    }

    private function weekChart(User $user, ?int $branchId, ?int $warehouseId): array
    {
        [$labels, $keys] = $this->dailySlots(6, 0, 'D');

        return $this->buildChart($user, Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay(), $labels, $keys, 'Y-m-d', $branchId, $warehouseId);
    }

    private function monthChart(User $user, ?int $branchId, ?int $warehouseId): array
    {
        [$labels, $keys] = $this->dailySlots(29, 0, 'M d');

        return $this->buildChart($user, Carbon::now()->subDays(29)->startOfDay(), Carbon::now()->endOfDay(), $labels, $keys, 'Y-m-d', $branchId, $warehouseId);
    }

    private function thisMonthChart(User $user, ?int $branchId, ?int $warehouseId): array
    {
        $start = Carbon::now()->startOfMonth();
        [$labels, $keys] = $this->dailySlots($start->daysInMonth - 1, 0, 'M d', $start);

        return $this->buildChart($user, $start, Carbon::now()->endOfDay(), $labels, $keys, 'Y-m-d', $branchId, $warehouseId);
    }

    private function yearChart(User $user, ?int $branchId, ?int $warehouseId): array
    {
        $start = Carbon::now()->subMonths(11)->startOfMonth();
        $labels = [];
        $keys   = [];
        for ($i = 0; $i < 12; $i++) {
            $m        = $start->copy()->addMonths($i);
            $labels[] = $m->format('M Y');
            $keys[]   = $m->format('Y-m');
        }

        return $this->buildChart($user, $start, Carbon::now()->endOfMonth(), $labels, $keys, 'Y-m', $branchId, $warehouseId);
    }

    private function buildChart(
        User $user, Carbon $start, Carbon $end,
        array $labels, array $keys, string $groupFormat,
        ?int $branchId, ?int $warehouseId
    ): array {
        $salesBuckets    = array_fill_keys($keys, 0.0);
        $purchaseBuckets = array_fill_keys($keys, 0.0);

        $this->getSalesData($user, $start, $end, $branchId, $warehouseId)
            ->groupBy(fn ($s) => Carbon::parse($s->created_at)->format($groupFormat))
            ->each(function ($group, $key) use (&$salesBuckets) {
                if (array_key_exists($key, $salesBuckets)) {
                    $salesBuckets[$key] = (float) $group->sum('total_amount');
                }
            });

        $this->getPurchasesData($user, $start, $end, $branchId, $warehouseId)
            ->groupBy(fn ($p) => Carbon::parse($p->created_at)->format($groupFormat))
            ->each(function ($group, $key) use (&$purchaseBuckets) {
                if (array_key_exists($key, $purchaseBuckets)) {
                    $purchaseBuckets[$key] = (float) $group->sum('total_amount');
                }
            });

        return [
            'labels'    => $labels,
            'sales'     => array_values($salesBuckets),
            'purchases' => array_values($purchaseBuckets),
        ];
    }

    private function hourlySlots(): array
    {
        $labels = $keys = [];
        for ($h = 0; $h < 24; $h++) {
            $labels[] = sprintf('%02d:00', $h);
            $keys[]   = sprintf('%02d', $h);
        }

        return [$labels, $keys];
    }

    private function dailySlots(int $fromDaysAgo, int $toDaysAgo, string $labelFormat, ?Carbon $base = null): array
    {
        $labels = $keys = [];
        for ($i = $fromDaysAgo; $i >= $toDaysAgo; $i--) {
            $date     = ($base ?? Carbon::now())->copy()->addDays($fromDaysAgo - $i);
            $labels[] = $date->format($labelFormat);
            $keys[]   = $date->format('Y-m-d');
        }

        return [$labels, $keys];
    }

    private function getSalesData(User $user, Carbon $startDate, Carbon $endDate, ?int $branchId, ?int $warehouseId): Collection
    {
        $query = Sale::whereBetween('created_at', [$startDate, $endDate]);

        if (UserHelper::isSales() && ! UserHelper::isAdminOrManager()) {
            $query->where('user_id', $user->id);
        }
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($warehouseId) {
            $query->whereHas('saleItems', fn ($q) => $q->where('warehouse_id', $warehouseId));
        }

        return $query->select('created_at', 'total_amount', 'branch_id')
            ->with(['branch:id,name', 'saleItems.warehouse:id,name'])
            ->get();
    }

    private function getPurchasesData(User $user, Carbon $startDate, Carbon $endDate, ?int $branchId, ?int $warehouseId): Collection
    {
        if (! UserHelper::isAdminOrManager() && ! UserHelper::hasRole(UserRole::PURCHASE_OFFICER)) {
            return collect();
        }

        $query = Purchase::whereBetween('created_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->select('created_at', 'total_amount', 'branch_id', 'warehouse_id')
            ->with(['branch:id,name', 'warehouse:id,name'])
            ->get();
    }
}
