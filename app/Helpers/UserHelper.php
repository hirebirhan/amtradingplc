<?php

namespace App\Helpers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\User\UserContextService;

class UserHelper
{
    public function __construct(
        private readonly UserContextService $context = new UserContextService(),
    ) {}

    /**
     * Get the currently authenticated user
     */
    public static function currentUser(): ?User
    {
        return Auth::user();
    }

    /**
     * Current branch/warehouse from user context service
     */
    public function currentBranch(): ?\App\Models\Branch
    {
        return $this->context->currentBranch();
    }

    public function currentWarehouse(): ?\App\Models\Warehouse
    {
        return $this->context->currentWarehouse();
    }

    public function allowedWarehouses(): \Illuminate\Support\Collection
    {
        return $this->context->allowedWarehouses();
    }

    public function setBranchId(int $branchId): bool
    {
        return $this->context->setBranchId($branchId);
    }

    public function setWarehouseId(int $warehouseId): bool
    {
        return $this->context->setWarehouseId($warehouseId);
    }

    /**
     * Check if current user has any of the given roles
     * 
     * @param  array|string  $roles
     */
    public static function hasRole($roles): bool
    {
        $user = self::currentUser();
        if (!$user) {
            return false;
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        // Convert enum cases to their string values if needed
        $roleNames = array_map(function ($role) {
            return $role instanceof \BackedEnum ? $role->value : $role;
        }, $roles);

        return $user->hasRole($roleNames);
    }

    /**
     * Check if current user is a super admin
     */
    public static function isSuperAdmin(): bool
    {
        return self::hasRole(UserRole::SUPER_ADMIN);
    }

    /**
     * Check if current user is a general manager
     * 
     * @deprecated This role doesn't exist in the system anymore
     * @return bool Always returns false
     */
    public static function isGeneralManager(): bool
    {
        return false;
    }

    /**
     * Check if current user is a branch manager
     */
    public static function isBranchManager(): bool
    {
        return self::hasRole(UserRole::BRANCH_MANAGER);
    }

    /**
     * Check if current user is a warehouse user (manager or staff)
     */
    public static function isWarehouseUser(): bool
    {
        // Align with seeded roles; only WarehouseManager exists currently
        return self::hasRole(UserRole::WAREHOUSE_MANAGER);
    }

    /**
     * Check if current user is a sales person
     */
    public static function isSales(): bool
    {
        return self::hasRole(UserRole::SALES);
    }

    /**
     * Check if current user can access location filters
     */
    public static function canAccessLocationFilters(): bool
    {
        $user = self::currentUser();
        if (!$user) {
            return false;
        }

        return $user->canAccessLocationFilters();
    }

    /**
     * Get the user's assignment type and name
     */
    public static function getAssignment(): string
    {
        $user = self::currentUser();
        if (!$user) {
            return 'Not logged in';
        }

        return $user->assignment;
    }

    /**
     * Check if user has access to a specific branch
     */
    public static function hasAccessToBranch($branchId): bool
    {
        $user = self::currentUser();
        if (!$user) {
            return false;
        }

        return $user->hasAccessToBranch($branchId);
    }

    /**
     * Check if user has access to a specific warehouse
     */
    public static function hasAccessToWarehouse($warehouseId): bool
    {
        $user = self::currentUser();
        if (!$user) {
            return false;
        }

        return $user->hasAccessToWarehouse($warehouseId);
    }

    /**
     * Get the user's role names as an array
     */
    public static function getRoleNames(): array
    {
        $user = self::currentUser();
        if (!$user) {
            return [];
        }

        return $user->getRoleNames();
    }

    /**
     * Check if current user is an admin or manager
     */
    public static function isAdminOrManager(): bool
    {
        return self::hasRole([
            UserRole::SUPER_ADMIN,
            UserRole::BRANCH_MANAGER
        ]);
    }

    /**
     * Check if the current user can manage stock reservations
     */
    public static function canManageStockReservations(): bool
    {
        $user = self::currentUser();
        if (!$user) {
            return false;
        }

        return $user->canManageStockReservations();
    }
}
