<?php

namespace App\Policies;

use App\Enums\AuthorizationLevel;
use App\Enums\TransferStatus;
use App\Models\Transfer;
use App\Models\User;

class TransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('transfers.view');
    }

    public function view(User $user, Transfer $transfer): bool
    {
        if (! $user->can('transfers.view')) {
            return false;
        }

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
        if (! $user->can('transfers.edit')) {
            return false;
        }

        return match (AuthorizationLevel::fromUser($user)) {
            AuthorizationLevel::FULL_ACCESS => true,
            AuthorizationLevel::BRANCH_RESTRICTED => $transfer->source_type === 'branch' && $transfer->source_id === $user->branch_id,
            AuthorizationLevel::NO_ACCESS => false,
        };
    }

    public function approve(User $user, Transfer $transfer): bool
    {
        if (! $user->can('transfers.approve')) {
            return false;
        }

        return match (AuthorizationLevel::fromUser($user)) {
            AuthorizationLevel::FULL_ACCESS => true,
            AuthorizationLevel::BRANCH_RESTRICTED => $transfer->destination_type === 'branch' && $transfer->destination_id === $user->branch_id,
            AuthorizationLevel::NO_ACCESS => false,
        };
    }

    public function receive(User $user, Transfer $transfer): bool
    {
        if (! $user->can('transfers.receive')) {
            return false;
        }

        return match (AuthorizationLevel::fromUser($user)) {
            AuthorizationLevel::FULL_ACCESS => true,
            AuthorizationLevel::BRANCH_RESTRICTED => $transfer->destination_type === 'branch' && $transfer->destination_id === $user->branch_id,
            AuthorizationLevel::NO_ACCESS => false,
        };
    }

    public function delete(User $user, Transfer $transfer): bool
    {
        if (! $user->can('transfers.delete')) {
            return false;
        }

        return match (AuthorizationLevel::fromUser($user)) {
            AuthorizationLevel::FULL_ACCESS => true,
            AuthorizationLevel::BRANCH_RESTRICTED => $transfer->status === TransferStatus::PENDING->value && $transfer->source_type === 'branch' && $transfer->source_id === $user->branch_id,
            AuthorizationLevel::NO_ACCESS => false,
        };
    }
}
