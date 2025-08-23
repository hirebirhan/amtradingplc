<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\User|null currentUser()
 * @method static bool hasRole(array|string $roles)
 * @method static bool isSuperAdmin()
 * @method static bool isGeneralManager()
 * @method static bool isBranchManager()
 * @method static bool isWarehouseUser()
 * @method static bool isSales()
 * @method static bool canAccessLocationFilters()
 * @method static string getAssignment()
 * @method static bool hasAccessToBranch($branchId)
 * @method static bool hasAccessToWarehouse($warehouseId)
 * @method static array getRoleNames()
 * @method static bool canManageStockReservations()
 * 
 * @see \App\Helpers\UserHelper
 */
class UserHelperFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'user.helper';
    }
}
