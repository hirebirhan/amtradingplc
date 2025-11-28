<?php

namespace App\Policies;

use App\Models\Transfer;
use App\Models\User;
use App\Enums\AuthorizationLevel;

class TransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('transfers.view');
    }

    public function view(User $user, Transfer $transfer): bool
    {
        return match (AuthorizationLevel::fromUser($user)) {
            AuthorizationLevel::FULL_ACCESS => true,
            AuthorizationLevel::BRANCH_RESTRICTED => ($transfer->source_type === 'branch' && $transfer->source_id === $user->branch_id) ||
                                                    ($transfer->destination_type === 'branch' && $transfer->destination_id === $user->branch_id),
            AuthorizationLevel::NO_ACCESS => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->can('transfers.create');
    }

    public function update(User $user, Transfer $transfer): bool
    {
        return match (AuthorizationLevel::fromUser($user)) {
            AuthorizationLevel::FULL_ACCESS => true,
            AuthorizationLevel::BRANCH_RESTRICTED => $transfer->source_type === 'branch' && $transfer->source_id === $user->branch_id,
            AuthorizationLevel::NO_ACCESS => false,
        };
    }

    public function approve(User $user, Transfer $transfer): bool
    {
        return match (AuthorizationLevel::fromUser($user)) {
            AuthorizationLevel::FULL_ACCESS => true,
            AuthorizationLevel::BRANCH_RESTRICTED => $transfer->destination_type === 'branch' && $transfer->destination_id === $user->branch_id,
            AuthorizationLevel::NO_ACCESS => false,
        };
    }

    public function delete(User $user, Transfer $transfer): bool
    {
        return match (AuthorizationLevel::fromUser($user)) {
            AuthorizationLevel::FULL_ACCESS => true,
            AuthorizationLevel::BRANCH_RESTRICTED => $transfer->status === 'pending' && $transfer->source_type === 'branch' && $transfer->source_id === $user->branch_id,
            AuthorizationLevel::NO_ACCESS => false,
        };
    }
}