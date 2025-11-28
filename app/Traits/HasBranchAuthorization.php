<?php

namespace App\Traits;

use App\Models\User;
use App\Enums\AuthorizationLevel;

trait HasBranchAuthorization
{
    /**
     * Scope query for user based on branch authorization.
     */
    public function scopeForUser($query, User $user)
    {
        return match (AuthorizationLevel::fromUser($user)) {
            AuthorizationLevel::FULL_ACCESS => $query,
            AuthorizationLevel::BRANCH_RESTRICTED => $query->where('branch_id', $user->branch_id),
            AuthorizationLevel::NO_ACCESS => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Check if user can access this record based on branch authorization.
     */
    public function canUserAccess(User $user): bool
    {
        return match (AuthorizationLevel::fromUser($user)) {
            AuthorizationLevel::FULL_ACCESS => true,
            AuthorizationLevel::BRANCH_RESTRICTED => $user->branch_id === $this->branch_id,
            AuthorizationLevel::NO_ACCESS => false,
        };
    }
}