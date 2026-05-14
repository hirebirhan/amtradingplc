<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StockCardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('stock-card.view'), 403);

        $user = $request->user();
        $itemsQuery = Item::query()->orderBy('name');

        if (! $user->isSuperAdmin() && ! $user->isGeneralManager()) {
            $warehouseIds = UserHelper::getAccessibleWarehouseIds();
            $itemsQuery->whereHas('stocks', fn ($q) =>
                $q->whereIn('warehouse_id', $warehouseIds)
            );
        }

        if ($itemId = $request->integer('item_id') ?: null) {
            $itemsQuery->where('id', $itemId);
        }

        $items = $itemsQuery->with('stocks.warehouse')->get();

        $stockHistoryQuery = StockHistory::with('item', 'warehouse')->latest();

        if (! $user->isSuperAdmin() && ! $user->isGeneralManager()) {
            $stockHistoryQuery->whereIn('warehouse_id', UserHelper::getAccessibleWarehouseIds());
        }

        if ($itemId) {
            $stockHistoryQuery->where('item_id', $itemId);
        }

        if ($warehouseId = $request->integer('warehouse_id') ?: null) {
            $stockHistoryQuery->where('warehouse_id', $warehouseId);
        }

        return response()->json([
            'data' => [
                'items'         => $items,
                'stock_history' => $stockHistoryQuery->take(200)->get(),
            ],
        ]);
    }
}
