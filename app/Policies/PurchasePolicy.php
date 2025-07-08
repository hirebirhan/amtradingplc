<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PurchasePolicy
{
    /**
     * Determine whether the user can view any purchases.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('purchases.view');
    }

    /**
     * Determine whether the user can view the purchase.
     */
    public function view(User $user, Purchase $purchase): bool
    {
        if (!$user->can('purchases.view')) {
            return false;
        }

        return $this->hasAccessToPurchase($user, $purchase);
    }

    /**
     * Determine whether the user can create purchases.
     */
    public function create(User $user): bool
    {
        return $user->can('purchases.create');
    }

    /**
     * Determine whether the user can update the purchase.
     */
    public function update(User $user, Purchase $purchase): bool
    {
        if (!$user->can('purchases.edit')) {
            return false;
        }

        return $this->hasAccessToPurchase($user, $purchase);
    }

    /**
     * Determine whether the user can delete the purchase.
     */
    public function delete(User $user, Purchase $purchase): bool
    {
        if (!$user->can('purchases.delete')) {
            return false;
        }

        return $this->hasAccessToPurchase($user, $purchase);
    }

    /**
     * Determine whether the user can approve the purchase.
     */
    public function approve(User $user, Purchase $purchase): bool
    {
        if (!$user->can('purchases.approve')) {
            return false;
        }

        return $this->hasAccessToPurchase($user, $purchase);
    }

    /**
     * Determine whether the user can receive items from the purchase.
     */
    public function receive(User $user, Purchase $purchase): bool
    {
        if (!$user->can('purchases.receive')) {
            return false;
        }

        return $this->hasAccessToPurchase($user, $purchase);
    }

    /**
     * Determine whether the user can restore the purchase.
     */
    public function restore(User $user, Purchase $purchase): bool
    {
        if (!$user->can('purchases.delete')) {
            return false;
        }

        return $this->hasAccessToPurchase($user, $purchase);
    }

    /**
     * Determine whether the user can permanently delete the purchase.
     */
    public function forceDelete(User $user, Purchase $purchase): bool
    {
        if (!$user->can('purchases.delete')) {
            return false;
        }

        return $this->hasAccessToPurchase($user, $purchase);
    }

    /**
     * Check if user has access to a specific purchase based on branch/warehouse assignment.
     */
    private function hasAccessToPurchase(User $user, Purchase $purchase): bool
    {
        // SuperAdmin and GeneralManager have access to all purchases
        if ($user->isSuperAdmin()) {
            return true;
        }

        // If user is assigned to a specific warehouse, they can only access purchases from that warehouse
        if ($user->warehouse_id) {
            return $purchase->warehouse_id === $user->warehouse_id;
        }

        // If user is assigned to a branch (and not a specific warehouse), they can access:
        // 1. Purchases directly assigned to their branch
        // 2. Purchases from warehouses that belong to their branch
        if ($user->branch_id) {
            // Direct branch assignment
            if ($purchase->branch_id === $user->branch_id) {
                return true;
            }

            // Check if purchase warehouse belongs to user's branch
            if ($purchase->warehouse_id && $purchase->warehouse) {
                return $purchase->warehouse->branches->contains('id', $user->branch_id);
            }
        }

        // If user has no specific assignment, they can't access any purchases
        return false;
    }
} 