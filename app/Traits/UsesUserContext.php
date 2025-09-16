<?php

namespace App\Traits;

use App\Facades\UserHelperFacade as UserHelper;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Support\Collection;

/**
 * Controller/Component helper trait to standardize access to user context.
 */
trait UsesUserContext
{
    protected function currentUser(): ?\App\Models\User
    {
        return UserHelper::currentUser();
    }

    protected function currentBranch(): ?Branch
    {
        return UserHelper::currentBranch();
    }

    protected function currentWarehouse(): ?Warehouse
    {
        return UserHelper::currentWarehouse();
    }

    /** @return Collection<int, Warehouse> */
    protected function allowedWarehouses(): Collection
    {
        return UserHelper::allowedWarehouses();
    }

    protected function hasAccessToWarehouse(int $warehouseId): bool
    {
        return UserHelper::hasAccessToWarehouse($warehouseId);
    }

    protected function setBranchContext(int $branchId): bool
    {
        return UserHelper::setBranchId($branchId);
    }

    protected function setWarehouseContext(int $warehouseId): bool
    {
        return UserHelper::setWarehouseId($warehouseId);
    }
}
