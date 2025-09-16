<?php

namespace App\Traits;

use App\Facades\UserHelperFacade as UserHelper;
use Illuminate\Database\Eloquent\Builder;

/**
 * Model trait to add a standard scope for filtering by the current user's warehouse context.
 */
trait ScopesByUserWarehouse
{
    /**
     * Scope a query to the current user's warehouse when available.
     * Falls back to branch warehouses when only a branch is set.
     */
    public function scopeForCurrentUserWarehouse(Builder $query, string $column = 'warehouse_id'): Builder
    {
        $warehouse = UserHelper::currentWarehouse();
        if ($warehouse) {
            return $query->where($column, $warehouse->getKey());
        }

        $branch = UserHelper::currentBranch();
        if ($branch && method_exists($branch, 'warehouses')) {
            $ids = $branch->warehouses()->pluck('id');
            if ($ids->isNotEmpty()) {
                return $query->whereIn($column, $ids);
            }
        }

        return $query;
    }
}
