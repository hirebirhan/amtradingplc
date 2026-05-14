<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\ChartDataService;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Dashboard')]
final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly ChartDataService $charts,
    ) {}

    #[OA\Get(path: '/dashboard', summary: 'Get dashboard statistics', security: [['sanctum' => []]], tags: ['Dashboard'],
        parameters: [
            new OA\Parameter(name: 'branch_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'warehouse_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Dashboard stats', content: new OA\JsonContent(
            properties: [new OA\Property(property: 'data', type: 'object',
                properties: [
                    new OA\Property(property: 'total_sales', type: 'number'),
                    new OA\Property(property: 'total_purchases', type: 'number'),
                    new OA\Property(property: 'total_revenue', type: 'number'),
                    new OA\Property(property: 'total_expenses', type: 'number'),
                    new OA\Property(property: 'low_stock_items', type: 'integer'),
                    new OA\Property(property: 'outstanding_credits', type: 'number'),
                ]
            )]
        ))]
    )]
    public function index(Request $request): JsonResponse
    {
        $user        = $request->user();
        $branchId    = $request->integer('branch_id') ?: null;
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $data = $this->dashboard->getDashboardData($user, $branchId, $warehouseId);
        return response()->json(['data' => $data]);
    }

    #[OA\Get(path: '/dashboard/charts/{range}', summary: 'Get chart data for a time range', security: [['sanctum' => []]], tags: ['Dashboard'],
        parameters: [
            new OA\Parameter(name: 'range', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['daily', 'weekly', 'monthly', 'yearly'])),
            new OA\Parameter(name: 'branch_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Chart data')]
    )]
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
