<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
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
        return $user->hasPermissionTo('suppliers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('suppliers.create');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasPermissionTo('suppliers.edit');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        if ($user->isManager()) {
            return true;
        }
        return $user->hasPermissionTo('suppliers.delete');
    }
}