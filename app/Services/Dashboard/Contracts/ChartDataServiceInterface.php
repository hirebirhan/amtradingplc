<?php

namespace App\Services\Dashboard\Contracts;

use App\Models\User;

interface ChartDataServiceInterface
{
    /**
     * Get chart data for a specific range and user
     */
    public function getChartData(User $user, string $range): array;

    /**
     * Get empty chart data for error scenarios
     */
    public function getEmptyChartData(): array;
} 