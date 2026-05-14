<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Item;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class StockReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('items.view'), 403);

        $user = $request->user();
        $query = Stock::with('item.category', 'warehouse', 'branch');

        if (! $user->isSuperAdmin() && ! $user->isGeneralManager()) {
            $warehouseIds = UserHelper::getAccessibleWarehouseIds();
            $query->whereIn('warehouse_id', $warehouseIds);
        }

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
            $query->whereHas('item', fn ($q) =>
                $q->whereColumn('reorder_level', '>', 'stocks.piece_count')->where('reorder_level', '>', 0)
            );
        }

        $stocks = $query->orderBy('warehouse_id')->orderByWith('item', 'name')->paginate($request->integer('per_page', 50));

        return response()->json($stocks);
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($request->user()->can('items.view'), 403);

        $user = $request->user();
        $query = Stock::with('item', 'warehouse', 'branch');

        if (! $user->isSuperAdmin() && ! $user->isGeneralManager()) {
            $query->whereIn('warehouse_id', UserHelper::getAccessibleWarehouseIds());
        }

        $stocks = $query->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="stock-report.csv"',
        ];

        $callback = function () use ($stocks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Item', 'SKU', 'Warehouse', 'Branch', 'Pieces', 'Total Units', 'Quantity']);
            foreach ($stocks as $stock) {
                fputcsv($handle, [
                    $stock->item?->name,
                    $stock->item?->sku,
                    $stock->warehouse?->name,
                    $stock->branch?->name,
                    $stock->piece_count,
                    $stock->total_units,
                    $stock->quantity,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
