<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PriceHistoryResource;
use App\Models\Item;
use App\Models\PriceHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'PriceHistory')]
final class PriceHistoryController extends Controller
{
    #[OA\Get(path: '/price-histories', summary: 'List all price changes', security: [['sanctum' => []]], tags: ['PriceHistory'],
        parameters: [
            new OA\Parameter(name: 'filter[item_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[date_from]', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'filter[date_to]', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: 'Price history list')]
    )]
    public function index(Request $request): ResourceCollection
    {
        abort_unless($request->user()->can('items.view'), 403);
        $query = PriceHistory::with('item');
        if ($itemId = $request->integer('filter.item_id') ?: null) { $query->where('item_id', $itemId); }
        if ($from = $request->input('filter.date_from')) { $query->whereDate('created_at', '>=', $from); }
        if ($to = $request->input('filter.date_to')) { $query->whereDate('created_at', '<=', $to); }
        return PriceHistoryResource::collection($query->latest()->paginate($request->integer('per_page', 20)));
    }

    #[OA\Get(path: '/items/{item}/price-histories', summary: "List price history for one item", security: [['sanctum' => []]], tags: ['PriceHistory'],
        parameters: [new OA\Parameter(name: 'item', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Item price history')]
    )]
    public function forItem(Request $request, Item $item): ResourceCollection
    {
        abort_unless($request->user()->can('items.view'), 403);
        return PriceHistoryResource::collection($item->priceHistories()->latest()->paginate($request->integer('per_page', 20)));
    }
}
