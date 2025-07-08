<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ItemPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('items.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Item $item): bool
    {
        return $user->hasPermissionTo('items.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('items.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Item $item): bool
    {
        // If the user is a WarehouseUser, they should only be able to update
        // items that belong to their warehouse
        if ($user->isWarehouseUser() && $user->warehouse_id) {
            $itemInWarehouse = $item->stocks()
                ->where('warehouse_id', $user->warehouse_id)
                ->exists();
            return $itemInWarehouse && $user->hasPermissionTo('items.edit');
        }

        // Branch managers can only edit items in their branch's warehouses
        if ($user->isBranchManager() && $user->branch_id) {
            $itemInBranch = $item->stocks()
                ->whereHas('warehouse', function ($query) use ($user) {
                    $query->where('branch_id', $user->branch_id);
                })
                ->exists();
            return $itemInBranch && $user->hasPermissionTo('items.edit');
        }

        // For SuperAdmin or other roles
        return $user->hasPermissionTo('items.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Item $item): bool
    {
        // Only SuperAdmin can delete items
        return $user->hasPermissionTo('items.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Item $item): bool
    {
        // Only SuperAdmin can restore items
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Item $item): bool
    {
        // Only SuperAdmin can force delete items
        return $user->isSuperAdmin();
    }
}