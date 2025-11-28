<?php

namespace App\Enums;

enum AuthorizationLevel: string
{
    case FULL_ACCESS = 'full_access';
    case BRANCH_RESTRICTED = 'branch_restricted';
    case NO_ACCESS = 'no_access';

    public static function fromUser($user): self
    {
        if ($user->isSuperAdmin() || $user->isGeneralManager()) {
            return self::FULL_ACCESS;
        }

        if ($user->isBranchManager() && $user->branch_id) {
            return self::BRANCH_RESTRICTED;
        }

        return self::NO_ACCESS;
    }
}