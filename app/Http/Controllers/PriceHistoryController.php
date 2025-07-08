<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PriceHistory;
use Illuminate\Http\Request;

class PriceHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = PriceHistory::with(['item', 'user'])
            ->latest();

        // Filter by item if specified
        if ($request->has('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        // Filter by date range if specified
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $priceHistories = $query->paginate(20);
        $items = Item::orderBy('name')->get();

        return view('price-history.index', compact('priceHistories', 'items'));
    }

    public function show(Item $item)
    {
        $priceHistories = $item->priceHistories()
            ->with(['user'])
            ->latest()
            ->paginate(20);

        return view('price-history.show', compact('item', 'priceHistories'));
    }
}