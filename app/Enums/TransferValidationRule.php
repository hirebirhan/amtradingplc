<?php

namespace App\Enums;

enum TransferValidationRule: string
{
    case NO_SELF_TRANSFER = 'no_self_transfer';
    case SOURCE_MUST_BE_USER_BRANCH = 'source_must_be_user_branch';
    case DESTINATION_CANNOT_BE_USER_BRANCH = 'destination_cannot_be_user_branch';
    case APPROVAL_ONLY_FOR_DESTINATION_MANAGER = 'approval_only_for_destination_manager';
}