<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('expenses.view');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->can('expenses.view') && $this->hasAccess($user, $expense);
    }

    public function create(User $user): bool
    {
        return $user->can('expenses.create');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->can('expenses.edit') && $this->hasAccess($user, $expense);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->can('expenses.delete') && $this->hasAccess($user, $expense);
    }

    private function hasAccess(User $user, Expense $expense): bool
    {
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return true;
        }

        return $user->branch_id && $expense->branch_id === $user->branch_id;
    }
}
