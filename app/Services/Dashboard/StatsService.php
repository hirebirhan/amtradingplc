<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Item;
use App\Models\Warehouse;
use App\Enums\UserRole;
use App\Services\Dashboard\Contracts\StatsServiceInterface;
use Illuminate\Support\Facades\DB;

class StatsService implements StatsServiceInterface
{
    /**
     * Get dashboard statistics for a user
     */
    public function getStats(User $user): array
    {
        return [
            ...$this->getSalesMetrics($user),
            ...$this->getPurchaseMetrics($user),
            ...$this->getBasicStats($user),
        ];
    }

    private function getSalesMetrics(User $user): array
    {
        $salesQuery = Sale::query();
        
        // Apply branch filtering for non-admin users
        if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
            if ($user->branch_id) {
                $salesQuery->forBranch($user->branch_id);
            }
            
            // Sales users see only their own sales
            if ($user->hasRole('Sales')) {
                $salesQuery->where('user_id', $user->id);
            }
        }

        $salesData = $salesQuery->selectRaw('
            COUNT(*) as total_sales,
            SUM(total_amount) as total_revenue,
            COUNT(CASE WHEN payment_status = "pending" THEN 1 END) as pending_sales,
            COUNT(CASE WHEN MONTH(sale_date) = MONTH(CURRENT_DATE()) 
                  AND YEAR(sale_date) = YEAR(CURRENT_DATE()) THEN 1 END) as monthly_sales,
            SUM(CASE WHEN MONTH(sale_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(sale_date) = YEAR(CURRENT_DATE()) THEN total_amount ELSE 0 END) as monthly_revenue
        ')->first();

        $canViewRevenue = !$user->hasRole(UserRole::SALES->value);

        return [
            'total_sales' => $salesData->total_sales ?? 0,
            'monthly_sales' => $salesData->monthly_sales ?? 0,
            'total_revenue' => $canViewRevenue ? ($salesData->total_revenue ?? 0) : 0,
            'monthly_revenue' => $canViewRevenue ? ($salesData->monthly_revenue ?? 0) : 0,
            'pending_sales' => $salesData->pending_sales ?? 0,
            'can_view_revenue' => $canViewRevenue,
        ];
    }

    private function getPurchaseMetrics(User $user): array
    {
        $purchasesQuery = Purchase::query();
        
        // Apply branch filtering for non-admin users
        if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
            if ($user->branch_id) {
                $purchasesQuery->forBranch($user->branch_id);
            }
        }

        $purchaseData = $purchasesQuery->selectRaw('
            COUNT(*) as total_purchases,
            SUM(total_amount) as total_purchase_amount,
            COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_purchases,
            COUNT(CASE WHEN MONTH(purchase_date) = MONTH(CURRENT_DATE()) 
                  AND YEAR(purchase_date) = YEAR(CURRENT_DATE()) THEN 1 END) as monthly_purchases,
            SUM(CASE WHEN MONTH(purchase_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(purchase_date) = YEAR(CURRENT_DATE()) THEN total_amount ELSE 0 END) as monthly_purchase_amount
        ')->first();

        $canViewPurchases = $user->can('purchases.view');

        return [
            'total_purchases' => $canViewPurchases ? ($purchaseData->total_purchases ?? 0) : 0,
            'monthly_purchases' => $canViewPurchases ? ($purchaseData->monthly_purchases ?? 0) : 0,
            'total_purchase_amount' => $canViewPurchases ? ($purchaseData->total_purchase_amount ?? 0) : 0,
            'monthly_purchase_amount' => $canViewPurchases ? ($purchaseData->monthly_purchase_amount ?? 0) : 0,
            'pending_purchases' => $canViewPurchases ? ($purchaseData->pending_purchases ?? 0) : 0,
            'can_view_purchases' => $canViewPurchases,
        ];
    }

    private function getBasicStats(User $user): array
    {
        // Apply branch filtering to basic stats
        $categoriesQuery = Category::where('is_active', true);
        $itemsQuery = Item::where('is_active', true);
        $customersQuery = Customer::where('is_active', true);
        
        if (!$user->isSuperAdmin() && !$user->isGeneralManager()) {
            if ($user->branch_id) {
                $categoriesQuery->forBranch($user->branch_id);
                $itemsQuery->forBranch($user->branch_id);
                $customersQuery->forBranch($user->branch_id);
            }
        }
        
        return [
            'categories_count' => $categoriesQuery->count(),
            'items_count' => $itemsQuery->count(),
            'warehouses_count' => $this->getWarehousesCount($user),
            'customers_count' => $customersQuery->count(),
        ];
    }

    private function getWarehousesCount(User $user): int
    {
        if ($user->isSuperAdmin()) {
            return Warehouse::count();
        }

        if ($user->hasRole(UserRole::BRANCH_MANAGER->value) && $user->branch_id) {
            return Warehouse::whereHas('branches', function($query) use ($user) {
                $query->where('branches.id', $user->branch_id);
            })->count();
        }

        if ($user->hasRole(UserRole::WAREHOUSE_USER->value) && $user->warehouse_id) {
            return 1; // User can only see their assigned warehouse
        }

        return 0;
    }


}