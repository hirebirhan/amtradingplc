<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Warehouse;
use App\Models\StockHistory;
use App\Models\Sale;
use App\Facades\UserHelperFacade as UserHelper;
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
    ) {
        // All role and access checks are centralized via UserHelper facade
    }

    /**
     * Get all dashboard data for a user
     */
    public function getDashboardData(User $user, ?int $branchId = null, ?int $warehouseId = null): array
    {
        // Get base dashboard data
        $data = [
            ...$this->statsService->getStats($user),
            ...$this->activityService->getActivities($user, $branchId, $warehouseId),
            ...$this->inventoryService->getInventoryData($user),
            'filter_branch_id' => $branchId,
            'filter_warehouse_id' => $warehouseId,
        ];
        
        // Add role-based view permissions using UserHelper
        $data['can_view_revenue'] = UserHelper::hasRole(\App\Enums\UserRole::SUPER_ADMIN) || 
                                  UserHelper::hasRole(\App\Enums\UserRole::BRANCH_MANAGER) || 
                                  UserHelper::hasRole(\App\Enums\UserRole::ACCOUNTANT);
        
        $data['can_view_purchases'] = UserHelper::hasRole(\App\Enums\UserRole::SUPER_ADMIN) || 
                                    UserHelper::hasRole(\App\Enums\UserRole::BRANCH_MANAGER) || 
                                    UserHelper::hasRole(\App\Enums\UserRole::PURCHASE_OFFICER);
        
        $data['can_view_inventory'] = UserHelper::hasRole(\App\Enums\UserRole::SUPER_ADMIN) || 
                                    UserHelper::hasRole(\App\Enums\UserRole::BRANCH_MANAGER) || 
                                    UserHelper::hasRole(\App\Enums\UserRole::WAREHOUSE_MANAGER);
        
        // Filter activities based on user role
        if (UserHelper::isSales() && !UserHelper::isAdminOrManager()) {
            $data['activities'] = $data['activities']->filter(function ($activity) use ($user) {
                return $activity->user_id === $user->id;
            });
        }
        
        return $data;
    }

    /**
     * Get empty dashboard data for error scenarios
     */
    public function getEmptyDashboardData(): array
    {
        return [
            'total_sales' => 0,
            'total_revenue' => 0,
            'total_purchases' => 0,
            'total_purchase_amount' => 0,
            'low_stock_items' => 0,
            'pending_sales' => 0,
            'categories_count' => 0,
            'items_count' => 0,
            'warehouses_count' => 0,
            'customers_count' => 0,
            'can_view_revenue' => false,
            'can_view_purchases' => false,
            'low_stock_items_list' => collect([]),
            'activities' => collect([]),
            'error' => 'Dashboard data is temporarily unavailable. Please try again in a moment.',
        ];
    }
} 