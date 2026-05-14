<?php

namespace App\Policies;

use App\Models\Credit;
use App\Models\User;

class CreditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('credits.view');
    }

    public function view(User $user, Credit $credit): bool
    {
        return $user->can('credits.view') && $this->hasAccess($user, $credit);
    }

    public function create(User $user): bool
    {
        return $user->can('credits.create');
    }

    public function update(User $user, Credit $credit): bool
    {
        return $user->can('credits.edit') && $this->hasAccess($user, $credit);
    }

    public function delete(User $user, Credit $credit): bool
    {
        return $user->can('credits.delete') && $this->hasAccess($user, $credit);
    }

    private function hasAccess(User $user, Credit $credit): bool
    {
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return true;
        }

        if ($user->branch_id && $credit->branch_id === $user->branch_id) {
            return true;
        }

        if ($user->warehouse_id && $credit->warehouse_id === $user->warehouse_id) {
            return true;
        }

        return false;
    }
}
