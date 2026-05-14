<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StockHistoryResource;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Stock')]
final class StockHistoryController extends Controller
{
    #[OA\Get(path: '/stock-histories', summary: 'List stock movement history', security: [['sanctum' => []]], tags: ['Stock'],
        parameters: [
            new OA\Parameter(name: 'filter[item_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[warehouse_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[date_from]', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'filter[date_to]', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Stock history list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        abort_unless($request->user()->can('stock.view'), 403);
        $query = StockHistory::with('item', 'warehouse');
        if ($itemId = $request->integer('filter.item_id') ?: null) { $query->where('item_id', $itemId); }
        if ($warehouseId = $request->integer('filter.warehouse_id') ?: null) { $query->where('warehouse_id', $warehouseId); }
        if ($from = $request->input('filter.date_from')) { $query->whereDate('created_at', '>=', $from); }
        if ($to = $request->input('filter.date_to')) { $query->whereDate('created_at', '<=', $to); }
        return StockHistoryResource::collection($query->latest()->paginate($request->integer('per_page', 20)));
    }
}
