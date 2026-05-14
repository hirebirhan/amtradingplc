<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Support\Access\UserAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'StockReports')]
final class StockReportController extends Controller
{
    #[OA\Get(path: '/stock-reports', summary: 'Stock report with filters', security: [['sanctum' => []]], tags: ['StockReports'],
        parameters: [
            new OA\Parameter(name: 'filter[warehouse_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[branch_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[category_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[below_reorder]', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 50)),
        ],
        responses: [new OA\Response(response: 200, description: 'Stock report')]
    )]
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('items.view'), 403);
        $user = $request->user();
        $query = UserAccess::scopeToWarehouses(Stock::with('item.category', 'warehouse', 'branch'), $user);
        if ($warehouseId = $request->integer('filter.warehouse_id') ?: null) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }
        if ($categoryId = $request->integer('filter.category_id') ?: null) {
            $query->whereHas('item', fn ($q) => $q->where('category_id', $categoryId));
        }
        if ($request->boolean('filter.below_reorder')) {
            $query->whereHas('item', fn ($q) => $q->whereColumn('reorder_level', '>', 'stocks.piece_count')->where('reorder_level', '>', 0));
        }
        $stocks = $query->orderBy('warehouse_id')->paginate($request->integer('per_page', 50));

        return response()->json($stocks);
    }

    #[OA\Get(path: '/stock-reports/export', summary: 'Export stock report as CSV', security: [['sanctum' => []]], tags: ['StockReports'],
        parameters: [
            new OA\Parameter(name: 'filter[warehouse_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[branch_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'CSV file download')]
    )]
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($request->user()->can('items.view'), 403);
        $user = $request->user();
        $query = UserAccess::scopeToWarehouses(Stock::with('item', 'warehouse', 'branch'), $user);
        $stocks = $query->get();
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="stock-report.csv"'];
        $callback = function () use ($stocks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Item', 'SKU', 'Warehouse', 'Branch', 'Pieces', 'Total Units', 'Quantity']);
            foreach ($stocks as $stock) {
                fputcsv($handle, [$stock->item?->name, $stock->item?->sku, $stock->warehouse?->name, $stock->branch?->name, $stock->piece_count, $stock->total_units, $stock->quantity]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
