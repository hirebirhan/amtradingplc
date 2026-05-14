<?php

namespace App\Policies;

use App\Models\BankAccount;
use App\Models\User;

class BankAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bank-accounts.view');
    }

    public function view(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('bank-accounts.view') && $this->hasAccess($user, $bankAccount);
    }

    public function create(User $user): bool
    {
        return $user->can('bank-accounts.create');
    }

    public function update(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('bank-accounts.edit') && $this->hasAccess($user, $bankAccount);
    }

    public function delete(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('bank-accounts.delete') && $this->hasAccess($user, $bankAccount);
    }

    private function hasAccess(User $user, BankAccount $bankAccount): bool
    {
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return true;
        }

        if ($bankAccount->branch_id && $user->branch_id === $bankAccount->branch_id) {
            return true;
        }

        if ($bankAccount->warehouse_id && $user->warehouse_id === $bankAccount->warehouse_id) {
            return true;
        }

        return false;
    }
}
