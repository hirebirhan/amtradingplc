<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StockResource;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Stock')]
final class StockController extends Controller
{
    #[OA\Get(path: '/stocks', summary: 'List stock levels', security: [['sanctum' => []]], tags: ['Stock'],
        parameters: [
            new OA\Parameter(name: 'filter[branch_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[warehouse_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[item_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[below_reorder]', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Stock list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        $user = $request->user();
        abort_unless($user->can('stock.view'), 403);
        $query = Stock::with('item', 'warehouse', 'branch');
        if (! $user->isSuperAdmin() && ! $user->isGeneralManager()) {
            $query->whereIn('warehouse_id', UserHelper::getAccessibleWarehouseIds());
        }
        if ($branchId = $request->integer('filter.branch_id') ?: null) { $query->where('branch_id', $branchId); }
        if ($warehouseId = $request->integer('filter.warehouse_id') ?: null) { $query->where('warehouse_id', $warehouseId); }
        if ($itemId = $request->integer('filter.item_id') ?: null) { $query->where('item_id', $itemId); }
        if ($request->boolean('filter.below_reorder')) {
            $query->whereHas('item', fn ($q) => $q->whereColumn('reorder_level', '>', 'stocks.piece_count')->where('reorder_level', '>', 0));
        }
        return StockResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    #[OA\Get(path: '/stocks/{id}', summary: 'Get a stock record', security: [['sanctum' => []]], tags: ['Stock'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Stock detail')]
    )]
    public function show(Request $request, Stock $stock): StockResource
    {
        abort_unless($request->user()->can('stock.view'), 403);
        return new StockResource($stock->load('item', 'warehouse', 'branch'));
    }
}
