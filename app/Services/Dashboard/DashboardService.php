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
use App\Enums\UserRole;

class DashboardService
{
    private const REVENUE_ROLES = [
        UserRole::SUPER_ADMIN,
        UserRole::BRANCH_MANAGER,
        UserRole::ACCOUNTANT
    ];

    private const PURCHASE_ROLES = [
        UserRole::SUPER_ADMIN,
        UserRole::BRANCH_MANAGER,
        UserRole::PURCHASE_OFFICER
    ];

    private const INVENTORY_ROLES = [
        UserRole::SUPER_ADMIN,
        UserRole::BRANCH_MANAGER,
        UserRole::WAREHOUSE_MANAGER
    ];

    private const ADMIN_ROLES = [
        UserRole::SUPER_ADMIN,
        UserRole::BRANCH_MANAGER
    ];
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
        
        // Add role-based view permissions
        $data['can_view_revenue'] = $this->hasAnyRole($user, self::REVENUE_ROLES);
        $data['can_view_purchases'] = $this->hasAnyRole($user, self::PURCHASE_ROLES);
        $data['can_view_inventory'] = $this->hasAnyRole($user, self::INVENTORY_ROLES);
        
        // Filter activities based on user role
        if ($user->hasRole('Sales') && !$this->hasAnyRole($user, self::ADMIN_ROLES)) {
            $data['activities'] = $data['activities']->filter(function ($activity) use ($user) {
                return $activity->user_id === $user->id;
            });
        }
        
        return $data;
    }

    /**
     * Get filter options for dashboard dropdowns
     */
    public function getFilterOptions(User $user): array
    {
        if (!$this->hasAnyRole($user, self::ADMIN_ROLES)) {
            return [
                'available_branches' => collect([]),
                'available_warehouses' => collect([])
            ];
        }
        
        return [
            'available_branches' => \App\Models\Branch::select('id', 'name')->get(),
            'available_warehouses' => \App\Models\Warehouse::select('id', 'name')->get(),
        ];
    }

    /**
     * Check if user has any of the specified roles
     */
    private function hasAnyRole(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }
        return false;
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
            'total_inventory_value' => 0,
            'low_stock_items' => 0,
            'pending_sales' => 0,
            'categories_count' => 0,
            'items_count' => 0,
            'warehouses_count' => 0,
            'customers_count' => 0,
            'can_view_revenue' => false,
            'can_view_purchases' => false,
            'can_view_inventory' => false,
            'low_stock_items_list' => collect([]),
            'activities' => collect([]),
        ];
    }
} 