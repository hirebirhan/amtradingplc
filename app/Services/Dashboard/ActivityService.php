<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\StockHistory;
use App\Services\Dashboard\Contracts\ActivityServiceInterface;
use Illuminate\Support\Collection;

class ActivityService implements ActivityServiceInterface
{
    /**
     * Get recent activities for a user
     */
    public function getActivities(User $user, ?int $branchId = null, ?int $warehouseId = null): array
    {
        $activities = $this->getRecentActivities($user, $branchId, $warehouseId);
        
        return [
            'activities' => $activities,
        ];
    }

    private function getRecentActivities(User $user, ?int $branchId = null, ?int $warehouseId = null): Collection
    {
        $query = StockHistory::with(['item', 'warehouse', 'warehouse.branches', 'user']);
        
        if ($user->hasRole(['SystemAdmin', 'Manager'])) {
            // SystemAdmin and Manager can filter by branch or warehouse
            if ($branchId) {
                $query->whereHas('warehouse.branches', function($branchQuery) use ($branchId) {
                    $branchQuery->where('branches.id', $branchId);
                });
            } elseif ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }
            // If no filters provided, show all activities
        } elseif ($user->hasRole('BranchManager') && $user->branch_id) {
            // Branch Manager sees activities from their branch warehouses
            $query->whereHas('warehouse.branches', function($branchQuery) use ($user) {
                $branchQuery->where('branches.id', $user->branch_id);
            });
        } elseif ($user->hasRole('WarehouseUser') && $user->warehouse_id) {
            // Warehouse User sees only activities from their assigned warehouse
            $query->where('warehouse_id', $user->warehouse_id);
        } elseif ($user->hasRole('Sales')) {
            // Sales users see only their own activities or activities in their branch
            $query->where(function($subQuery) use ($user) {
                $subQuery->where('user_id', $user->id);
                
                // Additional filter for branch warehouses if they have a branch assignment
                if ($user->branch_id) {
                    $subQuery->orWhereHas('warehouse.branches', function($branchQuery) use ($user) {
                        $branchQuery->where('branches.id', $user->branch_id);
                    });
                }
            });
        } else {
            // No access - return empty collection
            return collect([]);
        }
        
        return $query->orderByDesc('created_at')
                    ->limit(5)
                    ->get();
    }
} 