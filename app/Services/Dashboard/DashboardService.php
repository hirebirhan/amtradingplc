<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Warehouse;
use App\Models\StockHistory;
use App\Models\Sale;
use App\Services\Dashboard\Contracts\{
    StatsServiceInterface,
    ActivityServiceInterface,
    InventoryServiceInterface
};
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private StatsServiceInterface $statsService,
        private ActivityServiceInterface $activityService,
        private InventoryServiceInterface $inventoryService
    ) {}

    /**
     * Get all dashboard data for a user
     */
    public function getDashboardData(User $user, ?int $branchId = null, ?int $warehouseId = null): array
    {
        return [
            ...$this->statsService->getStats($user),
            ...$this->activityService->getActivities($user, $branchId, $warehouseId),
            ...$this->inventoryService->getInventoryData($user),
            'filter_branch_id' => $branchId,
            'filter_warehouse_id' => $warehouseId,
        ];
    }

    /**
     * Get empty dashboard data for error scenarios
     */
    public function getEmptyDashboardData(): array
    {
        return [
            'total_sales' => 0,
            'total_revenue' => 0,
            'low_stock_items' => 0,
            'pending_sales' => 0,
            'categories_count' => 0,
            'items_count' => 0,
            'warehouses_count' => 0,
            'customers_count' => 0,
            'can_view_revenue' => false,
            'low_stock_items_list' => collect([]),
            'activities' => collect([]),
            'error' => 'Unable to load dashboard data. Please refresh the page.',
        ];
    }
} 