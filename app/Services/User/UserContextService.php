<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Centralizes current user context for branch/warehouse selection and access helpers.
 *
 * Session keys:
 *  - user_context.branch_id
 *  - user_context.warehouse_id
 */
class UserContextService
{
    public const SESSION_BRANCH_ID = 'user_context.branch_id';
    public const SESSION_WAREHOUSE_ID = 'user_context.warehouse_id';

    public function user(): ?User
    {
        return Auth::user();
    }

    public function currentBranch(?User $user = null): ?Branch
    {
        $user ??= $this->user();
        if (!$user) return null;

        $branchId = Session::get(self::SESSION_BRANCH_ID);
        if ($branchId && $user->hasAccessToBranch((int)$branchId)) {
            return Branch::query()->find($branchId);
        }

        return $user->branch; // fallback to assignment
    }

    public function currentWarehouse(?User $user = null): ?Warehouse
    {
        $user ??= $this->user();
        if (!$user) return null;

        $warehouseId = Session::get(self::SESSION_WAREHOUSE_ID);
        if ($warehouseId && $user->hasAccessToWarehouse((int)$warehouseId)) {
            return Warehouse::query()->find($warehouseId);
        }

        return $user->warehouse; // fallback to assignment
    }

    public function setBranchId(int $branchId, ?User $user = null): bool
    {
        $user ??= $this->user();
        if (!$user) return false;
        if (!$user->hasAccessToBranch($branchId)) return false;
        Session::put(self::SESSION_BRANCH_ID, $branchId);
        // Clearing warehouse if invalid for selected branch is app-dependent; leave as-is for now.
        return true;
    }

    public function setWarehouseId(int $warehouseId, ?User $user = null): bool
    {
        $user ??= $this->user();
        if (!$user) return false;
        if (!$user->hasAccessToWarehouse($warehouseId)) return false;
        Session::put(self::SESSION_WAREHOUSE_ID, $warehouseId);
        return true;
    }

    public function clearSelection(): void
    {
        Session::forget([self::SESSION_BRANCH_ID, self::SESSION_WAREHOUSE_ID]);
    }

    public function allowedWarehouses(?User $user = null): Collection
    {
        $user ??= $this->user();
        if (!$user) return collect();

        if ($user->isSuperAdmin()) {
            return Warehouse::query()->orderBy('name')->get();
        }

        if ($user->branch) {
            // Assuming Branch has warehouses relation
            return $user->branch->warehouses()->orderBy('name')->get();
        }

        if ($user->warehouse) {
            return Warehouse::query()->whereKey($user->warehouse_id)->get();
        }

        return collect();
    }

    public function canAccessWarehouseId(int $warehouseId, ?User $user = null): bool
    {
        $user ??= $this->user();
        if (!$user) return false;
        return $user->hasAccessToWarehouse($warehouseId);
    }

    /**
     * Apply a user warehouse filter to a query builder when applicable.
     */
    public function scopeByUserWarehouse(Builder $query, ?User $user = null, string $column = 'warehouse_id'): Builder
    {
        $user ??= $this->user();
        if (!$user) return $query;

        $current = $this->currentWarehouse($user);
        if ($current) {
            return $query->where($column, $current->getKey());
        }

        // If user has branch but no explicit warehouse selection, restrict to branch warehouses
        if ($user->branch) {
            $ids = $user->branch->warehouses()->pluck('id');
            if ($ids->isNotEmpty()) {
                return $query->whereIn($column, $ids);
            }
        }

        return $query; // Super admin or no constraints
    }
}
