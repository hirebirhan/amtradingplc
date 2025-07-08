<?php

namespace App\Services\Dashboard\Contracts;

use App\Models\User;

interface InventoryServiceInterface
{
    /**
     * Get inventory data for a user
     */
    public function getInventoryData(User $user): array;
} 