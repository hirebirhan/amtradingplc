<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.edit') && ! $this->isProtectedRole($role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.delete') && ! $this->isProtectedRole($role);
    }

    private function isProtectedRole(Role $role): bool
    {
        return in_array($role->name, [
            UserRole::SUPER_ADMIN->value,
            UserRole::GENERAL_MANAGER->value,
        ], true);
    }
}
