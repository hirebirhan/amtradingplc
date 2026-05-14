<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.view');
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->can('sales.view') && $this->hasAccess($user, $sale);
    }

    public function create(User $user): bool
    {
        return $user->can('sales.create');
    }

    public function update(User $user, Sale $sale): bool
    {
        return $user->can('sales.edit') && $this->hasAccess($user, $sale);
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $user->can('sales.delete') && $this->hasAccess($user, $sale);
    }

    private function hasAccess(User $user, Sale $sale): bool
    {
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return true;
        }

        if ($user->branch_id && $sale->branch_id === $user->branch_id) {
            return true;
        }

        if ($user->warehouse_id && $sale->warehouse_id === $user->warehouse_id) {
            return true;
        }

        return false;
    }
}
