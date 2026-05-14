<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\ChartDataService;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly ChartDataService $charts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user      = $request->user();
        $branchId  = $request->integer('branch_id') ?: null;
        $warehouseId = $request->integer('warehouse_id') ?: null;

        $data = $this->dashboard->getDashboardData($user, $branchId, $warehouseId);
        return response()->json(['data' => $data]);
    }

    public function charts(Request $request, string $range): JsonResponse
    {
        $request->validate(['range' => ['in:daily,weekly,monthly,yearly']]);

        $user        = $request->user();
        $branchId    = $request->integer('branch_id') ?: null;
        $warehouseId = $request->integer('warehouse_id') ?: null;

        $data = $this->charts->getChartData($user, $range, $branchId, $warehouseId);
        return response()->json(['data' => $data]);
    }
}
