<?php

namespace App\Http\Controllers;

use App\Models\StockHistory;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use UserHelper;

class StockCardController extends Controller
{
    public function index(Request $request)
    {
        // Get items with access control
        $itemsQuery = \App\Models\Item::query()->orderBy('name');
        $salesQuery = \App\Models\Sale::with('saleItems.item');
        $purchasesQuery = \App\Models\Purchase::with('purchaseItems.item');
        
        // Apply warehouse access control if user doesn't have global access
        if (!UserHelper::hasRole('SystemAdmin')) {
            $warehouseIds = UserHelper::getAccessibleWarehouseIds();
            
            // Filter items by accessible warehouses
            $itemsQuery->whereHas('stocks', function($q) use ($warehouseIds) {
                $q->whereIn('warehouse_id', $warehouseIds);
            });
            
            // Filter sales and purchases by warehouse
            $salesQuery->whereIn('warehouse_id', $warehouseIds);
            $purchasesQuery->whereIn('warehouse_id', $warehouseIds);
        }
        
        $items = $itemsQuery->get();

        if ($request->item_id) {
            $salesQuery->whereHas('saleItems', fn($q) => $q->where('item_id', $request->item_id));
            $purchasesQuery->whereHas('purchaseItems', fn($q) => $q->where('item_id', $request->item_id));
        }
        if ($request->date_from) {
            $salesQuery->whereDate('sale_date', '>=', $request->date_from);
            $purchasesQuery->whereDate('purchase_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $salesQuery->whereDate('sale_date', '<=', $request->date_to);
            $purchasesQuery->whereDate('purchase_date', '<=', $request->date_to);
        }

        $sales = $salesQuery->get();
        $purchases = $purchasesQuery->get();

        $salesByDate = $sales->groupBy(fn($sale) => $sale->sale_date->format('Y-m-d'));
        $purchasesByDate = $purchases->groupBy(fn($purchase) => $purchase->purchase_date->format('Y-m-d'));

        $filters = [
            'item_id' => $request->item_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        return view('stock-card.index', compact('items', 'salesByDate', 'purchasesByDate', 'filters'));
    }

    public function print(Request $request)
    {
        // Build the same query as the Livewire component
        $query = StockHistory::with(['item', 'warehouse', 'user']);
        
        // Apply warehouse access control if user doesn't have global access
        if (!UserHelper::hasRole('SystemAdmin')) {
            $warehouseIds = UserHelper::getAccessibleWarehouseIds();
            $query->whereIn('warehouse_id', $warehouseIds);
        }
        
        $query->when($request->itemFilter, function (Builder $query) use ($request) {
                $query->where('item_id', $request->itemFilter);
            })
            ->when($request->typeFilter, function (Builder $query) use ($request) {
                $query->where('reference_type', $request->typeFilter);
            })
            ->when($request->dateFrom, function (Builder $query) use ($request) {
                $query->whereDate('created_at', '>=', $request->dateFrom);
            })
            ->when($request->dateTo, function (Builder $query) use ($request) {
                $query->whereDate('created_at', '<=', $request->dateTo);
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        $stockMovements = $query->get();

        // Get the selected item for traditional stock card format
        $selectedItem = null;
        if ($request->itemFilter) {
            $selectedItem = Item::find($request->itemFilter);
        }

        // Get filter information for display
        $filters = [
            'itemName' => $request->itemFilter ? Item::find($request->itemFilter)?->name : null,
            'dateFrom' => $request->dateFrom,
            'dateTo' => $request->dateTo,
        ];

        return view('stock-card.print', compact('stockMovements', 'filters', 'selectedItem'));
    }
} 