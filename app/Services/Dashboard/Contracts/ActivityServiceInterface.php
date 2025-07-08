<?php

namespace App\Services\Dashboard\Contracts;

use App\Models\User;

interface ActivityServiceInterface
{
    /**
     * Get recent activities for a user
     */
    public function getActivities(User $user, ?int $branchId = null, ?int $warehouseId = null): array;
} 