<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use App\Support\Access\UserAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('suppliers.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('suppliers.view')
            && UserAccess::canAccessLocation($user, $supplier->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('suppliers.create');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('suppliers.edit')
            && UserAccess::canAccessLocation($user, $supplier->branch_id);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('suppliers.delete')
            && UserAccess::canAccessLocation($user, $supplier->branch_id);
    }
}
