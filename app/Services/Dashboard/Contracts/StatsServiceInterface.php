<?php

namespace App\Services\Dashboard\Contracts;

use App\Models\User;

interface StatsServiceInterface
{
    /**
     * Get dashboard statistics for a user
     */
    public function getStats(User $user): array;
} 