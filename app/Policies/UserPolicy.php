<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if (!$user->hasPermissionTo('users.view')) {
            return false;
        }

        // Branch managers can only view users from their own branch
        if ($user->isBranchManager() && $user->branch_id) {
            return $model->branch_id === $user->branch_id || 
                   $model->warehouse?->branches->contains('id', $user->branch_id);
        }

        // Super admins can view all users
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if (!$user->hasPermissionTo('users.edit')) {
            return false;
        }

        // Prevent users from editing themselves through the admin interface
        // (they should use profile settings instead)
        if ($user->id === $model->id) {
            return false;
        }

        // Branch managers can only edit users from their own branch
        if ($user->isBranchManager() && $user->branch_id) {
            return $model->branch_id === $user->branch_id || 
                   $model->warehouse?->branches->contains('id', $user->branch_id);
        }

        // Super admins can edit all users (except themselves)
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        if (!$user->hasPermissionTo('users.delete')) {
            return false;
        }

        // Prevent users from deleting themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Branch managers can only delete users from their own branch
        if ($user->isBranchManager() && $user->branch_id) {
            return $model->branch_id === $user->branch_id || 
                   $model->warehouse?->branches->contains('id', $user->branch_id);
        }

        // Super admins can delete all users (except themselves)
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->hasPermissionTo('users.delete'); // Same permission as delete
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('users.delete') && $user->isSuperAdmin();
    }
}
