<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('branches.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Branch $branch): bool
    {
        // Branch managers can only view their own branch
        if ($user->isBranchManager() && $user->branch_id) {
            return $user->branch_id === $branch->id && $user->hasPermissionTo('branches.view');
        }

        // For SuperAdmin or other roles
        return $user->hasPermissionTo('branches.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only SuperAdmin can create branches
        return $user->hasPermissionTo('branches.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Branch $branch): bool
    {
        // Branch managers can only update their own branch and only if they have the permission
        if ($user->isBranchManager() && $user->branch_id) {
            return $user->branch_id === $branch->id && $user->hasPermissionTo('branches.edit');
        }

        // For SuperAdmin or other roles
        return $user->hasPermissionTo('branches.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Branch $branch): bool
    {
        // Only SuperAdmin can delete branches
        return $user->hasPermissionTo('branches.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Branch $branch): bool
    {
        // Only SuperAdmin can restore branches
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Branch $branch): bool
    {
        // Only SuperAdmin can force delete branches
        return $user->isSuperAdmin();
    }
}