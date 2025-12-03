<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('customers.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Customer $customer): bool
    {
        // Branch managers can only view customers in their branch
        if ($user->isBranchManager() && $user->branch_id) {
            return $user->branch_id === $customer->branch_id && $user->hasPermissionTo('customers.view');
        }

        // For SuperAdmin or other roles with the permission
        return $user->hasPermissionTo('customers.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('customers.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Customer $customer): bool
    {
        // Branch managers can only update customers in their branch
        if ($user->isBranchManager() && $user->branch_id) {
            return $user->branch_id === $customer->branch_id && $user->hasPermissionTo('customers.edit');
        }

        // For SuperAdmin or other roles with the permission
        return $user->hasPermissionTo('customers.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Customer $customer): bool
    {
        // All managers can delete customers
        if ($user->isManager()) {
            return true;
        }
        
        // Or users with explicit delete permission
        return $user->hasPermissionTo('customers.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Customer $customer): bool
    {
        // Only SuperAdmin can restore customers
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Customer $customer): bool
    {
        // Only SuperAdmin can force delete customers
        return $user->isSuperAdmin();
    }
} 