<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PriceHistoryResource;
use App\Models\Item;
use App\Models\PriceHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class PriceHistoryController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        abort_unless($request->user()->can('items.view'), 403);

        $query = PriceHistory::with('item');

        if ($itemId = $request->integer('filter.item_id') ?: null) {
            $query->where('item_id', $itemId);
        }
        if ($from = $request->input('filter.date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('filter.date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return PriceHistoryResource::collection(
            $query->latest()->paginate($request->integer('per_page', 20))
        );
    }

    public function forItem(Request $request, Item $item): ResourceCollection
    {
        abort_unless($request->user()->can('items.view'), 403);

        return PriceHistoryResource::collection(
            $item->priceHistories()->latest()->paginate($request->integer('per_page', 20))
        );
    }
}
