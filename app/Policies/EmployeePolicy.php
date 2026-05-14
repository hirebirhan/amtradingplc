<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employees.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('employees.view') && $this->hasAccess($user, $employee);
    }

    public function create(User $user): bool
    {
        return $user->can('employees.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employees.edit') && $this->hasAccess($user, $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('employees.delete') && $this->hasAccess($user, $employee);
    }

    private function hasAccess(User $user, Employee $employee): bool
    {
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return true;
        }

        return $user->branch_id && $employee->branch_id === $user->branch_id;
    }
}
