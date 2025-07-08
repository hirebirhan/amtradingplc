<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Item;
use App\Models\Warehouse;
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
        $this->applySalesFiltering($salesQuery, $user);

        $salesData = $salesQuery->selectRaw('
            COUNT(*) as total_sales,
            SUM(total_amount) as total_revenue,
            COUNT(CASE WHEN payment_status = "pending" THEN 1 END) as pending_sales,
            COUNT(CASE WHEN MONTH(sale_date) = MONTH(CURRENT_DATE()) 
                  AND YEAR(sale_date) = YEAR(CURRENT_DATE()) THEN 1 END) as monthly_sales,
            SUM(CASE WHEN MONTH(sale_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(sale_date) = YEAR(CURRENT_DATE()) THEN total_amount ELSE 0 END) as monthly_revenue
        ')->first();

        $canViewRevenue = !$user->hasRole('Sales');

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
        $this->applyPurchasesFiltering($purchasesQuery, $user);

        $purchaseData = $purchasesQuery->selectRaw('
            COUNT(*) as total_purchases,
            SUM(total_amount) as total_purchase_amount,
            COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_purchases,
            COUNT(CASE WHEN MONTH(purchase_date) = MONTH(CURRENT_DATE()) 
                  AND YEAR(purchase_date) = YEAR(CURRENT_DATE()) THEN 1 END) as monthly_purchases,
            SUM(CASE WHEN MONTH(purchase_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(purchase_date) = YEAR(CURRENT_DATE()) THEN total_amount ELSE 0 END) as monthly_purchase_amount
        ')->first();

        $canViewPurchases = $user->can('purchases.view'); // Allow users with purchase view permission

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
        return [
            'categories_count' => Category::count(),
            'items_count' => Item::count(),
            'warehouses_count' => $this->getWarehousesCount($user),
            'customers_count' => $this->getCustomersCount($user),
        ];
    }

    private function getWarehousesCount(User $user): int
    {
        if ($user->isSuperAdmin()) {
            return Warehouse::count();
        }

        if ($user->hasRole('BranchManager') && $user->branch_id) {
            return Warehouse::whereHas('branches', function($query) use ($user) {
                $query->where('branches.id', $user->branch_id);
            })->count();
        }

        if ($user->hasRole('WarehouseUser') && $user->warehouse_id) {
            return 1; // User can only see their assigned warehouse
        }

        return 0;
    }

    private function getCustomersCount(User $user): int
    {
        $customersQuery = Customer::query();

        if ($user->isSuperAdmin()) {
            return $customersQuery->count();
        }

        if ($user->hasRole('BranchManager') && $user->branch_id) {
            $customersQuery->where('branch_id', $user->branch_id);
        } elseif ($user->hasRole('WarehouseUser') && $user->warehouse_id) {
            $customersQuery->whereExists(function($query) use ($user) {
                $query->select(DB::raw(1))
                      ->from('sales')
                      ->where('warehouse_id', $user->warehouse_id)
                      ->whereColumn('sales.customer_id', 'customers.id');
            });
        } elseif ($user->hasRole('Sales')) {
            $customersQuery->whereExists(function($query) use ($user) {
                $query->select(DB::raw(1))
                      ->from('sales')
                      ->where('user_id', $user->id)
                      ->whereColumn('sales.customer_id', 'customers.id');
            });
        } else {
            return 0;
        }

        return $customersQuery->count();
    }

    private function applySalesFiltering($query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return; // No filtering for admins
        }

        if ($user->hasRole('BranchManager') && $user->branch_id) {
            $query->whereExists(function($q) use ($user) {
                $q->select(DB::raw(1))
                  ->from('warehouses')
                  ->join('branch_warehouse', 'warehouses.id', '=', 'branch_warehouse.warehouse_id')
                  ->where('branch_warehouse.branch_id', $user->branch_id)
                  ->whereColumn('warehouses.id', 'sales.warehouse_id');
            });
        } elseif ($user->hasRole('WarehouseUser') && $user->warehouse_id) {
            $query->where('warehouse_id', $user->warehouse_id);
        } elseif ($user->hasRole('Sales')) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereRaw('1 = 0'); // No access
        }
    }

    private function applyPurchasesFiltering($query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return; // No filtering for admins
        }

        if ($user->hasRole('BranchManager') && $user->branch_id) {
            $query->whereExists(function($q) use ($user) {
                $q->select(DB::raw(1))
                  ->from('warehouses')
                  ->join('branch_warehouse', 'warehouses.id', '=', 'branch_warehouse.warehouse_id')
                  ->where('branch_warehouse.branch_id', $user->branch_id)
                  ->whereColumn('warehouses.id', 'purchases.warehouse_id');
            });
        } elseif ($user->hasRole('WarehouseUser') && $user->warehouse_id) {
            $query->where('warehouse_id', $user->warehouse_id);
        } elseif ($user->hasRole('Sales') && $user->branch_id) {
            // Sales users can see purchases from their assigned branch
            $query->where(function($q) use ($user) {
                $q->where('branch_id', $user->branch_id)
                  ->orWhereExists(function($warehouseQuery) use ($user) {
                      $warehouseQuery->select(DB::raw(1))
                        ->from('warehouses')
                        ->join('branch_warehouse', 'warehouses.id', '=', 'branch_warehouse.warehouse_id')
                        ->where('branch_warehouse.branch_id', $user->branch_id)
                        ->whereColumn('warehouses.id', 'purchases.warehouse_id');
                  });
            });
        } else {
            $query->whereRaw('1 = 0'); // No access for users without proper assignments
        }
    }
} 