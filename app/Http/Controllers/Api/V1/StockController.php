<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StockResource;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class StockController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $user = $request->user();
        abort_unless($user->can('stock.view'), 403);

        $query = Stock::with('item', 'warehouse', 'branch');

        // Branch isolation: restrict non-admins to accessible warehouses
        if (! $user->isSuperAdmin() && ! $user->isGeneralManager()) {
            $ids = UserHelper::getAccessibleWarehouseIds();
            $query->whereIn('warehouse_id', $ids);
        }

        if ($branchId = $request->integer('filter.branch_id') ?: null) {
            $query->where('branch_id', $branchId);
        }

        if ($warehouseId = $request->integer('filter.warehouse_id') ?: null) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($itemId = $request->integer('filter.item_id') ?: null) {
            $query->where('item_id', $itemId);
        }

        if ($request->boolean('filter.below_reorder')) {
            $query->whereHas('item', fn ($q) =>
                $q->whereColumn('reorder_level', '>', 'stocks.piece_count')
                  ->where('reorder_level', '>', 0)
            );
        }

        return StockResource::collection(
            $query->paginate($request->integer('per_page', 20))
        );
    }

    public function show(Request $request, Stock $stock): StockResource
    {
        abort_unless($request->user()->can('stock.view'), 403);
        return new StockResource($stock->load('item', 'warehouse', 'branch'));
    }
}
