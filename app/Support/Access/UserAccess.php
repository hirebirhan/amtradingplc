<?php

declare(strict_types=1);

namespace App\Support\Access;

use App\Models\StockReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class UserAccess
{
    public static function hasFullAccess(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isGeneralManager();
    }

    /**
     * Returns null when the user can access every branch.
     *
     * @return array<int>|null
     */
    public static function branchIds(User $user): ?array
    {
        if (self::hasFullAccess($user)) {
            return null;
        }

        if ($user->branch_id) {
            return [(int) $user->branch_id];
        }

        if ($user->warehouse_id) {
            return $user->warehouse?->branches()
                ->pluck('branches.id')
                ->map(fn ($id) => (int) $id)
                ->all() ?? [];
        }

        return [];
    }

    /**
     * Returns null when the user can access every warehouse.
     *
     * @return array<int>|null
     */
    public static function warehouseIds(User $user): ?array
    {
        if (self::hasFullAccess($user)) {
            return null;
        }

        if ($user->warehouse_id) {
            return [(int) $user->warehouse_id];
        }

        if ($user->branch_id) {
            return $user->branch?->warehouses()
                ->pluck('warehouses.id')
                ->map(fn ($id) => (int) $id)
                ->all() ?? [];
        }

        return [];
    }

    public static function scopeToBranches(Builder $query, User $user, string $column = 'branch_id'): Builder
    {
        $branchIds = self::branchIds($user);

        if ($branchIds === null) {
            return $query;
        }

        if ($branchIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $branchIds);
    }

    public static function scopeToWarehouses(Builder $query, User $user, string $column = 'warehouse_id'): Builder
    {
        $warehouseIds = self::warehouseIds($user);

        if ($warehouseIds === null) {
            return $query;
        }

        if ($warehouseIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $warehouseIds);
    }

    public static function scopeToLocation(
        Builder $query,
        User $user,
        string $branchColumn = 'branch_id',
        ?string $warehouseColumn = 'warehouse_id',
    ): Builder {
        if (self::hasFullAccess($user)) {
            return $query;
        }

        $branchIds = self::branchIds($user) ?? [];
        $warehouseIds = $warehouseColumn ? (self::warehouseIds($user) ?? []) : [];

        if ($branchIds === [] && $warehouseIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $scoped) use ($branchColumn, $warehouseColumn, $branchIds, $warehouseIds): void {
            if ($branchIds !== []) {
                $scoped->whereIn($branchColumn, $branchIds);
            }

            if ($warehouseColumn && $warehouseIds !== []) {
                $method = $branchIds === [] ? 'whereIn' : 'orWhereIn';
                $scoped->{$method}($warehouseColumn, $warehouseIds);
            }
        });
    }

    public static function canAccessLocation(User $user, ?int $branchId = null, ?int $warehouseId = null): bool
    {
        if (self::hasFullAccess($user)) {
            return true;
        }

        $branchIds = self::branchIds($user) ?? [];
        $warehouseIds = self::warehouseIds($user) ?? [];

        if ($branchId && in_array($branchId, $branchIds, true)) {
            return true;
        }

        return $warehouseId && in_array($warehouseId, $warehouseIds, true);
    }

    public static function scopeToReservationLocation(Builder $query, User $user): Builder
    {
        if (self::hasFullAccess($user)) {
            return $query;
        }

        $branchIds = self::branchIds($user) ?? [];
        $warehouseIds = self::warehouseIds($user) ?? [];

        if ($branchIds === [] && $warehouseIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $scoped) use ($branchIds, $warehouseIds): void {
            if ($branchIds !== []) {
                $scoped->where(function (Builder $branchScope) use ($branchIds): void {
                    $branchScope->where('location_type', 'branch')
                        ->whereIn('location_id', $branchIds);
                });
            }

            if ($warehouseIds !== []) {
                $method = $branchIds === [] ? 'where' : 'orWhere';
                $scoped->{$method}(function (Builder $warehouseScope) use ($warehouseIds): void {
                    $warehouseScope->where('location_type', 'warehouse')
                        ->whereIn('location_id', $warehouseIds);
                });
            }
        });
    }

    public static function canAccessReservation(User $user, StockReservation $reservation): bool
    {
        if (self::hasFullAccess($user)) {
            return true;
        }

        return $reservation->location_type === 'branch'
            ? self::canAccessLocation($user, branchId: (int) $reservation->location_id)
            : self::canAccessLocation($user, warehouseId: (int) $reservation->location_id);
    }
}
