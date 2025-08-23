<?php

namespace App\Services\Dashboard\Contracts;

use App\Models\User;

interface ChartDataServiceInterface
{
    /**
     * Get chart data for a specific range and user with optional filters
     * 
     * @param User $user The authenticated user
     * @param string $range The time range for the chart (e.g., 'today', 'week', 'month')
     * @param int|null $branchId Optional branch ID to filter by
     * @param int|null $warehouseId Optional warehouse ID to filter by
     * @return array Chart data with labels, sales, and purchases
     */
    public function getChartData(
        User $user, 
        string $range, 
        ?int $branchId = null, 
        ?int $warehouseId = null
    ): array;

    /**
     * Get empty chart data for error scenarios
     */
    public function getEmptyChartData(): array;
} 